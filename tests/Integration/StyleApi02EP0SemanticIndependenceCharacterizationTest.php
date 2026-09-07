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
        $paragraph = (new Paragraph('P0SemanticParagraph', [
            'margin-left' => '1cm',
        ]))->addText('Semantic text', ['bold' => true]);
        $richText = (new RichText())->addElement($paragraph);
        $template = new StyleApi02EP0InspectableTemplate($this->templatePath('template_18_ListStyles.odt'));

        $template->setElement('my_list', $richText);

        self::assertFalse(method_exists($paragraph, 'registerStyles'));
        self::assertFalse(method_exists($paragraph, 'getStyleDefinitions'));
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
        $box = new DrawTextBox('P0SemanticBox', [
            'background-color' => '#abcdef',
        ]);
        $template = new StyleApi02EP0InspectableTemplate($this->templatePath('sample_textfeld.odt'));

        $template->setElement('test1', $box);

        self::assertFalse(method_exists($box, 'registerStyles'));
        self::assertFalse(method_exists($box, 'getStyleDefinitions'));
        $families = array_map(
            static fn (StyleRequirement $requirement): string => $requirement->family(),
            array_values($template->semanticDefinitions())
        );
        self::assertContains('graphic', $families);
        self::assertStringContainsString('draw:frame', $template->contentXml());
    }

    public function testTableInsertionUsesSemanticCellRequirementsWithoutLegacyMethods(): void
    {
        $cell = new RichTableCell('Cell', [
            'background' => '#abcdef',
        ]);
        $table = (new RichTable())->addRow([$cell]);
        $template = new StyleApi02EP0InspectableTemplate($this->templatePath('template_11_table.odt'));

        $template->setElement('tableblock', $table);

        self::assertFalse(method_exists($cell, 'registerStyles'));
        self::assertFalse(method_exists($cell, 'getStyleDefinitions'));
        $families = array_map(
            static fn (StyleRequirement $requirement): string => $requirement->family(),
            array_values($template->semanticDefinitions())
        );
        self::assertContains('table-cell', $families);
        self::assertStringContainsString('table:table', $template->contentXml());
    }

    public function testBuiltInElementsNoLongerExposeLegacyMethods(): void
    {
        $paragraph = new Paragraph('P0DirectParagraph', [
            'margin-left' => '1cm',
        ]);

        self::assertFalse(method_exists($paragraph, 'registerStyles'));
        self::assertFalse(method_exists($paragraph, 'getStyleDefinitions'));
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
