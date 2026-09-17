<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

enum Applicability: string
{
    case APPLICABLE = 'APPLICABLE';
    case NOT_APPLICABLE = 'NOT_APPLICABLE';
    case UNKNOWN = 'UNKNOWN';
}
