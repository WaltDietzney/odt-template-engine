<?php

namespace OdtTemplateEngine\Tests\Elements;

use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use PHPUnit\Framework\TestCase;

/**
 * Characterizes pre-TABLE-LAYOUT-01 behavior without approving new semantics.
 */
final class TableLayout01ACurrentBehaviorCharacterizationTest extends TestCase
{
    public function testTableStylePassesNativeGeometryPropertiesThroughAsAutomaticTableRequirement(): void
    {
        $table = (new RichTable())->setStyle([
            'style:width' => '10cm',
            'style:rel-width' => '60%',
            'table:align' => 'center',
            'fo:margin-left' => '1cm',
            'fo:margin-right' => '2cm',
        ]);

        $requirements = iterator_to_array($table->getOwnStyleRequirements());

        self::assertCount(1, $requirements);
        $requirement = $requirements[0];
        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
        self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirement->scope());
        self::assertSame('table', $requirement->family());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirement->documentPart());
        self::assertSame([
            'style:table-properties' => [
                'style:width' => '10cm',
                'style:rel-width' => '60%',
                'table:align' => 'center',
                'fo:margin-left' => '1cm',
                'fo:margin-right' => '2cm',
            ],
        ], $requirement->propertyGroups());
    }

    public function testAbsoluteTableGeometryAndAbsoluteColumnWidthsRemainIndependentRequirements(): void
    {
        $table = (new RichTable())->setStyle([
            'style:width' => '12cm',
            'table:align' => 'left',
        ]);
        $table->setColumnWidths(['4cm', '8cm']);

        $requirements = iterator_to_array($table->getOwnStyleRequirements());

        self::assertCount(3, $requirements);
        self::assertSame('table-column', $requirements[0]->family());
        self::assertSame(
            ['style:table-column-properties' => ['style:column-width' => '4cm']],
            $requirements[0]->propertyGroups()
        );
        self::assertSame('table-column', $requirements[1]->family());
        self::assertSame(
            ['style:table-column-properties' => ['style:column-width' => '8cm']],
            $requirements[1]->propertyGroups()
        );
        self::assertSame('table', $requirements[2]->family());
        self::assertSame(
            ['style:table-properties' => ['style:width' => '12cm', 'table:align' => 'left']],
            $requirements[2]->propertyGroups()
        );
    }

    public function testRelativeColumnRatiosKeepExistingWriter65535NormalizationAlongsideTableGeometry(): void
    {
        $table = (new RichTable())->setStyle([
            'style:rel-width' => '60%',
            'table:align' => 'left',
        ]);
        $table->setColumnWidthRatios([2, 1, 1]);

        $requirements = iterator_to_array($table->getOwnStyleRequirements());

        self::assertCount(4, $requirements);
        self::assertSame(
            ['style:table-column-properties' => ['style:rel-column-width' => '32766*']],
            $requirements[0]->propertyGroups()
        );
        self::assertSame(
            ['style:table-column-properties' => ['style:rel-column-width' => '16383*']],
            $requirements[1]->propertyGroups()
        );
        self::assertSame(
            ['style:table-column-properties' => ['style:rel-column-width' => '16386*']],
            $requirements[2]->propertyGroups()
        );
        self::assertSame(
            ['style:table-properties' => ['style:rel-width' => '60%', 'table:align' => 'left']],
            $requirements[3]->propertyGroups()
        );
    }

    public function testRowConveniencePathRecognizesMinimumAndExactHeightAsDistinctSemantics(): void
    {
        $table = new RichTable();
        $table->addRow(['minimum'], ['min-row-height' => '2cm']);
        $table->addRow(['exact'], ['row-height' => '2cm']);

        $requirements = iterator_to_array($table->getOwnStyleRequirements());

        self::assertCount(2, $requirements);
        self::assertSame('table-row', $requirements[0]->family());
        self::assertSame(
            ['style:table-row-properties' => ['style:min-row-height' => '2cm']],
            $requirements[0]->propertyGroups()
        );
        self::assertSame('table-row', $requirements[1]->family());
        self::assertSame(
            ['style:table-row-properties' => ['style:row-height' => '2cm']],
            $requirements[1]->propertyGroups()
        );
    }

    public function testNativeCellVerticalAlignmentPassesThroughAsCellOwnedProperty(): void
    {
        $cell = new RichTableCell('middle', ['style:vertical-align' => 'middle']);

        self::assertSame(['style:vertical-align' => 'middle'], $cell->getStyle());

        $requirements = iterator_to_array($cell->getOwnStyleRequirements());
        self::assertCount(1, $requirements);
        self::assertSame('table-cell', $requirements[0]->family());
        self::assertSame(
            ['style:table-cell-properties' => ['style:vertical-align' => 'middle']],
            $requirements[0]->propertyGroups()
        );
    }

    public function testFriendlyVerticalAlignMapsToCellOwnedNativeProperty(): void
    {
        $cell = new RichTableCell('middle', ['vertical-align' => 'middle']);

        self::assertSame(['style:vertical-align' => 'middle'], $cell->getStyle());

        $requirements = iterator_to_array($cell->getOwnStyleRequirements());
        self::assertCount(1, $requirements);
        self::assertSame('table-cell', $requirements[0]->family());
        self::assertSame(
            ['style:table-cell-properties' => ['style:vertical-align' => 'middle']],
            $requirements[0]->propertyGroups()
        );
    }
}
