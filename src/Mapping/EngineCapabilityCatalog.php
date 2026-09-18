<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Deterministic Phase-E capability knowledge; independent of any template/document. */
final readonly class EngineCapabilityCatalog
{
    /** @param list<NativeActionCapability> $nativeActions
     *  @param array<string, string> $metadataPayloadKinds
     */
    private function __construct(
        private DependencyAutomationCapability $dependencyAutomation,
        private array $nativeActions,
        private array $metadataPayloadKinds
    ) {
        foreach ($nativeActions as $action) {
            if (!$action instanceof NativeActionCapability) {
                throw new \InvalidArgumentException('Native capability catalog entries must be action capabilities.');
            }
        }
    }

    public static function phaseE1(): self
    {
        return new self(
            new DependencyAutomationCapability('dependency.automation'),
            [
                new NativeActionCapability('replace-content', 'section', 'ODT_ELEMENT', 'SECTION_TARGET'),
                new NativeActionCapability('replace-text', 'bookmark', 'STRING', 'BOOKMARK_TARGET'),
                new NativeActionCapability('replace-image', 'frame', 'IMAGE_REPLACEMENT', 'FRAME_IMAGE_REPLACEMENT'),
            ],
            [
                'title' => 'STRING',
                'subject' => 'STRING',
                'description' => 'STRING',
                'keywords' => 'LIST<STRING>',
                'initial_creator' => 'STRING',
                'creator' => 'STRING',
                'language' => 'LANGUAGE',
                'creation_date' => 'DATETIME',
                'date' => 'DATETIME',
                'editing_cycles' => 'NON_NEGATIVE_INTEGER',
                'editing_duration' => 'DURATION',
                'generator' => 'STRING',
                'coverage' => 'STRING',
            ]
        );
    }

    public function dependencyAutomation(): DependencyAutomationCapability
    {
        return $this->dependencyAutomation;
    }

    /** @return list<NativeActionCapability> */
    public function nativeActions(): array
    {
        return $this->nativeActions;
    }

    public function nativeAction(string $targetKind, string $actionId): ?NativeActionCapability
    {
        foreach ($this->nativeActions as $action) {
            if ($action->targetKind() === $targetKind && $action->actionId() === $actionId) {
                return $action;
            }
        }

        return null;
    }

    public function action(string $actionId): ?NativeActionCapability
    {
        foreach ($this->nativeActions as $action) {
            if ($action->actionId() === $actionId) {
                return $action;
            }
        }

        return null;
    }

    /** @return list<string> */
    public function metadataTargets(string $group = 'metadata'): array
    {
        return $group === 'metadata' ? array_keys($this->metadataPayloadKinds) : [];
    }

    public function metadataPayloadKind(string $target, string $group = 'metadata'): ?string
    {
        return $group === 'metadata' ? ($this->metadataPayloadKinds[$target] ?? null) : null;
    }

    public function supportsDocumentTarget(string $group, string $target): bool
    {
        return in_array($target, $this->metadataTargets($group), true);
    }
}
