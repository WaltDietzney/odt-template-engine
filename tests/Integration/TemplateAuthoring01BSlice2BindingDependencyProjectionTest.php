<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\DataScopeDescriptor;
use OdtTemplateEngine\Template\TemplateContractCapabilities;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01BSlice2BindingDependencyProjectionTest extends TestCase
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

    public function testBindingsAcrossBodyHeaderAndFooterShareRootDependencies(): void
    {
        $contract = (new OdtTemplate($this->createTemplate()))->inspectTemplate();

        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('dependency_mapping')
        );

        $bindings = $contract->bindings();
        self::assertSame(
            ['name', 'name', 'email', 'bio', 'items', 'name', 'email', 'name'],
            array_map(static fn ($binding): ?string => $binding->variableName(), $bindings)
        );

        $dependencies = $contract->dependencies();
        self::assertSame(
            ['name', 'email', 'bio', 'items'],
            array_map(static fn ($dependency): string => $dependency->name(), $dependencies)
        );

        foreach ($dependencies as $dependency) {
            self::assertSame(DataScopeDescriptor::ROOT, $dependency->scope()->kind());
            self::assertSame('scope_root', $dependency->scope()->id());
            self::assertSame($dependency->name(), $dependency->path());
        }

        $byName = [];
        foreach ($dependencies as $dependency) {
            $byName[$dependency->name()] = $dependency;
        }

        self::assertCount(4, $byName['name']->evidenceIds());
        self::assertCount(2, $byName['email']->evidenceIds());
        self::assertCount(1, $byName['bio']->evidenceIds());
        self::assertCount(1, $byName['items']->evidenceIds());

        foreach ($bindings as $binding) {
            self::assertSame(
                $byName[$binding->variableName()]->id(),
                $binding->dependencyId()
            );
        }

        self::assertSame('FILTERED_SCALAR', $bindings[1]->kind());
        self::assertSame('SPECIAL', $bindings[3]->kind());
        self::assertSame('SPECIAL', $bindings[4]->kind());
        self::assertSame($bindings[0]->dependencyId(), $bindings[1]->dependencyId());
    }

    public function testDependencyIdentityAndSerializationAreDeterministic(): void
    {
        $template = new OdtTemplate($this->createTemplate());

        $first = $template->inspectTemplate()->toArray();
        $second = $template->inspectTemplate()->toArray();

        self::assertSame($first, $second);

        $dependencyIds = array_column($first['dependencies'], 'id');
        self::assertSame($dependencyIds, array_values(array_unique($dependencyIds)));

        foreach ($first['bindings'] as $binding) {
            self::assertContains($binding['dependency_id'], $dependencyIds);
        }

        self::assertSame(
            ['name', 'email', 'bio', 'items'],
            array_column($first['dependencies'], 'path')
        );
    }

    public function testSlice2DefersRepetitionScopedDependenciesWithoutInventingRootSemantics(): void
    {
        $contract = (new OdtTemplate($this->createTemplate(true)))->inspectTemplate();

        self::assertSame([], $contract->controls());
        self::assertSame(
            TemplateContractCapabilities::LIMITED,
            $contract->capabilities()->readiness('dependency_mapping')
        );

        self::assertSame(
            ['name', 'email', 'bio', 'items', 'after'],
            array_map(static fn ($dependency): string => $dependency->name(), $contract->dependencies())
        );

        $companyBindings = array_values(array_filter(
            $contract->bindings(),
            static fn ($binding): bool => $binding->variableName() === 'company'
        ));
        self::assertCount(1, $companyBindings);
        self::assertNull($companyBindings[0]->dependencyId());

        $afterBindings = array_values(array_filter(
            $contract->bindings(),
            static fn ($binding): bool => $binding->variableName() === 'after'
        ));
        self::assertCount(1, $afterBindings);
        self::assertNotNull($afterBindings[0]->dependencyId());

        $after = array_values(array_filter(
            $contract->dependencies(),
            static fn ($dependency): bool => $dependency->name() === 'after'
        ))[0];
        self::assertSame(DataScopeDescriptor::ROOT, $after->scope()->kind());
        self::assertSame('after', $after->path());
    }

    private function createTemplate(bool $withForeach = false): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-b-slice2-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        $foreach = $withForeach
            ? '<text:p>{{#foreach:experience}}</text:p>'
                . '<text:p>{{company}}</text:p>'
                . '<text:p>{{#endforeach}}</text:p>'
                . '<text:p>{{after}}</text:p>'
            : '';

        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content' . $this->namespaces() . '>'
            . '<office:automatic-styles/>'
            . '<office:body><office:text>'
            . '<text:p>{{name}}</text:p>'
            . '<text:p>{{upper:name}}</text:p>'
            . '<text:p>{{email}}</text:p>'
            . '<text:p>{{nl2br:bio}}</text:p>'
            . '<text:p>{{ul:items}}</text:p>'
            . $foreach
            . '</office:text></office:body>'
            . '</office:document-content>'
        );
        $zip->addFromString(
            'styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles' . $this->namespaces() . '>'
            . '<office:styles/><office:automatic-styles/><office:master-styles>'
            . '<style:master-page style:name="Standard">'
            . '<style:header><text:p>{{name}} {{email}}</text:p></style:header>'
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
