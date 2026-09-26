<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Explicit application-source to named native-object action mapping. */
final readonly class NativeObjectActionMapping implements MappingRule
{
    public function __construct(
        private ApplicationPath $source,
        private string $targetKind,
        private string $targetName,
        private string $actionId
    ) {
        if ($targetKind === '' || $targetName === '' || $actionId === '') {
            throw new \InvalidArgumentException('Native action mapping target fields must not be empty.');
        }
    }

    public function source(): ApplicationPath
    {
        return $this->source;
    }

    public function targetKind(): string
    {
        return $this->targetKind;
    }

    public function targetName(): string
    {
        return $this->targetName;
    }

    public function actionId(): string
    {
        return $this->actionId;
    }
}
