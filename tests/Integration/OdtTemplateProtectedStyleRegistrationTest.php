<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class OdtTemplateProtectedStyleRegistrationTest extends TestCase
{
    public function testGenericProtectedStyleRegistrationFacadeIsRetired(): void
    {
        self::assertFalse(method_exists(OdtTemplate::class, 'registerStyles'));
    }
}
