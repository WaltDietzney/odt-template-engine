<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

interface MappingRule
{
    public function source(): ApplicationPath;
}
