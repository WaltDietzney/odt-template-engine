<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\TestCase;

/** Records the 02G retirement of static writer finalization discovery. */
final class D5GStaticFinalizationCompatibilityCharacterizationTest extends TestCase
{
    public function testStaticWriterFinalizationAndGraphicRegistryFacadesAreRetired(): void
    {
        self::assertFalse(method_exists(StyleWriter::class, 'writeAllStyles'));
        self::assertFalse(method_exists(StyleWriter::class, 'writeTextStyles'));
        self::assertFalse(method_exists(StyleWriter::class, 'writeFontFaces'));

        foreach ([
            'registerTableStyle',
            'registerTableCellStyle',
            'addFrameStyle',
            'registerImageStyle',
            'registerFillImage',
        ] as $method) {
            self::assertFalse(method_exists(StyleMapper::class, $method), $method);
        }
    }
}
