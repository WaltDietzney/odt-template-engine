<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TableLayout01CSlice3VerticalCellAlignmentTest extends TestCase
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO_NS = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';

    /** @var list<string> */
    private array $outputs = [];

    protected function tearDown(): void
    {
        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function friendlyAlignmentProvider(): iterable
    {
        yield 'top' => ['top'];
        yield 'middle' => ['middle'];
        yield 'bottom' => ['bottom'];
        yield 'automatic' => ['automatic'];
    }

    #[DataProvider('friendlyAlignmentProvider')]
    public function testFriendlyVerticalAlignmentMapsToCellOwnedRequirement(string $alignment): void
    {
        $cell = new RichTableCell('Value', ['vertical-align' => $alignment]);

        self::assertSame(
            ['style:vertical-align' => $alignment],
            $cell->getStyle()
        );

        $requirements = iterator_to_array($cell->getOwnStyleRequirements());
        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirements[0]->scope());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirements[0]->documentPart());
        self::assertSame('table-cell', $requirements[0]->family());
        self::assertSame([
            'style:table-cell-properties' => [
                'style:vertical-align' => $alignment,
            ],
        ], $requirements[0]->propertyGroups());
    }

    public function testFriendlyVerticalAlignmentIsNormalized(): void
    {
        $cell = new RichTableCell('Value', ['vertical-align' => ' MIDDLE ']);

        self::assertSame(
            ['style:vertical-align' => 'middle'],
            $cell->getStyle()
        );
    }

    public function testInvalidFriendlyVerticalAlignmentIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RichTableCell('Value', ['vertical-align' => 'center']);
    }

    public function testNativeVerticalAlignmentCompatibilityRemainsPermissive(): void
    {
        $cell = new RichTableCell('Value', ['style:vertical-align' => 'legacy-native-value']);

        self::assertSame(
            ['style:vertical-align' => 'legacy-native-value'],
            $cell->getStyle()
        );
    }

    public function testVerticalCellAlignmentDoesNotReplaceParagraphHorizontalAlignment(): void
    {
        $cell = new RichTableCell('Value', [
            'vertical-align' => 'middle',
            'text-align' => 'right',
        ]);

        self::assertSame(
            ['style:vertical-align' => 'middle'],
            $cell->getStyle()
        );

        $requirements = iterator_to_array($cell->getStyleRequirements());

        $cellRequirements = array_values(array_filter(
            $requirements,
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table-cell'
        ));
        $paragraphRequirements = array_values(array_filter(
            $requirements,
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'paragraph'
        ));

        self::assertCount(1, $cellRequirements);
        self::assertSame([
            'style:table-cell-properties' => [
                'style:vertical-align' => 'middle',
            ],
        ], $cellRequirements[0]->propertyGroups());

        self::assertNotSame([], $paragraphRequirements);
        self::assertTrue($this->requirementsContainProperty(
            $paragraphRequirements,
            'style:paragraph-properties',
            'fo:text-align',
            'right'
        ));
    }

    #[RunInSeparateProcess]
    public function testFriendlyVerticalAlignmentMaterializesInContentXml(): void
    {
        $cell = new RichTableCell('Value', ['vertical-align' => 'middle']);
        $cellStyleName = $cell->getStyleName();

        $table = (new RichTable())
            ->setTableName('VerticalAlignmentTable')
            ->addRow([$cell]);

        $output = $this->saveTable($table, 'middle');
        $contentXml = $this->entry($output, 'content.xml');
        $stylesXml = $this->entry($output, 'styles.xml');

        self::assertSame(1, $this->styleCountInContainer(
            $contentXml,
            $cellStyleName,
            'table-cell',
            'automatic-styles'
        ));
        self::assertSame(0, $this->styleCountInContainer(
            $stylesXml,
            $cellStyleName,
            'table-cell',
            'styles'
        ));
        self::assertSame('middle', $this->styleProperty(
            $contentXml,
            $cellStyleName,
            'table-cell',
            'table-cell-properties',
            self::STYLE_NS,
            'vertical-align'
        ));
        self::assertStringContainsString(
            'table:style-name="' . $cellStyleName . '"',
            $contentXml
        );
    }

    /**
     * @param list<StyleRequirement> $requirements
     */
    private function requirementsContainProperty(
        array $requirements,
        string $group,
        string $property,
        string $expected
    ): bool {
        foreach ($requirements as $requirement) {
            $groups = $requirement->propertyGroups();
            if (($groups[$group][$property] ?? null) === $expected) {
                return true;
            }
        }

        return false;
    }

    private function saveTable(RichTable $table, string $name): string
    {
        $template = new OdtTemplate($this->templatePath());
        $template->setElement('tableblock', $table);
        $output = $this->outputPath($name);
        $template->save($output);

        return $output;
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_11_table.odt';
    }

    private function outputPath(string $name): string
    {
        $path = sys_get_temp_dir() . '/table-layout-01c-cell-' . $name . '-' . bin2hex(random_bytes(4)) . '.odt';
        $this->outputs[] = $path;

        return $path;
    }

    private function entry(string $path, string $entry): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $value = $zip->getFromName($entry);
            self::assertIsString($value);

            return $value;
        } finally {
            $zip->close();
        }
    }

    private function styleCountInContainer(
        string $xml,
        string $name,
        string $family,
        string $containerLocalName
    ): int {
        $dom = $this->dom($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $xpath->registerNamespace('style', self::STYLE_NS);

        return $xpath->query(sprintf(
            '//office:%s/style:style[@style:name="%s" and @style:family="%s"]',
            $containerLocalName,
            $name,
            $family
        ))->length;
    }

    private function styleProperty(
        string $xml,
        string $styleName,
        string $family,
        string $propertyLocalName,
        string $attributeNamespace,
        string $attributeLocalName
    ): string {
        $dom = $this->dom($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('style', self::STYLE_NS);

        $property = $xpath->query(sprintf(
            '//style:style[@style:name="%s" and @style:family="%s"]/*[local-name()="%s"]',
            $styleName,
            $family,
            $propertyLocalName
        ))->item(0);

        self::assertNotNull($property);
        self::assertInstanceOf(DOMElement::class, $property);

        return $property->getAttributeNS($attributeNamespace, $attributeLocalName)
            ?: $property->getAttribute('style:' . $attributeLocalName);
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }
}
