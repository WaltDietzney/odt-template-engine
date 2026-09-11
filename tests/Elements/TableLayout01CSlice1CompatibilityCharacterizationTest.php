<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\RichTable;
use PHPUnit\Framework\TestCase;

/**
 * Protects the characterized RichTable call-order compatibility surface while
 * asserting the TABLE-LAYOUT-01C ownership correction for local definitions.
 */
final class TableLayout01CSlice1CompatibilityCharacterizationTest extends TestCase
{
    public function testSetTableStyleNameClearsCurrentLocalDefinitionState(): void
    {
        $table = (new RichTable())->setStyle([
            'style:width' => '15cm',
            'table:align' => 'center',
        ]);

        $table->setTableStyleName('NamedTableStyle');

        $requirements = iterator_to_array($table->getOwnStyleRequirements());

        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::KIND_REFERENCE, $requirements[0]->kind());
        self::assertSame('table', $requirements[0]->family());
        self::assertSame('NamedTableStyle', $requirements[0]->name());
        self::assertNull($requirements[0]->scope());
        self::assertNull($requirements[0]->documentPart());
        self::assertSame([], $requirements[0]->propertyGroups());
    }

    public function testRawSetStyleReplacesPriorNamedReferenceWithGeneratedDefinition(): void
    {
        $table = (new RichTable())->setTableStyleName('NamedTableStyle');

        $table->setStyle([
            'style:width' => '15cm',
            'table:align' => 'left',
        ]);

        $requirements = iterator_to_array($table->getOwnStyleRequirements());

        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirements[0]->kind());
        self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirements[0]->scope());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirements[0]->documentPart());
        self::assertNotSame('NamedTableStyle', $requirements[0]->name());
        self::assertSame($table->getTableStyleName(), $requirements[0]->name());
        self::assertSame([
            'style:table-properties' => [
                'style:width' => '15cm',
                'table:align' => 'left',
            ],
        ], $requirements[0]->propertyGroups());
    }

    public function testRawSetStyleEmptyClearsDefinitionAndReference(): void
    {
        $table = (new RichTable())->setStyle([
            'style:width' => '15cm',
        ]);

        $table->setStyle([]);

        self::assertNull($table->getTableStyleName());
        self::assertSame([], iterator_to_array($table->getOwnStyleRequirements()));
    }

    public function testRawSetStyleReplacesRatherThanMergesPreviousLocalProperties(): void
    {
        $table = (new RichTable())->setStyle([
            'style:width' => '15cm',
            'table:align' => 'center',
            'fo:margin-left' => '1cm',
        ]);

        $table->setStyle([
            'style:rel-width' => '60%',
        ]);

        $requirements = iterator_to_array($table->getOwnStyleRequirements());

        self::assertCount(1, $requirements);
        self::assertSame([
            'style:table-properties' => [
                'style:rel-width' => '60%',
            ],
        ], $requirements[0]->propertyGroups());
    }

    public function testElementOwnedDefinitionIsAutomaticAndContentXmlOwned(): void
    {
        $table = (new RichTable())->setStyle([
            'style:width' => '15cm',
            'table:align' => 'left',
        ]);

        $requirements = iterator_to_array($table->getOwnStyleRequirements());

        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirements[0]->kind());
        self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirements[0]->scope());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirements[0]->documentPart());
        self::assertSame('table', $requirements[0]->family());
    }
}
