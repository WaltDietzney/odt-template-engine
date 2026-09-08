<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\TestCase;

/**
 * Records the intentional 02G retirement of direct registry-to-writer paths.
 * The original P0 tests remain in Git history as the pre-02G evidence.
 */
final class StyleApi02FP0DirectStyleWriterCompatibilityTest extends TestCase
{
    public function testStyleMapperNoLongerExposesDirectWriterRegistryFacades(): void
    {
        foreach ([
            'registerTableCellStyle',
            'addFrameStyle',
            'registerTableStyle',
        ] as $method) {
            self::assertFalse(method_exists(StyleMapper::class, $method), $method);
        }
    }

    public function testStyleWriterNoLongerDiscoversRegistryState(): void
    {
        self::assertFalse(method_exists(StyleWriter::class, 'writeAllStyles'));
        self::assertFalse(method_exists(StyleWriter::class, 'writeTextStyles'));
        self::assertFalse(method_exists(StyleWriter::class, 'writeFontFaces'));
    }

    public function testExplicitColumnWriterRemainsAvailable(): void
    {
        self::assertTrue(method_exists(StyleWriter::class, 'writeColumnStyles'));
    }
}
