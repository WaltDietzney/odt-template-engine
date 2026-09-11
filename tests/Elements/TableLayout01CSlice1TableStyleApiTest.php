<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use InvalidArgumentException;
use LogicException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\TestCase;

final class TableLayout01CSlice1TableStyleApiTest extends TestCase
{
    public function testTableStyleMapperMapsFriendlyOptionsToNativeProperties(): void
    {
        self::assertSame([
            'style:width' => '15cm',
            'table:align' => 'center',
        ], StyleMapper::mapTableStyleOptions([
            'width' => '15cm',
            'alignment' => 'center',
        ]));

        self::assertSame([
            'style:rel-width' => '60%',
            'table:align' => 'right',
        ], StyleMapper::mapTableStyleOptions([
            'relative-width' => '60%',
            'alignment' => 'right',
        ]));
    }

    public function testMasterTableStyleMapsFriendlyOptionsAndCreatesLocalDefinition(): void
    {
        $table = (new RichTable())->setTableStyle([
            'width' => '15cm',
            'alignment' => 'CENTER',
        ]);

        $requirement = $this->tableRequirement($table);

        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
        self::assertSame([
            'style:table-properties' => [
                'style:width' => '15cm',
                'table:align' => 'center',
            ],
        ], $requirement->propertyGroups());
    }

    public function testMasterTableStyleExplicitlyReplacesNamedReferenceMode(): void
    {
        $table = (new RichTable())
            ->setTableStyleName('NamedTableStyle')
            ->setTableStyle([
                'width' => '15cm',
            ]);

        $requirement = $this->tableRequirement($table);

        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
        self::assertNotSame('NamedTableStyle', $requirement->name());
        self::assertSame([
            'style:table-properties' => [
                'style:width' => '15cm',
            ],
        ], $requirement->propertyGroups());
    }

    public function testConvenienceMutationRejectsNamedReferenceMode(): void
    {
        $table = (new RichTable())->setTableStyleName('NamedTableStyle');

        $this->expectException(LogicException::class);
        $table->setTableWidth('15cm');
    }

    public function testConvenienceMethodsShareOneStateAndMergeIndependentConcerns(): void
    {
        $table = (new RichTable())
            ->setTableWidth('15cm')
            ->setTableAlignment('center');

        self::assertSame([
            'style:table-properties' => [
                'style:width' => '15cm',
                'table:align' => 'center',
            ],
        ], $this->tableRequirement($table)->propertyGroups());
    }

    public function testSequentialWidthConvenienceUsesLastWidthModeWins(): void
    {
        $table = (new RichTable())
            ->setTableRelativeWidth('60%')
            ->setTableWidth('15cm');

        self::assertSame([
            'style:table-properties' => [
                'style:width' => '15cm',
            ],
        ], $this->tableRequirement($table)->propertyGroups());

        $table->setTableRelativeWidth('70%');

        self::assertSame([
            'style:table-properties' => [
                'style:rel-width' => '70%',
            ],
        ], $this->tableRequirement($table)->propertyGroups());
    }

    public function testMasterCallReplacesCompleteLocalState(): void
    {
        $table = (new RichTable())
            ->setTableStyle([
                'width' => '15cm',
                'alignment' => 'center',
            ])
            ->setTableStyle([
                'relative-width' => '60%',
            ]);

        self::assertSame([
            'style:table-properties' => [
                'style:rel-width' => '60%',
            ],
        ], $this->tableRequirement($table)->propertyGroups());
    }

    public function testRawStyleThenConveniencePreservesUnrelatedNativeProperties(): void
    {
        $table = (new RichTable())->setStyle([
            'fo:margin-left' => '1cm',
            'style:rel-width' => '60%',
            'table:align' => 'left',
        ]);

        $table
            ->setTableWidth('15cm')
            ->setTableAlignment('center');

        self::assertSame([
            'style:table-properties' => [
                'fo:margin-left' => '1cm',
                'table:align' => 'center',
                'style:width' => '15cm',
            ],
        ], $this->tableRequirement($table)->propertyGroups());
    }

    public function testEmptyMasterStyleClearsDefinitionAndReference(): void
    {
        $table = (new RichTable())
            ->setTableStyleName('NamedTableStyle')
            ->setTableStyle([]);

        self::assertNull($table->getTableStyleName());
        self::assertSame([], iterator_to_array($table->getOwnStyleRequirements()));
    }

    public function testFriendlyMasterRejectsCompetingWidthModes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RichTable())->setTableStyle([
            'width' => '15cm',
            'relative-width' => '60%',
        ]);
    }

    public function testFriendlyApiRejectsInvalidAlignmentAndRelativeWidth(): void
    {
        try {
            (new RichTable())->setTableAlignment('justify');
            self::fail('Invalid table alignment was accepted.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        (new RichTable())->setTableRelativeWidth('60');
    }

    public function testFriendlyMasterAndConvenienceFormsProduceEquivalentNativeState(): void
    {
        $master = (new RichTable())->setTableStyle([
            'width' => '15cm',
            'alignment' => 'center',
        ]);

        $convenience = (new RichTable())
            ->setTableWidth('15cm')
            ->setTableAlignment('center');

        self::assertSame(
            $this->tableRequirement($master)->propertyGroups(),
            $this->tableRequirement($convenience)->propertyGroups()
        );
        self::assertSame($master->getTableStyleName(), $convenience->getTableStyleName());
    }

    public function testAllConvenienceMethodsRejectNamedReferenceMode(): void
    {
        foreach ([
            static fn (RichTable $table): RichTable => $table->setTableWidth('15cm'),
            static fn (RichTable $table): RichTable => $table->setTableRelativeWidth('60%'),
            static fn (RichTable $table): RichTable => $table->setTableAlignment('center'),
        ] as $mutation) {
            $table = (new RichTable())->setTableStyleName('NamedTableStyle');

            try {
                $mutation($table);
                self::fail('Convenience mutation was accepted while named reference mode was active.');
            } catch (LogicException) {
                self::assertSame('NamedTableStyle', $table->getTableStyleName());
            }
        }
    }

    public function testRawMasterThenFriendlyMasterReplacesCompleteLocalState(): void
    {
        $table = (new RichTable())
            ->setStyle([
                'fo:margin-left' => '1cm',
                'table:align' => 'left',
            ])
            ->setTableStyle([
                'width' => '15cm',
                'alignment' => 'center',
            ]);

        self::assertSame([
            'style:table-properties' => [
                'style:width' => '15cm',
                'table:align' => 'center',
            ],
        ], $this->tableRequirement($table)->propertyGroups());
    }

    public function testFriendlyMasterThenRawMasterReplacesCompleteLocalState(): void
    {
        $table = (new RichTable())
            ->setTableStyle([
                'width' => '15cm',
                'alignment' => 'center',
            ])
            ->setStyle([
                'style:rel-width' => '60%',
            ]);

        self::assertSame([
            'style:table-properties' => [
                'style:rel-width' => '60%',
            ],
        ], $this->tableRequirement($table)->propertyGroups());
    }

    public function testFriendlyLocalDefinitionThenNamedReferenceClearsLocalState(): void
    {
        $table = (new RichTable())
            ->setTableStyle([
                'width' => '15cm',
                'alignment' => 'center',
            ])
            ->setTableStyleName('NamedTableStyle');

        $requirements = iterator_to_array($table->getOwnStyleRequirements());

        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::KIND_REFERENCE, $requirements[0]->kind());
        self::assertSame('NamedTableStyle', $requirements[0]->name());
        self::assertSame([], $requirements[0]->propertyGroups());
    }

    public function testFriendlyApiRejectsInvalidAbsoluteWidth(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RichTable())->setTableWidth('wide');
    }

    public function testRelativeFriendlyMasterAndConvenienceFormsProduceEquivalentNativeState(): void
    {
        $master = (new RichTable())->setTableStyle([
            'relative-width' => '60%',
            'alignment' => 'right',
        ]);

        $convenience = (new RichTable())
            ->setTableRelativeWidth('60%')
            ->setTableAlignment('right');

        self::assertSame(
            $this->tableRequirement($master)->propertyGroups(),
            $this->tableRequirement($convenience)->propertyGroups()
        );
        self::assertSame($master->getTableStyleName(), $convenience->getTableStyleName());
    }

    private function tableRequirement(RichTable $table): StyleRequirement
    {
        $requirements = array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements()),
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table'
        ));

        self::assertCount(1, $requirements);

        return $requirements[0];
    }
}
