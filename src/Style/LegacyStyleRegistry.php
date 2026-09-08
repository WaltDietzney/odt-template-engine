<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Style;

/**
 * Compatibility-only process-wide registry for legacy paragraph styles.
 *
 * This registry is intentionally separate from the document-scoped
 * StyleContext. Its global lifetime is retained for existing static callers.
 */
final class LegacyStyleRegistry
{
    /** @var array<string, array<string, mixed>> */
    private static array $paragraphStyles = [];

    /** @var array<string, array<string, mixed>> */
    private static array $textStyles = [];

    /**
     * Register a legacy paragraph style using the historical first-write-wins
     * behavior.
     *
     * @param array<string, mixed> $style
     */
    public static function registerParagraphStyle(string $name, array $style): void
    {
        if (!isset(self::$paragraphStyles[$name])) {
            self::$paragraphStyles[$name] = $style;
        }
    }

    /** @return array<string, array<string, mixed>> */
    public static function paragraphStyles(): array
    {
        return self::$paragraphStyles;
    }

    /**
     * Register a legacy text style for reference-resolution compatibility.
     *
     * This remains a narrow compatibility store; it is not writer ownership.
     *
     * @param array<string, mixed> $style
     */
    public static function registerTextStyle(string $name, array $style): void
    {
        if (!isset(self::$textStyles[$name])) {
            self::$textStyles[$name] = $style;
        }
    }

    /** @return array<string, array<string, mixed>> */
    public static function textStyles(): array
    {
        return self::$textStyles;
    }

    public static function hasTextStyle(string $name): bool
    {
        return isset(self::$textStyles[$name]);
    }
}
