<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementCollector;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class RichTableCellSemanticOwnershipTest extends TestCase
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

    public function testStyledCellProducesAutomaticContentSemanticDefinition(): void
    {
        $cell = new RichTableCell('Cell', [
            'background' => '#ddeeff',
            'border' => '0.1pt solid #999999',
            'padding' => '0.2cm',
        ]);
        $requirements = iterator_to_array($cell->getOwnStyleRequirements(), false);

        self::assertCount(1, $requirements);
        $requirement = $requirements[0];
        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
        self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirement->scope());
        self::assertSame('table-cell', $requirement->family());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirement->documentPart());
        self::assertSame($cell->getStyleName(), $requirement->name());
        self::assertSame('Default', $requirement->parentStyleName());
        self::assertSame(['style:table-cell-properties' => $cell->getStyle()], $requirement->propertyGroups());
    }

    public function testEmptyCellStyleProducesNoSemanticDefinition(): void
    {
        self::assertSame([], iterator_to_array((new RichTableCell('Cell'))->getOwnStyleRequirements(), false));
    }

    public function testCellRequirementContainsOnlyCellProperties(): void
    {
        $cell = new RichTableCell('Cell', [
            'background' => '#ddeeff',
            'padding' => '0.1cm',
            'text-align' => 'center',
            'bold' => true,
            'color' => '#112233',
        ]);
        $groups = iterator_to_array($cell->getOwnStyleRequirements(), false)[0]->propertyGroups();

        self::assertSame(['style:table-cell-properties'], array_keys($groups));
        self::assertArrayHasKey('fo:background-color', $groups['style:table-cell-properties']);
        self::assertArrayNotHasKey('fo:text-align', $groups['style:table-cell-properties']);
        self::assertArrayNotHasKey('fo:font-weight', $groups['style:table-cell-properties']);
        self::assertArrayNotHasKey('fo:color', $groups['style:table-cell-properties']);
        self::assertNotEmpty(iterator_to_array(
            iterator_to_array($cell->ownedElements(), false)[0]->getOwnStyleRequirements(),
            false
        ));
    }

    public function testTableCollectsCellRequirementTransitively(): void
    {
        $cell = new RichTableCell('Cell', ['background' => '#ddeeff']);
        $requirements = iterator_to_array((new StyleRequirementCollector())->collectSemantic(
            (new RichTable())->addRow([$cell])
        ), false);

        self::assertCount(1, array_filter(
            $requirements,
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table-cell'
        ));
    }

    #[RunInSeparateProcess]
    public function testNormalSetElementUsesOneAutomaticCellDefinition(): void
    {
        $cell = new RichTableCell('Cell', ['background' => '#ddeeff']);
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', (new RichTable())->addRow([$cell]));
        $output = $this->outputPath('semantic-cell');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        self::assertSame(1, $this->styleCount($content, $cell->getStyleName(), 'table-cell'));
        self::assertSame(0, $this->styleCount($styles, $cell->getStyleName(), 'table-cell'));
        self::assertStringContainsString('table:style-name="' . $cell->getStyleName() . '"', $content);
    }

    public function testDirectTableDomRenderingStillProvidesCompatibilityCellStyle(): void
    {
        $dom = $this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><office:automatic-styles/></office:document-content>');
        $cell = new RichTableCell('Cell', ['background' => '#ddeeff']);
        $table = (new RichTable())->addRow([$cell]);
        $table->toDomNode($dom);

        self::assertSame(1, $this->styleCount($dom->saveXML() ?: '', $cell->getStyleName(), 'table-cell'));
    }

    #[RunInSeparateProcess]
    public function testExplicitStaticCellRegistrationRemainsCommonCompatibility(): void
    {
        $name = 'ExplicitCell_' . bin2hex(random_bytes(3));
        StyleMapper::registerTableCellStyle($name, ['fo:background-color' => '#abcdef']);
        $dom = $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><office:styles/></office:document-styles>');
        StyleWriter::writeAllStyles($dom);

        self::assertSame(1, $this->styleCount($dom->saveXML() ?: '', $name, 'table-cell'));
    }

    #[RunInSeparateProcess]
    public function testRepeatedNormalSaveDoesNotDuplicateSemanticCellDefinition(): void
    {
        $cell = new RichTableCell('Cell', ['background' => '#ddeeff']);
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', (new RichTable())->addRow([$cell]));
        $first = $this->outputPath('semantic-cell-first');
        $second = $this->outputPath('semantic-cell-second');
        $template->save($first);
        $template->save($second);

        self::assertSame(1, $this->styleCount($this->entry($second, 'content.xml'), $cell->getStyleName(), 'table-cell'));
        self::assertSame(0, $this->styleCount($this->entry($second, 'styles.xml'), $cell->getStyleName(), 'table-cell'));
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function outputPath(string $name): string
    {
        $path = sys_get_temp_dir() . '/sr07c-' . $name . '-' . bin2hex(random_bytes(4)) . '.odt';
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

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }

    private function styleCount(string $xml, string $name, string $family): int
    {
        $dom = $this->dom($xml);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        return $xpath->query(sprintf(
            '//style:style[@style:name="%s" and @style:family="%s"]',
            $name,
            $family
        ))->length;
    }
}
