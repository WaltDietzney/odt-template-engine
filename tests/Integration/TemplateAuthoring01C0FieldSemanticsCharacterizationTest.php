<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01C0FieldSemanticsCharacterizationTest extends TestCase
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

    public function testCurrentUnifiedInspectionDoesNotPromoteNativeFieldsToBindingsOrDependencies(): void
    {
        $contract = (new OdtTemplate($this->createTemplate()))->inspectTemplate();

        self::assertSame(
            ['classic_name', 'header_classic'],
            array_map(static fn ($binding): ?string => $binding->variableName(), $contract->bindings())
        );
        self::assertSame(
            ['classic_name', 'header_classic'],
            array_map(static fn ($dependency): string => $dependency->name(), $contract->dependencies())
        );

        $serialized = json_encode($contract->toArray(), JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('user_customer', $serialized);
        self::assertStringNotContainsString('flow_customer', $serialized);
    }

    public function testNativeFieldBlindSpotRemainsOriginalSourceStableAcrossRenderAndSave(): void
    {
        $template = new OdtTemplate($this->createTemplate());
        $before = $template->inspectTemplate()->toArray();

        $template->assign([
            'classic_name' => 'Ada',
            'header_classic' => 'Header Ada',
        ]);
        $template->render();
        $template->save($this->outputPath());

        self::assertSame($before, $template->inspectTemplate()->toArray());
    }

    private function createTemplate(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-c0-');
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
            . '<office:automatic-styles/>'
            . '<office:body><office:text>'
            . '<text:user-field-decls>'
            . '<text:user-field-decl office:value-type="string" office:string-value="Walter" text:name="user_customer"/>'
            . '</text:user-field-decls>'
            . '<text:variable-decls>'
            . '<text:variable-decl office:value-type="string" text:name="flow_customer"/>'
            . '</text:variable-decls>'
            . '<text:p>Classic {{classic_name}}</text:p>'
            . '<text:p>User: <text:user-field-get text:name="user_customer">Walter</text:user-field-get></text:p>'
            . '<text:p><text:variable-set text:name="flow_customer" text:display="none" text:formula="ooow:Walter" office:value-type="string" office:string-value="Walter"/>'
            . 'Flow: <text:variable-get text:name="flow_customer">Walter</text:variable-get></text:p>'
            . '</office:text></office:body>'
            . '</office:document-content>'
        );

        $zip->addFromString(
            'styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles' . $this->namespaces() . '>'
            . '<office:styles/><office:automatic-styles/><office:master-styles>'
            . '<style:master-page style:name="Standard"><style:header>'
            . '<text:p>Header {{header_classic}} / <text:user-field-get text:name="user_customer">Walter</text:user-field-get></text:p>'
            . '</style:header></style:master-page>'
            . '</office:master-styles></office:document-styles>'
        );

        $zip->addFromString(
            'meta.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"><office:meta/></office:document-meta>'
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

    private function outputPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-c0-out-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        return $path;
    }

    private function namespaces(): string
    {
        return ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"';
    }
}
