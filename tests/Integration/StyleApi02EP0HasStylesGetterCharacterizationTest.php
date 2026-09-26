<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use PHPUnit\Framework\TestCase;

final class StyleApi02EP0HasStylesGetterCharacterizationTest extends TestCase
{
    public function testParagraphLegacyDefinitionProjectionIsRetired(): void
    {
        $paragraph = new Paragraph('P0Paragraph', [
            'margin-top' => '0.2cm',
        ]);
        $paragraph->addText('Styled', ['bold' => true]);

        self::assertFalse(method_exists($paragraph, 'getStyleDefinitions'));
        self::assertNotEmpty(iterator_to_array($paragraph->getOwnStyleRequirements()));
    }

    public function testRichTableCellLegacyDefinitionProjectionIsRetired(): void
    {
        $cell = new RichTableCell('Cell', [
            'background' => '#abcdef',
            'padding' => '0.1cm',
        ]);

        self::assertFalse(method_exists($cell, 'getStyleDefinitions'));
        self::assertNotEmpty(iterator_to_array($cell->getOwnStyleRequirements()));
    }

    public function testDrawTextBoxLegacyDefinitionProjectionIsRetired(): void
    {
        $box = new DrawTextBox('P0Box', [
            'background-color' => '#abcdef',
        ]);

        self::assertFalse(method_exists($box, 'getStyleDefinitions'));
        self::assertNotEmpty($box->getFrameStyleRequirements());
    }

    public function testImageElementReturnsAnEmptyDefinitionEvenWhenStyled(): void
    {
        $image = new ImageElement(__DIR__ . '/../../assets/WaltDietzney.png', [
            'width' => '3cm',
        ]);

        self::assertFalse(method_exists($image, 'getStyleDefinitions'));
    }

    public function testRichTableReturnsAnEmptyInheritedDefinition(): void
    {
        $table = (new RichTable())->addRow([
            new RichTableCell('Cell', ['background' => '#abcdef']),
        ]);

        self::assertFalse(method_exists($table, 'getStyleDefinitions'));
    }

    public function testListElementReturnsAnEmptyInheritedDefinition(): void
    {
        $list = (new ListElement())->addItem(new Paragraph('P0ListItem'));

        self::assertFalse(method_exists($list, 'getStyleDefinitions'));
    }
}
