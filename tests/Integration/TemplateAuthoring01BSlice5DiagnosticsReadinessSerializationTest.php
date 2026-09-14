<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Template\TemplateContractCapabilities;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01BSlice5DiagnosticsReadinessSerializationTest extends TestCase
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

    public function testRecognizedAndDuplicateFindingsPreserveReadyMapping(): void
    {
        $contract = (new OdtTemplate($this->template(
            '<text:p>{{name}}</text:p>'
            . '<text:section text:name="#foreach:experience"><text:p>{{company}}</text:p></text:section>'
            . '<table:table table:name="Duplicate"/>'
            . '<table:table table:name="Duplicate"/>'
        )))->inspectTemplate();

        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('inspection')
        );
        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('dependency_mapping')
        );
        self::assertSame(
            ['duplicate_native_name', 'duplicate_native_name'],
            array_map(static fn ($item): string => $item->code(), $contract->diagnostics())
        );
        self::assertSame('RECOGNIZED', array_values(array_filter(
            $contract->controls(),
            static fn ($control): bool => $control->representation() === 'NATIVE_SECTION_DECLARATION'
        ))[0]->supportState());
    }

    public function testMalformedAndUnsupportedSourceReturnPartialContractWithLimitedMapping(): void
    {
        $contract = (new OdtTemplate($this->template(
            '<text:p>{{name}}</text:p>'
            . '<text:p>{{foo bar}}</text:p>'
            . '<text:section text:name="#foreach experience"><text:p>Malformed declaration</text:p></text:section>'
        )))->inspectTemplate();

        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('inspection')
        );
        self::assertSame(
            TemplateContractCapabilities::LIMITED,
            $contract->capabilities()->readiness('dependency_mapping')
        );

        self::assertSame(['name'], array_map(
            static fn ($dependency): string => $dependency->path(),
            $contract->dependencies()
        ));

        $codes = array_map(
            static fn ($diagnostic): string => $diagnostic->code(),
            $contract->diagnostics()
        );
        self::assertContains('unsupported_template_expression', $codes);
        self::assertContains('malformed_native_section_declaration', $codes);
    }

    public function testContractVersionOneSerializationIsDeterministicAndHasFrozenTopLevelShape(): void
    {
        $template = new OdtTemplate($this->template(
            '<text:p>{{name}}</text:p>'
            . '<text:section text:name="#if:active"><text:p>Active</text:p></text:section>'
        ));

        $first = $template->inspectTemplate()->toArray();
        $second = $template->inspectTemplate()->toArray();

        self::assertSame($first, $second);
        self::assertSame(TemplateContract::CONTRACT_VERSION, $first['contract_version']);
        self::assertSame(
            [
                'contract_version',
                'coverage',
                'bindings',
                'controls',
                'native_objects',
                'dependencies',
                'capabilities',
                'diagnostics',
            ],
            array_keys($first)
        );
        self::assertSame(
            ['inspection', 'dependency_mapping', 'native_field_binding'],
            array_keys($first['capabilities'])
        );

        $json = json_encode($first, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('DOMDocument', $json);
        self::assertStringNotContainsString('DOMElement', $json);
        self::assertStringNotContainsString('DOMXPath', $json);
        self::assertStringNotContainsString('spl_object_id', $json);
    }

    public function testUnifiedDiagnosticsRetainStylesXmlProvenance(): void
    {
        $contract = (new OdtTemplate($this->template(
            '<text:p>{{name}}</text:p>',
            '<text:p>{{foo bar}}</text:p>'
        )))->inspectTemplate();

        $unsupported = array_values(array_filter(
            $contract->diagnostics(),
            static fn ($diagnostic): bool =>
                $diagnostic->code() === 'unsupported_template_expression'
        ));

        self::assertCount(1, $unsupported);
        $provenance = $unsupported[0]->provenance();
        self::assertNotNull($provenance);
        self::assertSame('styles.xml', $provenance->sourcePart());
        self::assertSame('MASTER_PAGE_CONTENT', $provenance->regionKind());
        self::assertSame('Standard', $provenance->regionOwner());
        self::assertSame('style:header', $provenance->carrierKind());
    }

    private function template(string $body, string $header = ''): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-b-slice5-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

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
            . ($header !== '' ? '<style:header>' . $header . '</style:header>' : '')
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
            . '<manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/>'
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
