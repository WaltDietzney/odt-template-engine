<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Template\NativeObjectDescriptor;

final readonly class ProjectedNativeObjectTarget
{
    /** @param list<ProjectedNativeAction> $actions */
    public function __construct(private NativeObjectDescriptor $nativeObject, private array $actions)
    {
    }

    public function nativeObject(): NativeObjectDescriptor
    {
        return $this->nativeObject;
    }

    /** @return list<ProjectedNativeAction> */
    public function actions(): array
    {
        return $this->actions;
    }

    public function action(string $actionId): ?ProjectedNativeAction
    {
        foreach ($this->actions as $action) {
            if ($action->capability()->actionId() === $actionId) {
                return $action;
            }
        }

        return null;
    }
}
