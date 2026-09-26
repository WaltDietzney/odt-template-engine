<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/**
 * @internal E2-C-only typed view of an IMAGE_REPLACEMENT value.
 *
 * This is an implementation projection for concrete preflight, not the
 * application-facing or future Phase-E replacement payload API.
 */
final readonly class ImageReplacementPreflightPayload
{
    /**
     * @param array<mixed> $options
     * @param list<string> $unsupportedFields
     */
    private function __construct(
        private string $sourcePath,
        private array $options,
        private array $unsupportedFields,
        private bool $optionsWereArray
    ) {
    }

    public static function fromApplicationValue(mixed $value): ?self
    {
        if (!is_array($value) || !isset($value['source']) || !is_string($value['source'])) {
            return null;
        }

        $options = array_key_exists('options', $value) ? $value['options'] : [];
        $unknownFields = array_diff(array_keys($value), ['source', 'options']);

        return new self(
            $value['source'],
            is_array($options) ? $options : [],
            array_map('strval', array_values($unknownFields)),
            is_array($options)
        );
    }

    public function sourcePath(): string
    {
        return $this->sourcePath;
    }

    /** @return array<mixed> */
    public function options(): array
    {
        return $this->options;
    }

    /** @return list<string> */
    public function unsupportedFields(): array
    {
        return $this->unsupportedFields;
    }

    public function optionsWereArray(): bool
    {
        return $this->optionsWereArray;
    }
}
