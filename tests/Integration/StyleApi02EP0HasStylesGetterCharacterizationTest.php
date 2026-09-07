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
    public function testParagraphReturnsInlineAndParagraphLegacyDefinitions(): void
    {
        $paragraph = new Paragraph('P0Paragraph', [
            'margin-top' => '0.2cm',
        ]);
        $paragraph->addText('Styled', ['bold' => true]);

        $definitions = $paragraph->getStyleDefinitions();

        self::assertArrayHasKey('P0Paragraph', $definitions);
        self::assertSame(
            ['fo:margin-top' => '0.2cm'],
            $definitions['P0Paragraph']
        );
        self::assertCount(2, $definitions);
        self::assertSame($definitions, $paragraph->getStyleDefinitions());
    }

    public function testRichTableCellReturnsItsMappedCellDefinition(): void
    {
        $cell = new RichTableCell('Cell', [
            'background' => '#abcdef',
            'padding' => '0.1cm',
        ]);

        $definitions = $cell->getStyleDefinitions();

        self::assertArrayHasKey($cell->getStyleName(), $definitions);
        self::assertSame($cell->getStyle(), $definitions[$cell->getStyleName()]);
        self::assertNotEmpty($definitions[$cell->getStyleName()]);
        self::assertSame($definitions, $cell->getStyleDefinitions());
    }

    public function testDrawTextBoxReturnsItsFrameDefinition(): void
    {
        $box = new DrawTextBox('P0Box', [
            'background-color' => '#abcdef',
        ]);

        $definitions = $box->getStyleDefinitions();

        self::assertCount(1, $definitions);
        $name = array_key_first($definitions);
        self::assertIsString($name);
        self::assertSame(
            $box->getFrameStyleRequirements()[$name],
            $definitions[$name]
        );
        self::assertSame($definitions, $box->getStyleDefinitions());
    }

    public function testImageElementReturnsAnEmptyDefinitionEvenWhenStyled(): void
    {
        $image = new ImageElement(__DIR__ . '/../../assets/WaltDietzney.png', [
            'width' => '3cm',
        ]);

        self::assertSame([], $image->getStyleDefinitions());
    }

    public function testRichTableReturnsAnEmptyInheritedDefinition(): void
    {
        $table = (new RichTable())->addRow([
            new RichTableCell('Cell', ['background' => '#abcdef']),
        ]);

        self::assertSame([], $table->getStyleDefinitions());
    }

    public function testListElementReturnsAnEmptyInheritedDefinition(): void
    {
        $list = (new ListElement())->addItem(new Paragraph('P0ListItem'));

        self::assertSame([], $list->getStyleDefinitions());
    }
}
