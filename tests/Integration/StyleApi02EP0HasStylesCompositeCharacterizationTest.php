<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use PHPUnit\Framework\TestCase;

final class StyleApi02EP0HasStylesCompositeCharacterizationTest extends TestCase
{
    public function testBuiltInRichTextNoLongerExposesLegacyRegistration(): void
    {
        $child = new StyleApi02EP0RegisterProbeParagraph();
        $richText = (new RichText())->addElement($child);

        self::assertFalse(method_exists($richText, 'registerStyles'));
        self::assertFalse(method_exists($child, 'registerStyles'));
    }

    public function testBuiltInRichTableNoLongerExposesLegacyRegistration(): void
    {
        $cell = new StyleApi02EP0RegisterProbeCell('Cell', [
            'background' => '#abcdef',
        ]);
        $table = (new RichTable())->addRow([$cell]);

        self::assertFalse(method_exists($table, 'registerStyles'));
        self::assertFalse(method_exists($cell, 'registerStyles'));
    }
}

final class StyleApi02EP0RegisterProbeParagraph extends Paragraph
{
}

final class StyleApi02EP0RegisterProbeCell extends RichTableCell
{
}
