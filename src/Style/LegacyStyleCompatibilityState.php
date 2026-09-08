<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Style;

/**
 * @internal
 *
 * Bounded process-wide storage for explicitly legacy style compatibility.
 *
 * This state is retained only for direct StyleMapper/StyleWriter compatibility
 * and the historical assign/render graphic path until the STYLE-API-02G
 * writer-boundary cleanup can retire or further reduce it. It is not a
 * supported application style-authoring API. Normal structured insertion must
 * use document-local semantic ownership instead.
 */
final class LegacyStyleCompatibilityState
{
    /** @var array<string, array<string, mixed>> */
    private static array $textStyles = [];

    /** @var array<string, array<string, mixed>> */
    private static array $tableCellStyles = [];

    /** @var array<string, array<string, mixed>> */
    private static array $imageStyles = [];

    /** @var array<string, array{name: string, path: string, filename: string}> */
    private static array $fillImages = [];

    /** @var array<string, array<string, mixed>> */
    private static array $frameStyles = [];

    /** @var array<string, array<string, mixed>> */
    private static array $tableStyles = [];

    public static function registerTextStyle(string $name, array $style): void
    {
        if (!isset(self::$textStyles[$name])) {
            self::$textStyles[$name] = $style;
        }
    }

    public static function setTextStyle(string $name, array $style): void
    {
        self::registerTextStyle($name, $style);
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

    /** @param array<string, mixed> $style */
    public static function registerTableCellStyle(string $name, array $style): void
    {
        self::$tableCellStyles[$name] = $style;
    }

    /** @return array<string, array<string, mixed>> */
    public static function tableCellStyles(): array
    {
        return self::$tableCellStyles;
    }

    /** @param array<string, mixed> $options Already-normalized compatibility data. */
    public static function registerImageStyle(string $name, array $options): void
    {
        self::$imageStyles[$name] = $options;
    }

    /** @return array<string, array<string, mixed>> */
    public static function imageStyles(): array
    {
        return self::$imageStyles;
    }

    public static function registerFillImage(string $name, string $path): void
    {
        self::$fillImages[$name] = [
            'name' => $name,
            'path' => $path,
            'filename' => basename($path),
        ];
    }

    /** @return array<string, array{name: string, path: string, filename: string}> */
    public static function fillImages(): array
    {
        return self::$fillImages;
    }

    /** @param array<string, mixed> $properties */
    public static function addFrameStyle(string $name, array $properties): void
    {
        self::$frameStyles[$name] = isset(self::$frameStyles[$name])
            ? array_merge(self::$frameStyles[$name], $properties)
            : $properties;
    }

    /** @return array<string, array<string, mixed>> */
    public static function frameStyles(): array
    {
        return self::$frameStyles;
    }

    /** @param array<string, mixed> $properties */
    public static function registerTableStyle(string $name, array $properties): void
    {
        self::$tableStyles[$name] = $properties;
    }

    /** @return array<string, array<string, mixed>> */
    public static function tableStyles(): array
    {
        return self::$tableStyles;
    }
}
