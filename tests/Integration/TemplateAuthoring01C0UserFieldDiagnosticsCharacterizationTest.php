<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01C0UserFieldDiagnosticsCharacterizationTest extends TestCase
{
    private const TEXT_NS = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';

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

    public function testOrphanUserFieldGetLoadsPersistsAndProjectsMalformedContractEvidence(): void
    {
        $path = $this->createTemplate(
            contentDeclarations: '',
            contentBody: '<text:p>Orphan: <text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            stylesDeclarations: '',
            stylesHeader: ''
        );

        $template = new OdtTemplate($path);
        $contract = $template->inspectTemplate();

        self::assertCount(1, $contract->bindings());
        self::assertSame('MALFORMED', $contract->bindings()[0]->supportState());
        self::assertSame([], $contract->dependencies());
        self::assertSame(
            ['orphan_user_field_reference'],
            array_map(static fn ($diagnostic): string => $diagnostic->code(), $contract->diagnostics())
        );

        $output = $this->outputPath('orphan');
        $template->save($output);

        self::assertSame(
            ['customer'],
            $this->fieldGetNamesFromArchive($output, 'content.xml')
        );
        self::assertSame(
            [],
            $this->fieldDeclarationsFromArchive($output, 'content.xml')
        );
    }

    public function testDuplicateDeclarationsInOnePartRemainPhysicallyDistinct(): void
    {
        $path = $this->createTemplate(
            contentDeclarations:
                $this->declaration('customer', 'string', 'Walter')
                . $this->declaration('customer', 'string', 'Maria'),
            contentBody: '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            stylesDeclarations: '',
            stylesHeader: ''
        );

        $template = new OdtTemplate($path);
        $output = $this->outputPath('duplicate');
        $template->save($output);

        self::assertSame(
            [
                ['name' => 'customer', 'type' => 'string', 'value' => 'Walter'],
                ['name' => 'customer', 'type' => 'string', 'value' => 'Maria'],
            ],
            $this->fieldDeclarationsFromArchive($output, 'content.xml')
        );

        self::assertSame(
            ['ambiguous_user_field_declaration'],
            array_map(
                static fn ($diagnostic): string => $diagnostic->code(),
                $template->inspectTemplate()->diagnostics()
            )
        );
    }

    public function testConflictingCrossPartDeclarationsRemainContradictory(): void
    {
        $path = $this->createTemplate(
            contentDeclarations: $this->declaration('customer', 'string', 'Walter'),
            contentBody: '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            stylesDeclarations: $this->declaration('customer', 'string', 'Maria'),
            stylesHeader: '<text:p><text:user-field-get text:name="customer">Maria</text:user-field-get></text:p>'
        );

        $template = new OdtTemplate($path);
        $output = $this->outputPath('cross-part-conflict');
        $template->save($output);

        self::assertSame(
            [['name' => 'customer', 'type' => 'string', 'value' => 'Walter']],
            $this->fieldDeclarationsFromArchive($output, 'content.xml')
        );
        self::assertSame(
            [['name' => 'customer', 'type' => 'string', 'value' => 'Maria']],
            $this->fieldDeclarationsFromArchive($output, 'styles.xml')
        );

        self::assertSame(
            ['conflicting_user_field_value'],
            array_map(
                static fn ($diagnostic): string => $diagnostic->code(),
                $template->inspectTemplate()->diagnostics()
            )
        );
    }

    public function testSameLogicalNameCanCarryConflictingValueTypesAcrossParts(): void
    {
        $path = $this->createTemplate(
            contentDeclarations: $this->declaration('customer', 'string', 'Walter'),
            contentBody: '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            stylesDeclarations: $this->declaration('customer', 'float', '42'),
            stylesHeader: '<text:p><text:user-field-get text:name="customer">42</text:user-field-get></text:p>'
        );

        $template = new OdtTemplate($path);
        $output = $this->outputPath('type-conflict');
        $template->save($output);

        self::assertSame(
            [['name' => 'customer', 'type' => 'string', 'value' => 'Walter']],
            $this->fieldDeclarationsFromArchive($output, 'content.xml')
        );
        self::assertSame(
            [['name' => 'customer', 'type' => 'float', 'value' => '42']],
            $this->fieldDeclarationsFromArchive($output, 'styles.xml')
        );

        self::assertSame(
            ['conflicting_user_field_type'],
            array_map(
                static fn ($diagnostic): string => $diagnostic->code(),
                $template->inspectTemplate()->diagnostics()
            )
        );
    }

    public function testUnreferencedEmptyDeclarationIsPreservedAsSourceEvidence(): void
    {
        $path = $this->createTemplate(
            contentDeclarations:
                $this->declaration('', 'string', '')
                . $this->declaration('customer', 'string', 'Walter'),
            contentBody: '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            stylesDeclarations: '',
            stylesHeader: ''
        );

        $template = new OdtTemplate($path);
        $output = $this->outputPath('empty-declaration');
        $template->save($output);

        self::assertSame(
            [
                ['name' => '', 'type' => 'string', 'value' => ''],
                ['name' => 'customer', 'type' => 'string', 'value' => 'Walter'],
            ],
            $this->fieldDeclarationsFromArchive($output, 'content.xml')
        );

        self::assertSame([], $template->inspectTemplate()->diagnostics());
        self::assertSame(
            ['customer'],
            array_map(
                static fn ($dependency): string => $dependency->name(),
                $template->inspectTemplate()->dependencies()
            )
        );
    }

    private function createTemplate(
        string $contentDeclarations,
        string $contentBody,
        string $stylesDeclarations,
        string $stylesHeader
    ): string {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-c-r7-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');

        $contentDeclBlock = $contentDeclarations === ''
            ? ''
            : '<text:user-field-decls>' . $contentDeclarations . '</text:user-field-decls>';

        $stylesDeclBlock = $stylesDeclarations === ''
            ? ''
            : '<text:user-field-decls>' . $stylesDeclarations . '</text:user-field-decls>';

        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content' . $this->namespaces() . '>'
            . '<office:automatic-styles/>'
            . '<office:body><office:text>'
            . $contentDeclBlock
            . $contentBody
            . '</office:text></office:body>'
            . '</office:document-content>'
        );

        $zip->addFromString(
            'styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles' . $this->namespaces() . '>'
            . '<office:styles/><office:automatic-styles/><office:master-styles>'
            . '<style:master-page style:name="Standard"><style:header>'
            . $stylesDeclBlock
            . $stylesHeader
            . '</style:header></style:master-page>'
            . '</office:master-styles></office:document-styles>'
        );

        $zip->addFromString(
            'meta.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-meta xmlns:office="' . self::OFFICE_NS . '">'
            . '<office:meta/></office:document-meta>'
        );

        $zip->addFromString(
            'META-INF/manifest.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest:manifest'
            . ' xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">'
            . '<manifest:file-entry manifest:full-path="/"'
            . ' manifest:media-type="application/vnd.oasis.opendocument.text"/>'
            . '<manifest:file-entry manifest:full-path="content.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="styles.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="meta.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '</manifest:manifest>'
        );

        $zip->close();

        return $path;
    }

    private function declaration(string $name, string $type, string $value): string
    {
        $valueAttribute = $type === 'string'
            ? ' office:string-value="' . htmlspecialchars($value, ENT_QUOTES | ENT_XML1) . '"'
            : ' office:value="' . htmlspecialchars($value, ENT_QUOTES | ENT_XML1) . '"';

        return '<text:user-field-decl'
            . ' office:value-type="' . htmlspecialchars($type, ENT_QUOTES | ENT_XML1) . '"'
            . $valueAttribute
            . ' text:name="' . htmlspecialchars($name, ENT_QUOTES | ENT_XML1) . '"/>';
    }

    /** @return list<array{name:string,type:string,value:string}> */
    private function fieldDeclarationsFromArchive(string $path, string $part): array
    {
        $dom = $this->loadPart($path, $part);
        $result = [];

        foreach ($dom->getElementsByTagNameNS(self::TEXT_NS, 'user-field-decl') as $decl) {
            if (!$decl instanceof DOMElement) {
                continue;
            }

            $type = $decl->getAttributeNS(self::OFFICE_NS, 'value-type');
            $value = $type === 'string'
                ? $decl->getAttributeNS(self::OFFICE_NS, 'string-value')
                : $decl->getAttributeNS(self::OFFICE_NS, 'value');

            $result[] = [
                'name' => $decl->getAttributeNS(self::TEXT_NS, 'name'),
                'type' => $type,
                'value' => $value,
            ];
        }

        return $result;
    }

    /** @return list<string> */
    private function fieldGetNamesFromArchive(string $path, string $part): array
    {
        $dom = $this->loadPart($path, $part);
        $result = [];

        foreach ($dom->getElementsByTagNameNS(self::TEXT_NS, 'user-field-get') as $get) {
            if ($get instanceof DOMElement) {
                $result[] = $get->getAttributeNS(self::TEXT_NS, 'name');
            }
        }

        return $result;
    }

    private function loadPart(string $path, string $part): DOMDocument
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $xml = $zip->getFromName($part);
        } finally {
            $zip->close();
        }

        self::assertIsString($xml);

        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }

    private function outputPath(string $suffix): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-c-r7-' . $suffix . '-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        return $path;
    }

    private function namespaces(): string
    {
        return ' xmlns:office="' . self::OFFICE_NS . '"'
            . ' xmlns:text="' . self::TEXT_NS . '"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
            . ' xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0"';
    }
}
