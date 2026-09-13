<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Template\TemplateContractCapabilities;
use PHPUnit\Framework\TestCase;

final class TemplateAuthoring01BLibreOfficeFixtureInspectionTest extends TestCase
{
    public function testWriterAuthoredFixtureProducesExpectedUnifiedContract(): void
    {
        $template = new OdtTemplate(
            dirname(__DIR__, 2)
            . '/tests/fixtures/libreoffice-reference/odt/'
            . 'TEMPLATE-AUTHORING-01B-inspection-contract.odt'
        );

        $contract = $template->inspectTemplate();

        self::assertSame(1, TemplateContract::CONTRACT_VERSION);
        self::assertSame(1, $contract->toArray()['contract_version']);

        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('inspection')
        );
        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('dependency_mapping')
        );

        $bindings = [];
        foreach ($contract->bindings() as $binding) {
            $bindings[] = [
                'name' => $binding->variableName(),
                'source_part' => $binding->provenance()->sourcePart(),
                'region_kind' => $binding->provenance()->regionKind(),
                'region_owner' => $binding->provenance()->regionOwner(),
                'carrier_kind' => $binding->provenance()->carrierKind(),
            ];
        }

        self::assertContains(
            [
                'name' => 'document_title',
                'source_part' => 'styles.xml',
                'region_kind' => 'MASTER_PAGE_CONTENT',
                'region_owner' => 'Standard',
                'carrier_kind' => 'style:header',
            ],
            $bindings
        );

        foreach (['name', 'profession', 'profile', 'company', 'role'] as $name) {
            self::assertNotEmpty(array_filter(
                $bindings,
                static fn (array $binding): bool =>
                    $binding['name'] === $name
                    && $binding['source_part'] === 'content.xml'
            ));
        }

        $nativeControls = array_values(array_filter(
            $contract->controls(),
            static fn ($control): bool =>
                $control->representation() === 'NATIVE_SECTION_DECLARATION'
        ));

        self::assertSame(
            ['IF', 'FOREACH', 'IFNOT'],
            array_map(
                static fn ($control): string => $control->kind(),
                $nativeControls
            )
        );

        foreach ($nativeControls as $control) {
            self::assertSame('RECOGNIZED', $control->supportState());
            self::assertNotNull($control->carrierNativeObjectId());
        }

        $nativeObjects = $contract->nativeObjects();

        $foreach = $this->nativeObject($nativeObjects, 'section', '#foreach:experience');
        $ifnot = $this->nativeObject($nativeObjects, 'section', '#ifnot:hidden');
        $table = $this->nativeObject($nativeObjects, 'table', 'ExperienceTable');
        $badge = $this->nativeObject($nativeObjects, 'section', 'ExperienceBadge');
        $notes = $this->nativeObject($nativeObjects, 'section', 'ProfileNotes');
        $bookmark = $this->nativeObject($nativeObjects, 'bookmark', 'ProfileBookmark');
        $frame = $this->nativeObject($nativeObjects, 'frame', 'Textrahmen 1');

        self::assertContains($foreach->id(), $ifnot->ownerIds());
        self::assertSame(
            [$foreach->id(), $ifnot->id()],
            $table->ownerIds()
        );

        self::assertContains($foreach->id(), $badge->ownerIds());
        self::assertSame([], $notes->ownerIds());
        self::assertSame([], $bookmark->ownerIds());
        self::assertSame([], $frame->ownerIds());

        $paths = array_map(
            static fn ($dependency): string => $dependency->path(),
            $contract->dependencies()
        );

        foreach ([
            'document_title',
            'name',
            'profession',
            'show_profile',
            'profile',
            'experience[]',
            'experience[].hidden',
            'experience[].company',
            'experience[].role',
        ] as $path) {
            self::assertContains($path, $paths);
        }

        self::assertSame(
            $template->inspectTemplate()->toArray(),
            $template->inspectTemplate()->toArray()
        );

        $serialized = json_encode(
            $contract->toArray(),
            JSON_THROW_ON_ERROR
        );
        self::assertStringNotContainsString('DOMDocument', $serialized);
        self::assertStringNotContainsString('DOMElement', $serialized);
        self::assertStringNotContainsString('DOMXPath', $serialized);
        self::assertStringNotContainsString('spl_object_id', $serialized);
    }

    /** @param list<object> $objects */
    private function nativeObject(array $objects, string $kind, string $name): object
    {
        foreach ($objects as $object) {
            if ($object->kind() === $kind && $object->name() === $name) {
                return $object;
            }
        }

        self::fail('Missing native object ' . $kind . ':' . $name);
    }
}
