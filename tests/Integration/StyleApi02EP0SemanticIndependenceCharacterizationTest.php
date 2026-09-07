<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class StyleApi02EP0SemanticIndependenceCharacterizationTest extends TestCase
{
    public function testParagraphAndTextInsertionUsesSemanticRequirementsWithoutLegacyMethods(): void
    {
        $paragraph = (new StyleApi02EP0LegacyProbeParagraph('P0SemanticParagraph', [
            'margin-left' => '1cm',
        ]))->addText('Semantic text', ['bold' => true]);
        $richText = (new RichText())->addElement($paragraph);
        $template = new StyleApi02EP0InspectableTemplate($this->templatePath('template_18_ListStyles.odt'));

        $template->setElement('my_list', $richText);

        self::assertSame(0, $paragraph->registerStylesCalls);
        self::assertSame(0, $paragraph->styleDefinitionsCalls);
        $families = array_map(
            static fn (StyleRequirement $requirement): string => $requirement->family(),
            array_values($template->semanticDefinitions())
        );
        self::assertContains('paragraph', $families);
        self::assertContains('text', $families);
        self::assertStringContainsString('fo:margin-left="1cm"', $template->stylesXml());
        self::assertStringContainsString('fo:font-weight="bold"', $template->stylesXml());
    }

    public function testGraphicInsertionUsesSemanticRequirementsWithoutLegacyMethods(): void
    {
        $box = new StyleApi02EP0LegacyProbeTextBox('P0SemanticBox', [
            'background-color' => '#abcdef',
        ]);
        $template = new StyleApi02EP0InspectableTemplate($this->templatePath('sample_textfeld.odt'));

        $template->setElement('test1', $box);

        self::assertSame(0, $box->registerStylesCalls);
        self::assertSame(0, $box->styleDefinitionsCalls);
        $families = array_map(
            static fn (StyleRequirement $requirement): string => $requirement->family(),
            array_values($template->semanticDefinitions())
        );
        self::assertContains('graphic', $families);
        self::assertStringContainsString('draw:frame', $template->contentXml());
    }

    public function testTableInsertionUsesSemanticCellRequirementsWithoutLegacyMethods(): void
    {
        $cell = new StyleApi02EP0LegacyProbeCell('Cell', [
            'background' => '#abcdef',
        ]);
        $table = (new RichTable())->addRow([$cell]);
        $template = new StyleApi02EP0InspectableTemplate($this->templatePath('template_11_table.odt'));

        $template->setElement('tableblock', $table);

        self::assertSame(0, $cell->registerStylesCalls);
        self::assertSame(0, $cell->styleDefinitionsCalls);
        $families = array_map(
            static fn (StyleRequirement $requirement): string => $requirement->family(),
            array_values($template->semanticDefinitions())
        );
        self::assertContains('table-cell', $families);
        self::assertStringContainsString('table:table', $template->contentXml());
    }

    public function testDirectPublicLegacyOverridesRemainObservable(): void
    {
        $paragraph = new StyleApi02EP0LegacyProbeParagraph('P0DirectParagraph', [
            'margin-left' => '1cm',
        ]);

        $paragraph->registerStyles();
        $paragraph->getStyleDefinitions();

        self::assertSame(1, $paragraph->registerStylesCalls);
        self::assertSame(1, $paragraph->styleDefinitionsCalls);
    }

    private function templatePath(string $name): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $name;
        self::assertFileExists($path);

        return $path;
    }
}

final class StyleApi02EP0InspectableTemplate extends OdtTemplate
{
    /** @return array<string, StyleRequirement> */
    public function semanticDefinitions(): array
    {
        return $this->documentContext()->styleContext()->semanticDefinitions();
    }

    public function stylesXml(): string
    {
        return $this->documentContext()->stylesDom()->saveXML();
    }

    public function contentXml(): string
    {
        return $this->documentContext()->contentDom()->saveXML();
    }
}

final class StyleApi02EP0LegacyProbeParagraph extends Paragraph
{
    public int $registerStylesCalls = 0;
    public int $styleDefinitionsCalls = 0;

    public function registerStyles(): void
    {
        ++$this->registerStylesCalls;
        parent::registerStyles();
    }

    public function getStyleDefinitions(): array
    {
        ++$this->styleDefinitionsCalls;

        return parent::getStyleDefinitions();
    }
}

final class StyleApi02EP0LegacyProbeTextBox extends DrawTextBox
{
    public int $registerStylesCalls = 0;
    public int $styleDefinitionsCalls = 0;

    public function registerStyles(): void
    {
        ++$this->registerStylesCalls;
        parent::registerStyles();
    }

    public function getStyleDefinitions(): array
    {
        ++$this->styleDefinitionsCalls;

        return parent::getStyleDefinitions();
    }
}

final class StyleApi02EP0LegacyProbeCell extends RichTableCell
{
    public int $registerStylesCalls = 0;
    public int $styleDefinitionsCalls = 0;

    public function registerStyles(): void
    {
        ++$this->registerStylesCalls;
        parent::registerStyles();
    }

    public function getStyleDefinitions(): array
    {
        ++$this->styleDefinitionsCalls;

        return parent::getStyleDefinitions();
    }
}
