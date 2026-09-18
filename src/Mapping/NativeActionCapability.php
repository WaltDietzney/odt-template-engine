<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

final readonly class NativeActionCapability
{
    public function __construct(
        private string $actionId,
        private string $targetKind,
        private string $payloadKind,
        private string $mutationOwner
    ) {
    }

    public function actionId(): string
    {
        return $this->actionId;
    }

    public function targetKind(): string
    {
        return $this->targetKind;
    }

    public function payloadKind(): string
    {
        return $this->payloadKind;
    }

    public function mutationOwner(): string
    {
        return $this->mutationOwner;
    }
}
