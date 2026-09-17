<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Deterministic Phase-E capability knowledge; independent of any template/document. */
final readonly class EngineCapabilityCatalog
{
    /** @param list<NativeActionCapability> $nativeActions
     *  @param list<string> $metadataTargets
     */
    private function __construct(
        private DependencyAutomationCapability $dependencyAutomation,
        private array $nativeActions,
        private array $metadataTargets
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
                'title', 'subject', 'description', 'coverage', 'keywords', 'initial_author',
                'author', 'language', 'creation_date', 'date', 'editing_cycles',
                'editing_duration', 'generator',
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
        return $group === 'metadata' ? $this->metadataTargets : [];
    }

    public function supportsDocumentTarget(string $group, string $target): bool
    {
        return in_array($target, $this->metadataTargets($group), true);
    }
}
