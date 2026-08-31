<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use PHPUnit\Framework\TestCase;

final class StructuredElementOwnershipTest extends TestCase
{
    public function testLeafElementsExposeNoOwnedElements(): void
    {
        self::assertSame([], iterator_to_array((new ImageElement($this->imagePath()))->ownedElements()));
        self::assertSame([], iterator_to_array((new CircularImageElement($this->imagePath()))->ownedElements()));
    }

    public function testParagraphExposesRenderedEmbeddedElementsAndKeepsLegacyStorage(): void
    {
        $image = new ImageElement($this->imagePath());
        $paragraph = (new Paragraph())->addElement($image);

        self::assertSame([$image], iterator_to_array($paragraph->ownedElements()));
        self::assertSame([$image], $paragraph->getEmbeddedElements());
    }

    public function testRichTextExposesItsRealElementsAndPreservesNestedHierarchy(): void
    {
        $image = new ImageElement($this->imagePath());
        $paragraph = (new Paragraph())->addElement($image);
        $directImage = new ImageElement($this->imagePath());
        $richText = (new RichText())->addParagraph($paragraph)->addElement($directImage);

        self::assertSame([$paragraph, $directImage], iterator_to_array($richText->ownedElements()));
        self::assertSame([$image], iterator_to_array($paragraph->ownedElements()));
        self::assertSame([], $richText->getEmbeddedElements());
    }

    public function testDrawTextBoxExposesItsParagraphCollectionWithoutPopulatingBaseStorage(): void
    {
        $paragraph = new Paragraph();
        $image = new ImageElement($this->imagePath());
        $box = (new DrawTextBox('OwnershipBox'))->addElement($paragraph)->addElement($image);

        self::assertSame([$paragraph, $image], iterator_to_array($box->ownedElements()));
        self::assertSame([], $box->getEmbeddedElements());
    }

    public function testListOwnershipPreservesNestedListHierarchyAndOrder(): void
    {
        $first = new Paragraph();
        $second = new Paragraph();
        $inner = (new ListElement())->addItem($second);
        $list = (new ListElement())->addItem($first)->addSubList($inner);

        self::assertSame([$first, $inner], iterator_to_array($list->ownedElements()));
        self::assertSame([$second], iterator_to_array($inner->ownedElements()));
    }

    public function testTableOwnershipExposesCellContentInRowAndCellOrder(): void
    {
        $first = new Paragraph();
        $second = new RichText();
        $third = new Paragraph();
        $table = (new RichTable())
            ->addRow([new RichTableCell($first), new RichTableCell($second)])
            ->addRow([new RichTableCell($third)]);

        $cells = iterator_to_array($table->ownedElements());
        self::assertSame(3, count($cells));
        self::assertSame([$first, $second, $third], array_map(
            static fn (RichTableCell $cell): mixed => $cell->getContent(),
            $cells
        ));
        self::assertSame([$first], iterator_to_array((new RichTableCell($first))->ownedElements()));
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}
