<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\DataScopeDescriptor;
use OdtTemplateEngine\Template\TemplateContractCapabilities;
use OdtTemplateEngine\Template\TemplateProcessor;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01BSlice3ClassicControlsDataScopesTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testClassicControlsProduceTruthfulNestedScopedDependencyGraph(): void
    {
        $contract = (new OdtTemplate($this->createTemplate()))->inspectTemplate();

        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('dependency_mapping')
        );

        self::assertSame(
            ['FOREACH', 'IF', 'IF', 'FOREACH', 'IF', 'IF'],
            array_map(static fn ($control): string => $control->kind(), $contract->controls())
        );

        $dependenciesByPath = [];
        foreach ($contract->dependencies() as $dependency) {
            $dependenciesByPath[$dependency->path()] = $dependency;
        }

        self::assertSame(
            [
                'name',
                'experience[]',
                'experience[].company',
                'experience[].current',
                'experience[].gender',
                'experience[].projects[]',
                'experience[].projects[].project_name',
                'experience[].projects[].active',
                'after',
                'status',
                'priority',
            ],
            array_keys($dependenciesByPath)
        );

        self::assertSame('COLLECTION', $dependenciesByPath['experience[]']->kind());
        self::assertSame('COLLECTION', $dependenciesByPath['experience[].projects[]']->kind());

        $experienceScope = $dependenciesByPath['experience[].company']->scope();
        self::assertSame(DataScopeDescriptor::COLLECTION_ITEM, $experienceScope->kind());
        self::assertSame('experience[]', $experienceScope->pathPrefix());
        self::assertSame(
            $dependenciesByPath['experience[]']->id(),
            $experienceScope->collectionDependencyId()
        );

        $projectScope = $dependenciesByPath['experience[].projects[].project_name']->scope();
        self::assertSame(DataScopeDescriptor::COLLECTION_ITEM, $projectScope->kind());
        self::assertSame('experience[].projects[]', $projectScope->pathPrefix());
        self::assertSame($experienceScope->id(), $projectScope->parentId());
        self::assertSame(
            $dependenciesByPath['experience[].projects[]']->id(),
            $projectScope->collectionDependencyId()
        );

        $company = $this->bindingByVariable($contract->bindings(), 'company');
        self::assertSame(
            $dependenciesByPath['experience[].company']->id(),
            $company->dependencyId()
        );

        $projectName = $this->bindingByVariable($contract->bindings(), 'project_name');
        self::assertSame(
            $dependenciesByPath['experience[].projects[].project_name']->id(),
            $projectName->dependencyId()
        );

        $after = $this->bindingByVariable($contract->bindings(), 'after');
        self::assertSame($dependenciesByPath['after']->id(), $after->dependencyId());

        $rootNames = array_values(array_filter(
            $contract->bindings(),
            static fn ($binding): bool => $binding->variableName() === 'name'
        ));
        self::assertCount(3, $rootNames);
        foreach ($rootNames as $binding) {
            self::assertSame($dependenciesByPath['name']->id(), $binding->dependencyId());
        }
    }

    public function testControlDescriptorsPreserveMarkerEvidenceAndConditionDependencies(): void
    {
        $contract = (new OdtTemplate($this->createTemplate()))->inspectTemplate();
        $controls = $contract->controls();

        $experience = $controls[0];
        self::assertSame('CLASSIC', $experience->representation());
        self::assertSame('SUPPORTED', $experience->supportState());
        self::assertCount(2, $experience->markerEvidence());
        self::assertSame(
            DataScopeDescriptor::ROOT,
            $experience->scope()->kind()
        );
        self::assertSame(
            DataScopeDescriptor::COLLECTION_ITEM,
            $experience->createdScope()?->kind()
        );

        $genderCondition = $controls[2];
        self::assertSame('IF', $genderCondition->kind());
        self::assertCount(2, $genderCondition->markerEvidence());

        $rootCondition = $controls[5];
        self::assertSame('IF', $rootCondition->kind());
        self::assertCount(4, $rootCondition->markerEvidence());
        self::assertCount(2, $rootCondition->dependencyIds());

        $pathsById = [];
        foreach ($contract->dependencies() as $dependency) {
            $pathsById[$dependency->id()] = $dependency->path();
        }

        self::assertSame(
            ['status', 'priority'],
            array_map(
                static fn (string $id): string => $pathsById[$id],
                $rootCondition->dependencyIds()
            )
        );

        foreach ($rootCondition->markerEvidence() as $marker) {
            self::assertSame('classic_control_marker', $marker->representationKind());
        }
    }

    public function testUnifiedInspectionUsesRuntimeConditionGrammarWithoutChangingFocusedInspection(): void
    {
        $template = new OdtTemplate($this->createTemplate());
        $contract = $template->inspectTemplate();

        $paths = array_map(
            static fn ($dependency): string => $dependency->path(),
            $contract->dependencies()
        );
        self::assertContains('experience[].gender', $paths);
        self::assertContains('status', $paths);

        $focused = $template->inspectTemplateStructure();
        $unsupported = array_values(array_filter(
            $focused->expressions(),
            static fn ($expression): bool => $expression->rawText() === '{{#if:gender=="female"}}'
                || $expression->rawText() === '{{#if:status=="active"}}'
        ));
        self::assertCount(2, $unsupported);
        foreach ($unsupported as $expression) {
            self::assertSame('UNSUPPORTED', $expression->kind());
            self::assertSame('UNSAFE', $expression->classification());
        }

        $processor = new TemplateProcessor();
        self::assertTrue($processor->evaluateCondition('gender=="female"', ['gender' => 'female']));
        self::assertFalse($processor->evaluateCondition('gender=="female"', ['gender' => 'male']));
        self::assertTrue($processor->evaluateCondition('score>=10', ['score' => 10]));
        self::assertTrue($processor->evaluateCondition('current', ['current' => true]));
    }

    public function testNestedClassicControlsExposeCompatibilityFindingsWithoutCorruptingScope(): void
    {
        $contract = (new OdtTemplate($this->createTemplate()))->inspectTemplate();

        $findings = array_values(array_filter(
            $contract->diagnostics(),
            static fn ($diagnostic): bool =>
                $diagnostic->code() === 'classic_nested_control_runtime_limitation'
        ));

        self::assertNotEmpty($findings);
        foreach ($findings as $finding) {
            self::assertSame('warning', $finding->severity());
            self::assertNotNull($finding->subjectId());
            self::assertNotNull($finding->provenance());
        }

        $paths = array_map(
            static fn ($dependency): string => $dependency->path(),
            $contract->dependencies()
        );
        self::assertContains('experience[].current', $paths);
        self::assertContains('experience[].projects[].active', $paths);
        self::assertNotContains('current', $paths);
        self::assertNotContains('active', $paths);
    }

    public function testSlice3SerializationIsDeterministic(): void
    {
        $template = new OdtTemplate($this->createTemplate());

        self::assertSame(
            $template->inspectTemplate()->toArray(),
            $template->inspectTemplate()->toArray()
        );
    }

    /** @param list<object> $bindings */
    private function bindingByVariable(array $bindings, string $variable): object
    {
        foreach ($bindings as $binding) {
            if ($binding->variableName() === $variable) {
                return $binding;
            }
        }

        self::fail('Missing binding: ' . $variable);
    }

    private function createTemplate(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-b-slice3-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        $body = '<text:p>{{name}}</text:p>'
            . '<text:p>{{#foreach:experience}}</text:p>'
            . '<text:p>{{company}}</text:p>'
            . '<text:p>{{#if:current}}</text:p>'
            . '<text:p>Current</text:p>'
            . '<text:p>{{#endif}}</text:p>'
            . '<text:p>{{#if:gender=="female"}}</text:p>'
            . '<text:p>Female</text:p>'
            . '<text:p>{{#endif}}</text:p>'
            . '<text:p>{{#foreach:projects}}</text:p>'
            . '<text:p>{{project_name}}</text:p>'
            . '<text:p>{{#if:active}}</text:p>'
            . '<text:p>Active</text:p>'
            . '<text:p>{{#endif}}</text:p>'
            . '<text:p>{{#endforeach}}</text:p>'
            . '<text:p>{{#endforeach}}</text:p>'
            . '<text:p>{{after}}</text:p>'
            . '<text:p>{{#if:status=="active"}}</text:p>'
            . '<text:p>Active status</text:p>'
            . '<text:p>{{#elseif:priority}}</text:p>'
            . '<text:p>Priority</text:p>'
            . '<text:p>{{#else}}</text:p>'
            . '<text:p>Fallback</text:p>'
            . '<text:p>{{#endif}}</text:p>';

        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content' . $this->namespaces() . '>'
            . '<office:automatic-styles/><office:body><office:text>'
            . $body
            . '</office:text></office:body></office:document-content>'
        );
        $zip->addFromString(
            'styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles' . $this->namespaces() . '>'
            . '<office:styles/><office:automatic-styles/><office:master-styles>'
            . '<style:master-page style:name="Standard">'
            . '<style:header><text:p>{{name}}</text:p></style:header>'
            . '<style:footer><text:p>{{name}}</text:p></style:footer>'
            . '</style:master-page></office:master-styles>'
            . '</office:document-styles>'
        );
        $zip->addFromString(
            'meta.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">'
            . '<office:meta/></office:document-meta>'
        );
        $zip->addFromString(
            'META-INF/manifest.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">'
            . '<manifest:file-entry manifest:full-path="/"'
            . ' manifest:media-type="application/vnd.oasis.opendocument.text"/>'
            . '<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>'
            . '</manifest:manifest>'
        );
        $zip->close();

        return $path;
    }

    private function namespaces(): string
    {
        return ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
            . ' xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"';
    }
}
