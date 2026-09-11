<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TableLayout01CSlice2RowHeightTest extends TestCase
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

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

    public function testExactRowHeightProducesAutomaticRowRequirement(): void
    {
        $table = (new RichTable())
            ->setTableName('ExactHeightTable')
            ->addRow(['A'], ['row-height' => '2cm']);

        $requirements = array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements()),
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table-row'
        ));

        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirements[0]->scope());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirements[0]->documentPart());
        self::assertSame('ExactHeightTable_ro0', $requirements[0]->name());
        self::assertSame([
            'style:table-row-properties' => [
                'style:row-height' => '2cm',
            ],
        ], $requirements[0]->propertyGroups());
    }

    public function testMinimumRowHeightRemainsSupported(): void
    {
        $table = (new RichTable())
            ->setTableName('MinimumHeightTable')
            ->addRow(['A'], ['min-row-height' => '1.5cm']);

        $requirements = array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements()),
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table-row'
        ));

        self::assertCount(1, $requirements);
        self::assertSame([
            'style:table-row-properties' => [
                'style:min-row-height' => '1.5cm',
            ],
        ], $requirements[0]->propertyGroups());
    }

    public function testExactAndMinimumRowHeightCannotBeCombined(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RichTable())->addRow(['A'], [
            'row-height' => '2cm',
            'min-row-height' => '1cm',
        ]);
    }

    public function testInvalidExactRowHeightIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RichTable())->addRow(['A'], ['row-height' => 'tall']);
    }

    public function testUnsupportedRowKeysRemainIgnored(): void
    {
        $table = (new RichTable())->addRow(['A'], [
            'height' => '1cm',
            'keep-together' => 'always',
            'break-before' => 'page',
        ]);

        $requirements = array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements()),
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table-row'
        ));

        self::assertSame([], $requirements);
    }

    #[RunInSeparateProcess]
    public function testExactRowHeightMaterializesInContentXml(): void
    {
        $table = (new RichTable())
            ->setTableName('ExactHeightTable')
            ->addRow(['A'], ['row-height' => '2cm']);

        $output = $this->saveTable($table, 'exact');
        $contentXml = $this->entry($output, 'content.xml');
        $stylesXml = $this->entry($output, 'styles.xml');

        self::assertSame(1, $this->styleCountInContainer(
            $contentXml,
            'ExactHeightTable_ro0',
            'table-row',
            'automatic-styles'
        ));
        self::assertSame(0, $this->styleCountInContainer(
            $stylesXml,
            'ExactHeightTable_ro0',
            'table-row',
            'styles'
        ));
        self::assertSame('2cm', $this->styleProperty(
            $contentXml,
            'ExactHeightTable_ro0',
            'table-row',
            'table-row-properties',
            self::STYLE_NS,
            'row-height'
        ));
        self::assertStringContainsString(
            'table:style-name="ExactHeightTable_ro0"',
            $contentXml
        );
    }

    #[RunInSeparateProcess]
    public function testMinimumRowHeightStillMaterializesInContentXml(): void
    {
        $table = (new RichTable())
            ->setTableName('MinimumHeightTable')
            ->addRow(['A'], ['min-row-height' => '1.5cm']);

        $output = $this->saveTable($table, 'minimum');
        $contentXml = $this->entry($output, 'content.xml');

        self::assertSame('1.5cm', $this->styleProperty(
            $contentXml,
            'MinimumHeightTable_ro0',
            'table-row',
            'table-row-properties',
            self::STYLE_NS,
            'min-row-height'
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
        $path = sys_get_temp_dir() . '/table-layout-01c-row-' . $name . '-' . bin2hex(random_bytes(4)) . '.odt';
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
