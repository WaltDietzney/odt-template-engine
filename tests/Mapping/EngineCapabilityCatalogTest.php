<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Mapping;

use OdtTemplateEngine\Mapping\EngineCapabilityCatalog;
use PHPUnit\Framework\TestCase;

final class EngineCapabilityCatalogTest extends TestCase
{
    public function testPhaseE1CatalogExposesOnlyApprovedNativeActionsAndMetadataTargets(): void
    {
        $catalog = EngineCapabilityCatalog::phaseE1();

        self::assertSame('dependency.automation', $catalog->dependencyAutomation()->id());
        self::assertSame([
            'section:replace-content:ODT_ELEMENT:SECTION_TARGET',
            'bookmark:replace-text:STRING:BOOKMARK_TARGET',
            'frame:replace-image:IMAGE_REPLACEMENT:FRAME_IMAGE_REPLACEMENT',
        ], array_map(
            static fn ($action): string => implode(':', [
                $action->targetKind(), $action->actionId(), $action->payloadKind(), $action->mutationOwner(),
            ]),
            $catalog->nativeActions()
        ));
        self::assertSame([
            'title', 'subject', 'description', 'coverage', 'keywords', 'initial_author',
            'author', 'language', 'creation_date', 'date', 'editing_cycles',
            'editing_duration', 'generator',
        ], $catalog->metadataTargets());
        self::assertFalse($catalog->supportsDocumentTarget('metadata', 'unknown_field'));
        self::assertNull($catalog->action('populate'));
        self::assertNull($catalog->action('instantiate'));
        self::assertNull($catalog->action('instantiate-many'));
    }
}
