<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class StyleApi02EBRegisterStylesRetirementTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testParagraphRegisterStylesNoLongerMutatesLegacyTextOrParagraphRegistries(): void
    {
        $paragraph = (new Paragraph('02EB_Paragraph', [
            'margin-left' => '1cm',
        ]))->addText('Text', ['bold' => true]);
        $paragraphStylesBefore = StyleMapper::getParagraphStyles();
        $textStylesBefore = StyleMapper::getTextStyles();

        $paragraph->registerStyles();

        self::assertSame($paragraphStylesBefore, StyleMapper::getParagraphStyles());
        self::assertSame($textStylesBefore, StyleMapper::getTextStyles());
    }

    #[RunInSeparateProcess]
    public function testRichTableCellRegisterStylesDoesNotRepeatLegacyCellRegistration(): void
    {
        $cell = new RichTableCell('Cell', [
            'background' => '#abcdef',
        ]);
        $registeredBefore = StyleMapper::getRegisteredTableCellStyles();

        $cell->registerStyles();

        self::assertSame($registeredBefore, StyleMapper::getRegisteredTableCellStyles());
    }

    #[RunInSeparateProcess]
    public function testDrawTextBoxRegisterStylesDoesNotMutateLegacyFrameRegistry(): void
    {
        $box = new DrawTextBox('02EB_Box', [
            'background-color' => '#abcdef',
        ]);
        $frameStylesBefore = StyleMapper::$frameStyles;

        $box->registerStyles();

        self::assertSame($frameStylesBefore, StyleMapper::$frameStyles);
    }

    #[RunInSeparateProcess]
    public function testImageRegisterStylesDoesNotReapplyMappedOptions(): void
    {
        $image = new ImageElement(__DIR__ . '/../../assets/WaltDietzney.png', [
            'width' => '3cm',
        ]);
        $optionsBefore = $image->getImageOptions();

        $image->registerStyles();

        self::assertSame($optionsBefore, $image->getImageOptions());
    }
}
