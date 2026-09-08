<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementCollector;
use OdtTemplateEngine\Import\HtmlImporter;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleApi02FADocumentOwnershipTest extends TestCase
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
    public function testRichTableOwnsGeneratedTableDefinitionWithoutGlobalRegistration(): void
    {
        $properties = [
            'table:width' => '15cm',
            'table:align' => 'left',
            'style:rel-width' => '100%',
        ];
        $table = (new RichTable())->setStyle($properties)->addRow(['A']);
        $requirements = array_values(iterator_to_array($table->getOwnStyleRequirements(), false));

        self::assertCount(1, $requirements);
        self::assertSame('table', $requirements[0]->family());
        self::assertSame('definition', $requirements[0]->kind());
        self::assertSame('common', $requirements[0]->scope());
        self::assertSame(StyleRequirement::PART_STYLES, $requirements[0]->documentPart());
        self::assertSame(
            ['style:table-properties' => $properties],
            $requirements[0]->propertyGroups()
        );
        self::assertArrayNotHasKey($requirements[0]->name(), StyleMapper::getRegisteredTableStyles());

        $template = new OdtTemplate($this->templatePath('template_11_table.odt'));
        $template->setElement('tableblock', $table);
        $output = $this->outputPath('owned-table');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        $name = $requirements[0]->name();
        self::assertStringContainsString('table:style-name="' . $name . '"', $content);
        self::assertStringContainsString('style:name="' . $name . '"', $styles);
        self::assertStringContainsString('style:family="table"', $styles);
        self::assertStringContainsString('table:width="15cm"', $styles);
        self::assertStringContainsString('table:align="left"', $styles);
        self::assertStringContainsString('style:rel-width="100%"', $styles);
    }

    #[RunInSeparateProcess]
    public function testTableStyleNameIsReferenceOnlyWithoutOwnedDefinition(): void
    {
        $table = (new RichTable())->setTableStyleName('AuthoredTable')->addRow(['A']);
        $requirements = array_values(iterator_to_array($table->getOwnStyleRequirements(), false));

        self::assertCount(1, $requirements);
        self::assertSame('reference', $requirements[0]->kind());
        self::assertSame('AuthoredTable', $requirements[0]->name());
        self::assertSame([], $requirements[0]->propertyGroups());
    }

    #[RunInSeparateProcess]
    public function testRichTableCellOwnsCellDefinitionWithoutGlobalRegistration(): void
    {
        $before = StyleMapper::getRegisteredTableCellStyles();
        $cell = new RichTableCell('Cell', ['background' => '#ddeeff', 'padding' => '0.2cm']);

        self::assertSame($before, StyleMapper::getRegisteredTableCellStyles());
        self::assertCount(1, iterator_to_array($cell->getOwnStyleRequirements(), false));
    }

    #[RunInSeparateProcess]
    public function testHtmlImporterUsesSemanticParagraphAndTextOwnership(): void
    {
        $beforeText = StyleMapper::getTextStyles();
        $beforeParagraph = StyleMapper::getParagraphStyles();

        $richText = HtmlImporter::fromHtml(
            '<p style="margin-top: 0.2cm"><strong>Imported</strong></p>'
        );
        $requirements = iterator_to_array(
            (new StyleRequirementCollector())->collectSemantic($richText),
            false
        );

        self::assertNotEmpty(array_filter(
            $requirements,
            static fn ($requirement): bool => $requirement->family() === 'paragraph'
        ));
        self::assertNotEmpty(array_filter(
            $requirements,
            static fn ($requirement): bool => $requirement->family() === 'text'
        ));
        self::assertSame($beforeText, StyleMapper::getTextStyles());
        self::assertSame($beforeParagraph, StyleMapper::getParagraphStyles());
    }

    #[RunInSeparateProcess]
    public function testParagraphStyledTextDoesNotRegisterGlobally(): void
    {
        $before = StyleMapper::getTextStyles();
        $paragraph = (new Paragraph())->addText('Local', ['bold' => true]);

        self::assertNotEmpty(iterator_to_array($paragraph->getOwnStyleRequirements(), false));
        self::assertSame($before, StyleMapper::getTextStyles());
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function outputPath(string $name): string
    {
        $path = sys_get_temp_dir() . '/odt-style-api-02f-a-' . $name . '-' . bin2hex(random_bytes(5)) . '.odt';
        $this->outputs[] = $path;
        return $path;
    }

    private function entry(string $path, string $name): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            $value = $zip->getFromName($name);
            self::assertIsString($value);
            return $value;
        } finally {
            $zip->close();
        }
    }
}
