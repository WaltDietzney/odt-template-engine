<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\DataScopeDescriptor;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01BSlice4NativeOwnershipDeclarativeCandidatesTest extends TestCase
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

    public function testNativeContainmentUsesDeterministicObjectIdentityWithoutCollapsingDuplicates(): void
    {
        $template = new OdtTemplate($this->createTemplate());
        $contract = $template->inspectTemplate();

        $objects = $contract->nativeObjects();
        self::assertCount(11, $objects);
        self::assertCount(11, array_unique(array_map(
            static fn ($object): string => $object->id(),
            $objects
        )));

        $experienceSection = $this->nativeObject($objects, 'section', '#foreach:experience');
        $experienceTable = $this->nativeObject($objects, 'table', 'ExperienceTable');
        $logoFrame = $this->nativeObject($objects, 'frame', 'CompanyLogo');

        self::assertSame([$experienceSection->id()], $experienceTable->ownerIds());
        self::assertSame(
            [$experienceSection->id(), $experienceTable->id()],
            $logoFrame->ownerIds()
        );

        $duplicates = array_values(array_filter(
            $objects,
            static fn ($object): bool => $object->kind() === 'table'
                && $object->name() === 'Duplicate'
        ));
        self::assertCount(2, $duplicates);
        self::assertNotSame($duplicates[0]->id(), $duplicates[1]->id());

        $first = $contract->toArray();
        $second = $template->inspectTemplate()->toArray();
        self::assertSame($first, $second);
    }

    public function testDuplicateDiagnosticsAreTypeScopedAndPreserveAllEvidence(): void
    {
        $contract = (new OdtTemplate($this->createTemplate()))->inspectTemplate();

        $duplicateFindings = array_values(array_filter(
            $contract->diagnostics(),
            static fn ($diagnostic): bool => $diagnostic->code() === 'duplicate_native_name'
        ));

        self::assertCount(2, $duplicateFindings);
        self::assertNotSame(
            $duplicateFindings[0]->subjectId(),
            $duplicateFindings[1]->subjectId()
        );

        $profileObjects = array_values(array_filter(
            $contract->nativeObjects(),
            static fn ($object): bool => $object->name() === 'Profile'
        ));
        self::assertCount(2, $profileObjects);
        self::assertSame(['section', 'table'], array_map(
            static fn ($object): string => $object->kind(),
            $profileObjects
        ));

        $profileDiagnosticSubjects = array_filter(
            $duplicateFindings,
            static fn ($diagnostic): bool => in_array(
                $diagnostic->subjectId(),
                array_map(static fn ($object): string => $object->id(), $profileObjects),
                true
            )
        );
        self::assertSame([], array_values($profileDiagnosticSubjects));
    }

    public function testExplicitSectionDeclarationsAreRecognizedButNotPromotedToExecutableSupport(): void
    {
        $contract = (new OdtTemplate($this->createTemplate()))->inspectTemplate();

        $nativeControls = array_values(array_filter(
            $contract->controls(),
            static fn ($control): bool =>
                $control->representation() === 'NATIVE_SECTION_DECLARATION'
        ));

        self::assertSame(
            ['FOREACH', 'IF', 'FOREACH', 'IF'],
            array_map(static fn ($control): string => $control->kind(), $nativeControls)
        );

        foreach ($nativeControls as $control) {
            self::assertSame('RECOGNIZED', $control->supportState());
            self::assertCount(1, $control->markerEvidence());
            self::assertSame(
                'native_section_name',
                $control->markerEvidence()[0]->representationKind()
            );
            self::assertNotNull($control->carrierNativeObjectId());
        }

        $experience = $nativeControls[0];
        self::assertSame(DataScopeDescriptor::ROOT, $experience->scope()->kind());
        self::assertSame(
            DataScopeDescriptor::COLLECTION_ITEM,
            $experience->createdScope()?->kind()
        );

        $current = $nativeControls[1];
        self::assertSame(
            $experience->createdScope()?->id(),
            $current->scope()->id()
        );

        $projects = $nativeControls[2];
        self::assertSame(
            $experience->createdScope()?->id(),
            $projects->scope()->id()
        );
        self::assertSame(
            DataScopeDescriptor::COLLECTION_ITEM,
            $projects->createdScope()?->kind()
        );

        $paths = array_map(
            static fn ($dependency): string => $dependency->path(),
            $contract->dependencies()
        );
        self::assertContains('experience[]', $paths);
        self::assertContains('experience[].company', $paths);
        self::assertContains('experience[].current', $paths);
        self::assertContains('experience[].projects[]', $paths);
        self::assertContains('experience[].projects[].project_name', $paths);
        self::assertContains('show_header', $paths);

        self::assertSame(
            'experience[].company',
            $this->bindingDependencyPath($contract, 'company')
        );
        self::assertSame(
            'experience[].projects[].project_name',
            $this->bindingDependencyPath($contract, 'project_name')
        );
    }

    public function testMalformedCandidateIsDiagnosedWithoutFuzzyCorrectionAndOrdinarySectionsStayOrdinary(): void
    {
        $contract = (new OdtTemplate($this->createTemplate()))->inspectTemplate();

        $malformed = array_values(array_filter(
            $contract->diagnostics(),
            static fn ($diagnostic): bool =>
                $diagnostic->code() === 'malformed_native_section_declaration'
        ));
        self::assertCount(1, $malformed);

        $nativeControlNames = array_map(
            static fn ($control): string =>
                $control->markerEvidence()[0]->nativeOwnerChain() === []
                    ? ''
                    : implode('/', $control->markerEvidence()[0]->nativeOwnerChain()),
            array_values(array_filter(
                $contract->controls(),
                static fn ($control): bool =>
                    $control->representation() === 'NATIVE_SECTION_DECLARATION'
            ))
        );

        self::assertNotContains('section:#notes', $nativeControlNames);

        $ordinary = $this->nativeObject($contract->nativeObjects(), 'section', '#notes');
        self::assertSame('#notes', $ordinary->name());

        $malformedSection = $this->nativeObject(
            $contract->nativeObjects(),
            'section',
            '#foreach experience'
        );
        self::assertSame($malformedSection->id(), $malformed[0]->subjectId());
    }

    /** @param list<object> $objects */
    private function nativeObject(array $objects, string $kind, string $name): object
    {
        foreach ($objects as $object) {
            if ($object->kind() === $kind && $object->name() === $name) {
                return $object;
            }
        }

        self::fail('Missing native object ' . $kind . ':' . $name);
    }

    private function bindingDependencyPath(object $contract, string $variable): string
    {
        $dependencyPaths = [];
        foreach ($contract->dependencies() as $dependency) {
            $dependencyPaths[$dependency->id()] = $dependency->path();
        }

        foreach ($contract->bindings() as $binding) {
            if ($binding->variableName() === $variable) {
                return $dependencyPaths[$binding->dependencyId()];
            }
        }

        self::fail('Missing binding ' . $variable);
    }

    private function createTemplate(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-b-slice4-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        $body = '<text:section text:name="#foreach:experience">'
            . '<text:p>{{company}}</text:p>'
            . '<table:table table:name="ExperienceTable">'
            . '<table:table-row><table:table-cell><text:p>Company</text:p>'
            . '<draw:frame draw:name="CompanyLogo"><draw:text-box><text:p>Logo</text:p></draw:text-box></draw:frame>'
            . '</table:table-cell></table:table-row></table:table>'
            . '<text:section text:name="#if:current"><text:p>Current</text:p></text:section>'
            . '<text:section text:name="#foreach:projects">'
            . '<text:p>{{project_name}}</text:p>'
            . '</text:section>'
            . '</text:section>'
            . '<text:section text:name="#notes"><text:p>Ordinary</text:p></text:section>'
            . '<text:section text:name="#foreach experience"><text:p>Malformed</text:p></text:section>'
            . '<table:table table:name="Duplicate"/>'
            . '<table:table table:name="Duplicate"/>'
            . '<text:section text:name="Profile"><text:p>Profile</text:p></text:section>'
            . '<table:table table:name="Profile"/>';

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
            . '<style:header>'
            . '<text:section text:name="#if:show_header"><text:p>Header</text:p></text:section>'
            . '</style:header>'
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
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"';
    }
}
