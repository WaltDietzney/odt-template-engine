<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Document\StyleRequirement;
use PHPUnit\Framework\TestCase;

/**
 * Keeps the former table-style P0 boundary visible while asserting the
 * TABLE-LAYOUT-01C ownership correction for element-owned table definitions.
 */
final class StyleApi02FP0TableStyleCompatibilityTest extends TestCase
{
    public function testTableStyleOwnershipIsElementLocalAfterWriterBoundaryCleanup(): void
    {
        $properties = [
            'table:width' => '15cm',
            'table:align' => 'left',
            'style:rel-width' => '100%',
        ];
        $requirements = iterator_to_array(
            (new RichTable())->setStyle($properties)->getOwnStyleRequirements(),
            false
        );

        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirements[0]->kind());
        self::assertSame('table', $requirements[0]->family());
        self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirements[0]->scope());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirements[0]->documentPart());
        self::assertSame(['style:table-properties' => $properties], $requirements[0]->propertyGroups());
    }
}
