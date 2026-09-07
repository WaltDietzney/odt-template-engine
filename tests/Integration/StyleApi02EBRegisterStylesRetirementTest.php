<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTableCell;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class StyleApi02EBRegisterStylesRetirementTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testParagraphNoLongerExposesLegacyRegistration(): void
    {
        $paragraph = (new Paragraph('02EB_Paragraph', [
            'margin-left' => '1cm',
        ]))->addText('Text', ['bold' => true]);
        self::assertFalse(method_exists($paragraph, 'registerStyles'));
    }

    #[RunInSeparateProcess]
    public function testRichTableCellNoLongerExposesLegacyRegistration(): void
    {
        $cell = new RichTableCell('Cell', [
            'background' => '#abcdef',
        ]);
        self::assertFalse(method_exists($cell, 'registerStyles'));
    }

    #[RunInSeparateProcess]
    public function testDrawTextBoxNoLongerExposesLegacyRegistration(): void
    {
        $box = new DrawTextBox('02EB_Box', [
            'background-color' => '#abcdef',
        ]);
        self::assertFalse(method_exists($box, 'registerStyles'));
    }

    #[RunInSeparateProcess]
    public function testImageNoLongerExposesLegacyRegistration(): void
    {
        $image = new ImageElement(__DIR__ . '/../../assets/WaltDietzney.png', [
            'width' => '3cm',
        ]);
        self::assertFalse(method_exists($image, 'registerStyles'));
    }
}
