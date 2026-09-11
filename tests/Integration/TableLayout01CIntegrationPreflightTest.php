<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TableLayout01CIntegrationPreflightTest extends TestCase
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TABLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';

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
    public function testAbsoluteTableLayoutFeaturesMaterializeTogether(): void
    {
        $topCell = new RichTableCell('Top', [
            'vertical-align' => 'top',
            'text-align' => 'right',
        ]);
        $middleCell = new RichTableCell('Middle', [
            'vertical-align' => 'middle',
        ]);

        $table = (new RichTable())
            ->setTableName('AbsoluteLayoutTable')
            ->setTableStyle([
                'width' => '15cm',
                'alignment' => 'center',
            ])
            ->addRow([$topCell, $middleCell], ['row-height' => '2cm'])
            ->addRow(['A', 'B'], ['min-row-height' => '1cm']);
        $table->setColumnWidths(['5cm', '10cm']);

        $output = $this->saveTable($table, 'absolute');
        $contentXml = $this->entry($output, 'content.xml');
        $stylesXml = $this->entry($output, 'styles.xml');

        $tableStyleName = $table->getTableStyleName();
        self::assertNotNull($tableStyleName);

        self::assertSame(1, $this->styleCountInContainer(
            $contentXml,
            $tableStyleName,
            'table',
            'automatic-styles'
        ));
        self::assertSame(0, $this->styleCountInContainer(
            $stylesXml,
            $tableStyleName,
            'table',
            'styles'
        ));
        self::assertSame('15cm', $this->styleProperty(
            $contentXml,
            $tableStyleName,
            'table',
            'table-properties',
            self::STYLE_NS,
            'width'
        ));
        self::assertSame('center', $this->styleProperty(
            $contentXml,
            $tableStyleName,
            'table',
            'table-properties',
            self::TABLE_NS,
            'align'
        ));

        self::assertSame('5cm', $this->styleProperty(
            $contentXml,
            'co0',
            'table-column',
            'table-column-properties',
            self::STYLE_NS,
            'column-width'
        ));
        self::assertSame('10cm', $this->styleProperty(
            $contentXml,
            'co1',
            'table-column',
            'table-column-properties',
            self::STYLE_NS,
            'column-width'
        ));

        self::assertSame('2cm', $this->styleProperty(
            $contentXml,
            'AbsoluteLayoutTable_ro0',
            'table-row',
            'table-row-properties',
            self::STYLE_NS,
            'row-height'
        ));
        self::assertSame('1cm', $this->styleProperty(
            $contentXml,
            'AbsoluteLayoutTable_ro1',
            'table-row',
            'table-row-properties',
            self::STYLE_NS,
            'min-row-height'
        ));

        self::assertSame('top', $this->styleProperty(
            $contentXml,
            $topCell->getStyleName(),
            'table-cell',
            'table-cell-properties',
            self::STYLE_NS,
            'vertical-align'
        ));
        self::assertSame('middle', $this->styleProperty(
            $contentXml,
            $middleCell->getStyleName(),
            'table-cell',
            'table-cell-properties',
            self::STYLE_NS,
            'vertical-align'
        ));
    }

    #[RunInSeparateProcess]
    public function testRelativeTableLayoutPreservesWriterRatioNormalization(): void
    {
        $table = (new RichTable())
            ->setTableName('RelativeLayoutTable')
            ->setTableStyle([
                'relative-width' => '60%',
                'alignment' => 'right',
            ])
            ->addRow([
                new RichTableCell('A', ['vertical-align' => 'bottom']),
                'B',
                'C',
            ]);
        $table->setColumnWidthRatios([2, 1, 1]);

        $output = $this->saveTable($table, 'relative');
        $contentXml = $this->entry($output, 'content.xml');

        $tableStyleName = $table->getTableStyleName();
        self::assertNotNull($tableStyleName);

        self::assertSame('60%', $this->styleProperty(
            $contentXml,
            $tableStyleName,
            'table',
            'table-properties',
            self::STYLE_NS,
            'rel-width'
        ));
        self::assertSame('right', $this->styleProperty(
            $contentXml,
            $tableStyleName,
            'table',
            'table-properties',
            self::TABLE_NS,
            'align'
        ));

        self::assertSame('32766*', $this->styleProperty(
            $contentXml,
            'co0',
            'table-column',
            'table-column-properties',
            self::STYLE_NS,
            'rel-column-width'
        ));
        self::assertSame('16383*', $this->styleProperty(
            $contentXml,
            'co1',
            'table-column',
            'table-column-properties',
            self::STYLE_NS,
            'rel-column-width'
        ));
        self::assertSame('16386*', $this->styleProperty(
            $contentXml,
            'co2',
            'table-column',
            'table-column-properties',
            self::STYLE_NS,
            'rel-column-width'
        ));
    }

    #[RunInSeparateProcess]
    public function testMixedRawAndFriendlyTableStyleKeepsOneAutomaticDefinition(): void
    {
        $table = (new RichTable())->setStyle([
            'fo:margin-left' => '1cm',
            'table:align' => 'left',
            'style:rel-width' => '60%',
        ]);
        $table
            ->setTableWidth('12cm')
            ->setTableAlignment('center')
            ->addRow(['A']);

        $output = $this->saveTable($table, 'mixed');
        $contentXml = $this->entry($output, 'content.xml');
        $stylesXml = $this->entry($output, 'styles.xml');

        $styleName = $table->getTableStyleName();
        self::assertNotNull($styleName);

        self::assertSame(1, $this->styleCountInContainer(
            $contentXml,
            $styleName,
            'table',
            'automatic-styles'
        ));
        self::assertSame(0, $this->styleCountInContainer(
            $stylesXml,
            $styleName,
            'table',
            'styles'
        ));
        self::assertSame('12cm', $this->styleProperty(
            $contentXml,
            $styleName,
            'table',
            'table-properties',
            self::STYLE_NS,
            'width'
        ));
        self::assertSame('', $this->styleProperty(
            $contentXml,
            $styleName,
            'table',
            'table-properties',
            self::STYLE_NS,
            'rel-width'
        ));
        self::assertSame('center', $this->styleProperty(
            $contentXml,
            $styleName,
            'table',
            'table-properties',
            self::TABLE_NS,
            'align'
        ));
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
        $path = sys_get_temp_dir()
            . '/table-layout-01c-preflight-'
            . $name
            . '-'
            . bin2hex(random_bytes(4))
            . '.odt';
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
        $xpath->registerNamespace(
            'office',
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0'
        );
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
            ?: $property->getAttribute($this->qualifiedFallback(
                $attributeNamespace,
                $attributeLocalName
            ));
    }

    private function qualifiedFallback(string $namespace, string $localName): string
    {
        return match ($namespace) {
            self::STYLE_NS => 'style:' . $localName,
            self::TABLE_NS => 'table:' . $localName,
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
