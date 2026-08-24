<?php

namespace OdtTemplateEngine\Import;

use OdtTemplateEngine\Utils\TemporaryAssetRegistry;

/**
 * Resolves image sources used by HtmlImporter without changing parser state.
 */
final class HtmlImageResolver
{
    private const MAX_REMOTE_BYTES = 5_000_000;

    public function __construct(private readonly bool $allowRemoteImages = false)
    {
    }

    public function resolve(string $source): ?string
    {
        if (preg_match('#^data:image/([a-z0-9.+-]+);base64,(.*)$#is', $source, $matches)) {
            return $this->resolveDataImage($matches[1], $matches[2]);
        }

        $scheme = strtolower((string) parse_url($source, PHP_URL_SCHEME));
        if (in_array($scheme, ['http', 'https'], true)) {
            return $this->allowRemoteImages ? $this->resolveRemoteImage($source) : null;
        }

        if (is_file($source) && is_readable($source)) {
            $path = realpath($source);
            return $path === false ? null : $path;
        }

        return null;
    }

    private function resolveDataImage(string $extension, string $encoded): ?string
    {
        $binary = base64_decode($encoded, true);
        if ($binary === false || $binary === '' || @getimagesizefromstring($binary) === false) {
            return null;
        }

        return $this->writeTemporaryAsset($binary, $extension);
    }

    private function resolveRemoteImage(string $source): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'follow_location' => 0,
            ],
            'https' => [
                'timeout' => 5,
                'follow_location' => 0,
            ],
        ]);

        $handle = @fopen($source, 'rb', false, $context);
        if ($handle === false) {
            return null;
        }

        $contents = @stream_get_contents($handle, self::MAX_REMOTE_BYTES + 1);
        fclose($handle);

        if (
            $contents === false
            || strlen($contents) > self::MAX_REMOTE_BYTES
            || @getimagesizefromstring($contents) === false
        ) {
            return null;
        }

        $extension = $this->normalizeExtension(
            (string) pathinfo((string) parse_url($source, PHP_URL_PATH), PATHINFO_EXTENSION)
        );
        return $this->writeTemporaryAsset($contents, $extension);
    }

    private function writeTemporaryAsset(string $contents, string $extension): ?string
    {
        $path = $this->temporaryPath($this->normalizeExtension($extension));
        if (file_put_contents($path, $contents) === false) {
            @unlink($path);
            return null;
        }

        TemporaryAssetRegistry::register($path);
        return $path;
    }

    private function normalizeExtension(string $extension): string
    {
        $extension = strtolower($extension);
        return in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'bmp'], true)
            ? $extension
            : 'png';
    }

    private function temporaryPath(string $extension): string
    {
        return sys_get_temp_dir() . '/odt_img_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }
}
