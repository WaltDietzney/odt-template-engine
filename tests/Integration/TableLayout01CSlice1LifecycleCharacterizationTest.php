<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Protects the characterized table-level lifecycle while asserting the
 * TABLE-LAYOUT-01C automatic/content.xml ownership target.
 */
final class TableLayout01CSlice1LifecycleCharacterizationTest extends TestCase
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
    public function testSample11TableStyleMaterializesAsAutomaticStyleInContentXml(): void
    {
        $table = (new RichTable())->setStyle([
            'table:width' => '15cm',
            'table:align' => 'left',
            'style:rel-width' => '100%',
        ]);
        $table->addRow(['A', 'B']);
        $table->setColumnWidths(['2cm', '10cm']);

        $output = $this->saveTable($table, 'sample11-current');

        $stylesXml = $this->entry($output, 'styles.xml');
        $contentXml = $this->entry($output, 'content.xml');
        $styleName = $table->getTableStyleName();

        self::assertNotNull($styleName);
        self::assertSame(0, $this->styleCountInContainer($stylesXml, $styleName, 'table', 'styles'));
        self::assertSame(1, $this->styleCountInContainer($contentXml, $styleName, 'table', 'automatic-styles'));

        self::assertSame('15cm', $this->styleProperty(
            $contentXml,
            $styleName,
            'table',
            'table-properties',
            self::TABLE_NS,
            'width'
        ));
        self::assertSame('left', $this->styleProperty(
            $contentXml,
            $styleName,
            'table',
            'table-properties',
            self::TABLE_NS,
            'align'
        ));
        self::assertSame('100%', $this->styleProperty(
            $contentXml,
            $styleName,
            'table',
            'table-properties',
            self::STYLE_NS,
            'rel-width'
        ));
    }

    #[RunInSeparateProcess]
    public function testElementOwnedAutomaticTableDefinitionRemainsSingleAcrossRepeatedSave(): void
    {
        $table = (new RichTable())->setStyle([
            'style:width' => '15cm',
            'table:align' => 'center',
        ]);
        $table->addRow(['A', 'B']);

        $template = new OdtTemplate($this->templatePath());
        $template->setElement('tableblock', $table);

        $first = $this->outputPath('repeat-first');
        $template->save($first);
        $second = $this->outputPath('repeat-second');
        $template->save($second);

        $styleName = $table->getTableStyleName();
        self::assertNotNull($styleName);

        foreach ([$first, $second] as $output) {
            $stylesXml = $this->entry($output, 'styles.xml');
            $contentXml = $this->entry($output, 'content.xml');

            self::assertSame(0, $this->styleCountInContainer($stylesXml, $styleName, 'table', 'styles'));
            self::assertSame(1, $this->styleCountInContainer($contentXml, $styleName, 'table', 'automatic-styles'));
            self::assertSame('15cm', $this->styleProperty(
                $contentXml,
                $styleName,
                'table',
                'table-properties',
                self::STYLE_NS,
                'width'
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
    }

    #[RunInSeparateProcess]
    public function testRawAbsoluteTablePropertiesCoexistWithAbsoluteColumnRequirements(): void
    {
        $table = (new RichTable())->setStyle([
            'style:width' => '12cm',
            'table:align' => 'left',
        ]);
        $table->setColumnWidths(['4cm', '8cm']);
        $table->addRow(['A', 'B']);

        $output = $this->saveTable($table, 'absolute-columns');
        $stylesXml = $this->entry($output, 'styles.xml');
        $contentXml = $this->entry($output, 'content.xml');
        $styleName = $table->getTableStyleName();

        self::assertNotNull($styleName);
        self::assertSame('12cm', $this->styleProperty(
            $contentXml,
            $styleName,
            'table',
            'table-properties',
            self::STYLE_NS,
            'width'
        ));
        self::assertSame('4cm', $this->styleProperty(
            $contentXml,
            'co0',
            'table-column',
            'table-column-properties',
            self::STYLE_NS,
            'column-width'
        ));
        self::assertSame('8cm', $this->styleProperty(
            $contentXml,
            'co1',
            'table-column',
            'table-column-properties',
            self::STYLE_NS,
            'column-width'
        ));
    }

    #[RunInSeparateProcess]
    public function testRawRelativeTablePropertiesCoexistWithRelativeColumnRequirements(): void
    {
        $table = (new RichTable())->setStyle([
            'style:rel-width' => '60%',
            'table:align' => 'right',
        ]);
        $table->setColumnWidthRatios([2, 1, 1]);
        $table->addRow(['A', 'B', 'C']);

        $output = $this->saveTable($table, 'relative-columns');
        $stylesXml = $this->entry($output, 'styles.xml');
        $contentXml = $this->entry($output, 'content.xml');
        $styleName = $table->getTableStyleName();

        self::assertNotNull($styleName);
        self::assertSame('60%', $this->styleProperty(
            $contentXml,
            $styleName,
            'table',
            'table-properties',
            self::STYLE_NS,
            'rel-width'
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
    public function testFriendlyAbsoluteTableStyleMaterializesInContentXml(): void
    {
        $table = (new RichTable())
            ->setTableStyle([
                'width' => '15cm',
                'alignment' => 'center',
            ])
            ->addRow(['A', 'B']);

        $output = $this->saveTable($table, 'friendly-absolute');
        $stylesXml = $this->entry($output, 'styles.xml');
        $contentXml = $this->entry($output, 'content.xml');
        $styleName = $table->getTableStyleName();

        self::assertNotNull($styleName);
        self::assertSame(0, $this->styleCountInContainer($stylesXml, $styleName, 'table', 'styles'));
        self::assertSame(1, $this->styleCountInContainer($contentXml, $styleName, 'table', 'automatic-styles'));
        self::assertSame('15cm', $this->styleProperty(
            $contentXml,
            $styleName,
            'table',
            'table-properties',
            self::STYLE_NS,
            'width'
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

    #[RunInSeparateProcess]
    public function testFriendlyRelativeTableStyleMaterializesInContentXml(): void
    {
        $table = (new RichTable())
            ->setTableStyle([
                'relative-width' => '60%',
                'alignment' => 'right',
            ])
            ->setColumnWidthRatios([2, 1, 1]);
        $table->addRow(['A', 'B', 'C']);

        $output = $this->saveTable($table, 'friendly-relative');
        $stylesXml = $this->entry($output, 'styles.xml');
        $contentXml = $this->entry($output, 'content.xml');
        $styleName = $table->getTableStyleName();

        self::assertNotNull($styleName);
        self::assertSame(0, $this->styleCountInContainer($stylesXml, $styleName, 'table', 'styles'));
        self::assertSame(1, $this->styleCountInContainer($contentXml, $styleName, 'table', 'automatic-styles'));
        self::assertSame('60%', $this->styleProperty(
            $contentXml,
            $styleName,
            'table',
            'table-properties',
            self::STYLE_NS,
            'rel-width'
        ));
        self::assertSame('right', $this->styleProperty(
            $contentXml,
            $styleName,
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
        $path = sys_get_temp_dir() . '/table-layout-01c-' . $name . '-' . bin2hex(random_bytes(4)) . '.odt';
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
            ?: $property->getAttribute($this->qualifiedFallback($attributeNamespace, $attributeLocalName));
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
