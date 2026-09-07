<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Contracts\HasStyles;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use PHPUnit\Framework\TestCase;

final class StyleApi02EP0HasStylesCompositeCharacterizationTest extends TestCase
{
    public function testDirectRichTextRegisterStylesDispatchesToHasStylesChildOverride(): void
    {
        $child = new StyleApi02EP0RegisterProbeParagraph();
        $richText = (new RichText())->addElement($child);

        self::assertInstanceOf(HasStyles::class, $child);
        self::assertSame(0, $child->registerStylesCalls);

        $richText->registerStyles();

        self::assertSame(1, $child->registerStylesCalls);
    }

    public function testDirectRichTableRegisterStylesDispatchesToHasStylesCellOverride(): void
    {
        $cell = new StyleApi02EP0RegisterProbeCell('Cell', [
            'background' => '#abcdef',
        ]);
        $table = (new RichTable())->addRow([$cell]);

        self::assertInstanceOf(HasStyles::class, $cell);
        self::assertSame(0, $cell->registerStylesCalls);

        $table->registerStyles();

        self::assertSame(1, $cell->registerStylesCalls);
        self::assertSame([], $cell->getStyleDefinitions());
    }
}

final class StyleApi02EP0RegisterProbeParagraph extends Paragraph
{
    public int $registerStylesCalls = 0;

    public function registerStyles(): void
    {
        ++$this->registerStylesCalls;
        parent::registerStyles();
    }
}

final class StyleApi02EP0RegisterProbeCell extends RichTableCell
{
    public int $registerStylesCalls = 0;

    public function registerStyles(): void
    {
        ++$this->registerStylesCalls;
        parent::registerStyles();
    }
}
