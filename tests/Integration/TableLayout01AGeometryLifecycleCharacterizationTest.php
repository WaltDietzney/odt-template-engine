<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes TABLE-LAYOUT-01 geometry materialization and lifecycle behavior.
 *
 * These tests preserve current behavior only; they do not approve a target API.
 */
final class TableLayout01AGeometryLifecycleCharacterizationTest extends TestCase
{
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

    #[RunInSeparateProcess]
    public function testNativeTableGeometryCoexistsWithAbsoluteColumnWidthsAfterSave(): void
    {
        $table = new RichTable();
        $table->setTableName('TL01Absolute');
        $table->setStyle([
            'style:width' => '12cm',
            'table:align' => 'left',
        ]);
        $table->setColumnWidths(['4cm', '8cm']);
        $table->addRow(['A', 'B']);

        $template = new OdtTemplate($this->templatePath());
        $template->setElement('tableblock', $table);
        $output = $this->outputPath('absolute');
        $template->save($output);

        $stylesXml = $this->entry($output, 'styles.xml');
        $contentXml = $this->entry($output, 'content.xml');

        $tableStyleName = $table->getTableStyleName();
        self::assertNotNull($tableStyleName);
        self::assertSame(0, $this->styleCount($stylesXml, $tableStyleName, 'table'));
        self::assertSame(1, $this->styleCount($contentXml, $tableStyleName, 'table'));
        self::assertSame('12cm', $this->styleProperty(
            $contentXml,
            $tableStyleName,
            'table',
            'table-properties',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'width'
        ));
        self::assertSame('left', $this->styleProperty(
            $contentXml,
            $tableStyleName,
            'table',
            'table-properties',
            'urn:oasis:names:tc:opendocument:xmlns:table:1.0',
            'align'
        ));
        self::assertSame('4cm', $this->styleProperty(
            $contentXml,
            'co0',
            'table-column',
            'table-column-properties',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'column-width'
        ));
        self::assertSame('8cm', $this->styleProperty(
            $contentXml,
            'co1',
            'table-column',
            'table-column-properties',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'column-width'
        ));
    }

    #[RunInSeparateProcess]
    public function testRelativeTableGeometryCoexistsWithNormalizedRelativeColumnsAfterSave(): void
    {
        $table = new RichTable();
        $table->setTableName('TL01Relative');
        $table->setStyle([
            'style:rel-width' => '60%',
            'table:align' => 'left',
        ]);
        $table->setColumnWidthRatios([2, 1, 1]);
        $table->addRow(['A', 'B', 'C']);

        $template = new OdtTemplate($this->templatePath());
        $template->setElement('tableblock', $table);
        $output = $this->outputPath('relative');
        $template->save($output);

        $stylesXml = $this->entry($output, 'styles.xml');
        $contentXml = $this->entry($output, 'content.xml');

        $tableStyleName = $table->getTableStyleName();
        self::assertNotNull($tableStyleName);
        self::assertSame(0, $this->styleCount($stylesXml, $tableStyleName, 'table'));
        self::assertSame(1, $this->styleCount($contentXml, $tableStyleName, 'table'));
        self::assertSame('60%', $this->styleProperty(
            $contentXml,
            $tableStyleName,
            'table',
            'table-properties',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'rel-width'
        ));

        self::assertSame('32766*', $this->styleProperty(
            $contentXml,
            'co0',
            'table-column',
            'table-column-properties',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'rel-column-width'
        ));
        self::assertSame('16383*', $this->styleProperty(
            $contentXml,
            'co1',
            'table-column',
            'table-column-properties',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'rel-column-width'
        ));
        self::assertSame('16386*', $this->styleProperty(
            $contentXml,
            'co2',
            'table-column',
            'table-column-properties',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'rel-column-width'
        ));
    }

    #[RunInSeparateProcess]
    public function testMinimumHeightAndNativeVerticalAlignmentSurviveRepeatedSaveWithoutDuplication(): void
    {
        $cell = new RichTableCell('Middle', ['style:vertical-align' => 'middle']);
        $table = new RichTable();
        $table->setTableName('TL01Lifecycle');
        $table->addRow([$cell], ['min-row-height' => '2cm']);

        $template = new OdtTemplate($this->templatePath());
        $template->setElement('tableblock', $table);

        $first = $this->outputPath('lifecycle-first');
        $template->save($first);
        $second = $this->outputPath('lifecycle-second');
        $template->save($second);

        foreach ([$first, $second] as $output) {
            $contentXml = $this->entry($output, 'content.xml');

            self::assertSame(1, $this->styleCount($contentXml, 'TL01Lifecycle_ro0', 'table-row'));
            self::assertSame('2cm', $this->styleProperty(
                $contentXml,
                'TL01Lifecycle_ro0',
                'table-row',
                'table-row-properties',
                'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                'min-row-height'
            ));

            self::assertSame(1, $this->styleCount($contentXml, $cell->getStyleName(), 'table-cell'));
            self::assertSame('middle', $this->styleProperty(
                $contentXml,
                $cell->getStyleName(),
                'table-cell',
                'table-cell-properties',
                'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                'vertical-align'
            ));
        }
    }

    #[RunInSeparateProcess]
    public function testUnsupportedExactRowHeightDoesNotMaterializeRowStyleOrReference(): void
    {
        $table = new RichTable();
        $table->setTableName('TL01ExactIgnored');
        $table->addRow(['Exact'], ['row-height' => '2cm']);

        $template = new OdtTemplate($this->templatePath());
        $template->setElement('tableblock', $table);
        $output = $this->outputPath('exact-ignored');
        $template->save($output);

        $contentXml = $this->entry($output, 'content.xml');
        self::assertSame(0, $this->styleCount($contentXml, 'TL01ExactIgnored_ro0', 'table-row'));
        self::assertStringNotContainsString('table:style-name="TL01ExactIgnored_ro0"', $contentXml);
        self::assertStringNotContainsString('style:row-height="2cm"', $contentXml);
    }

    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_11_table.odt';
    }

    private function outputPath(string $name): string
    {
        $path = sys_get_temp_dir() . '/table-layout-01a-' . $name . '-' . bin2hex(random_bytes(4)) . '.odt';
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

    private function styleCount(string $xml, string $name, string $family): int
    {
        $dom = $this->dom($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('style', self::STYLE_NS);

        return $xpath->query(sprintf(
            '//style:style[@style:name="%s" and @style:family="%s"]',
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
        self::assertInstanceOf(\DOMElement::class, $property);

        return $property->getAttributeNS($attributeNamespace, $attributeLocalName)
            ?: $property->getAttribute($this->qualifiedFallback($attributeNamespace, $attributeLocalName));
    }

    private function qualifiedFallback(string $namespace, string $localName): string
    {
        return match ($namespace) {
            self::STYLE_NS => 'style:' . $localName,
            'urn:oasis:names:tc:opendocument:xmlns:table:1.0' => 'table:' . $localName,
            default => $localName,
        };
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }
}
