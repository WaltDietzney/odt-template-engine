<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use InvalidArgumentException;

/**
 * Immutable semantic drawing-layout state shared by frame-backed elements.
 *
 * This type deliberately models authoring semantics rather than native ODF
 * carriers. Native object attributes and graphic layout properties are
 * projected separately by DrawingLayoutProjector.
 */
final class DrawingLayout
{
    private const ANCHORS = ['paragraph', 'char', 'as-char', 'page'];
    private const HORIZONTAL_ALIGNMENTS = ['left', 'center', 'right'];
    private const VERTICAL_ALIGNMENTS = ['top', 'middle', 'bottom'];
    private const WRAPS = ['none', 'left', 'right', 'parallel', 'dynamic', 'run-through'];

    private const HORIZONTAL_RELATIONS = [
        'paragraph' => ['paragraph', 'paragraph-content', 'page', 'page-content'],
        'char' => ['char', 'paragraph', 'paragraph-content', 'page', 'page-content'],
        'page' => ['page', 'page-content'],
        'as-char' => [],
    ];

    private const VERTICAL_RELATIONS = [
        'paragraph' => ['paragraph', 'paragraph-content', 'page', 'page-content'],
        'char' => ['char', 'paragraph', 'paragraph-content', 'page', 'page-content', 'baseline'],
        'page' => ['page', 'page-content'],
        'as-char' => ['baseline'],
    ];

    private function __construct(
        private readonly ?string $anchor,
        private readonly ?string $width,
        private readonly ?string $height,
        private readonly ?string $horizontalMode,
        private readonly ?string $horizontalAlignment,
        private readonly ?string $horizontalRelation,
        private readonly ?string $horizontalOffset,
        private readonly ?string $verticalMode,
        private readonly ?string $verticalAlignment,
        private readonly ?string $verticalRelation,
        private readonly ?string $verticalOffset,
        private readonly ?string $wrap
    ) {
    }

    public static function empty(): self
    {
        return new self(null, null, null, null, null, null, null, null, null, null, null, null);
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function fromArray(array $layout): self
    {
        self::assertKnownKeys($layout, ['anchor', 'width', 'height', 'horizontal', 'vertical', 'wrap']);

        $anchor = self::nullableString($layout['anchor'] ?? null, 'anchor');
        if ($anchor !== null && !in_array($anchor, self::ANCHORS, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported frame anchor "%s".', $anchor));
        }

        $width = self::nullableLength($layout['width'] ?? null, 'width', false);
        $height = self::nullableLength($layout['height'] ?? null, 'height', false);

        [$horizontalMode, $horizontalAlignment, $horizontalRelation, $horizontalOffset]
            = self::parseHorizontal($layout['horizontal'] ?? null, $anchor);

        [$verticalMode, $verticalAlignment, $verticalRelation, $verticalOffset]
            = self::parseVertical($layout['vertical'] ?? null, $anchor);

        $wrap = self::nullableString($layout['wrap'] ?? null, 'wrap');
        if ($wrap !== null && !in_array($wrap, self::WRAPS, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported frame wrap "%s".', $wrap));
        }

        return new self(
            $anchor,
            $width,
            $height,
            $horizontalMode,
            $horizontalAlignment,
            $horizontalRelation,
            $horizontalOffset,
            $verticalMode,
            $verticalAlignment,
            $verticalRelation,
            $verticalOffset,
            $wrap
        );
    }

    public function anchor(): ?string
    {
        return $this->anchor;
    }

    public function width(): ?string
    {
        return $this->width;
    }

    public function height(): ?string
    {
        return $this->height;
    }

    public function horizontalMode(): ?string
    {
        return $this->horizontalMode;
    }

    public function horizontalAlignment(): ?string
    {
        return $this->horizontalAlignment;
    }

    public function horizontalRelation(): ?string
    {
        return $this->horizontalRelation;
    }

    public function horizontalOffset(): ?string
    {
        return $this->horizontalOffset;
    }

    public function verticalMode(): ?string
    {
        return $this->verticalMode;
    }

    public function verticalAlignment(): ?string
    {
        return $this->verticalAlignment;
    }

    public function verticalRelation(): ?string
    {
        return $this->verticalRelation;
    }

    public function verticalOffset(): ?string
    {
        return $this->verticalOffset;
    }

    public function wrap(): ?string
    {
        return $this->wrap;
    }

    public function withAnchor(string $anchor): self
    {
        $data = $this->toArray();
        $data['anchor'] = $anchor;

        return self::fromArray($data);
    }

    public function withHorizontalAlignment(string $alignment, ?string $relativeTo = null): self
    {
        $data = $this->toArray();
        $data['horizontal'] = [
            'alignment' => $alignment,
            'relative-to' => $relativeTo ?? $this->defaultHorizontalRelation(),
        ];

        return self::fromArray($data);
    }

    public function withHorizontalOffset(string $offset, ?string $relativeTo = null): self
    {
        $data = $this->toArray();
        $data['horizontal'] = [
            'offset' => $offset,
            'relative-to' => $relativeTo ?? $this->defaultHorizontalRelation(),
        ];

        return self::fromArray($data);
    }

    public function withVerticalAlignment(string $alignment, ?string $relativeTo = null): self
    {
        $data = $this->toArray();
        $data['vertical'] = [
            'alignment' => $alignment,
            'relative-to' => $relativeTo ?? $this->defaultVerticalRelation(),
        ];

        return self::fromArray($data);
    }

    public function withVerticalOffset(string $offset, ?string $relativeTo = null): self
    {
        if ($this->anchor === 'as-char') {
            throw new InvalidArgumentException('Vertical offset is not supported for as-char frame layout.');
        }

        $data = $this->toArray();
        $data['vertical'] = [
            'offset' => $offset,
            'relative-to' => $relativeTo ?? $this->defaultVerticalRelation(),
        ];

        return self::fromArray($data);
    }

    public function withWrap(string $wrap): self
    {
        $data = $this->toArray();
        $data['wrap'] = $wrap;

        return self::fromArray($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->anchor !== null) {
            $data['anchor'] = $this->anchor;
        }
        if ($this->width !== null) {
            $data['width'] = $this->width;
        }
        if ($this->height !== null) {
            $data['height'] = $this->height;
        }
        if ($this->horizontalMode !== null) {
            $data['horizontal'] = [
                $this->horizontalMode === 'alignment' ? 'alignment' : 'offset'
                    => $this->horizontalMode === 'alignment'
                        ? $this->horizontalAlignment
                        : $this->horizontalOffset,
                'relative-to' => $this->horizontalRelation,
            ];
        }
        if ($this->verticalMode !== null) {
            $data['vertical'] = [
                $this->verticalMode === 'alignment' ? 'alignment' : 'offset'
                    => $this->verticalMode === 'alignment'
                        ? $this->verticalAlignment
                        : $this->verticalOffset,
                'relative-to' => $this->verticalRelation,
            ];
        }
        if ($this->wrap !== null) {
            $data['wrap'] = $this->wrap;
        }

        return $data;
    }

    /**
     * @return array{?string, ?string, ?string, ?string}
     */
    private static function parseHorizontal(mixed $group, ?string $anchor): array
    {
        if ($group === null) {
            return [null, null, null, null];
        }
        if (!is_array($group)) {
            throw new InvalidArgumentException('Frame horizontal layout must be an array.');
        }

        self::assertKnownKeys($group, ['alignment', 'offset', 'relative-to']);
        $hasAlignment = array_key_exists('alignment', $group);
        $hasOffset = array_key_exists('offset', $group);
        if ($hasAlignment === $hasOffset) {
            throw new InvalidArgumentException(
                'Frame horizontal layout must define exactly one of alignment or offset.'
            );
        }
        if ($anchor === 'as-char') {
            throw new InvalidArgumentException(
                'Horizontal frame placement is not supported for as-char layout.'
            );
        }

        $relation = self::nullableString($group['relative-to'] ?? null, 'horizontal relative-to')
            ?? 'paragraph';
        self::assertRelation($relation, $anchor, true);

        if ($hasAlignment) {
            $alignment = self::nullableString($group['alignment'], 'horizontal alignment');
            if ($alignment === null || !in_array($alignment, self::HORIZONTAL_ALIGNMENTS, true)) {
                throw new InvalidArgumentException('Unsupported horizontal frame alignment.');
            }

            return ['alignment', $alignment, $relation, null];
        }

        $offset = self::nullableLength($group['offset'], 'horizontal offset', true);
        if ($offset === null) {
            throw new InvalidArgumentException('Horizontal frame offset must not be empty.');
        }

        return ['offset', null, $relation, $offset];
    }

    /**
     * @return array{?string, ?string, ?string, ?string}
     */
    private static function parseVertical(mixed $group, ?string $anchor): array
    {
        if ($group === null) {
            return [null, null, null, null];
        }
        if (!is_array($group)) {
            throw new InvalidArgumentException('Frame vertical layout must be an array.');
        }

        self::assertKnownKeys($group, ['alignment', 'offset', 'relative-to']);
        $hasAlignment = array_key_exists('alignment', $group);
        $hasOffset = array_key_exists('offset', $group);
        if ($hasAlignment === $hasOffset) {
            throw new InvalidArgumentException(
                'Frame vertical layout must define exactly one of alignment or offset.'
            );
        }
        if ($anchor === 'as-char' && $hasOffset) {
            throw new InvalidArgumentException('Vertical offset is not supported for as-char frame layout.');
        }

        $relation = self::nullableString($group['relative-to'] ?? null, 'vertical relative-to')
            ?? ($anchor === 'as-char' ? 'baseline' : 'paragraph');
        self::assertRelation($relation, $anchor, false);

        if ($hasAlignment) {
            $alignment = self::nullableString($group['alignment'], 'vertical alignment');
            if ($alignment === null || !in_array($alignment, self::VERTICAL_ALIGNMENTS, true)) {
                throw new InvalidArgumentException('Unsupported vertical frame alignment.');
            }

            return ['alignment', $alignment, $relation, null];
        }

        $offset = self::nullableLength($group['offset'], 'vertical offset', true);
        if ($offset === null) {
            throw new InvalidArgumentException('Vertical frame offset must not be empty.');
        }

        return ['offset', null, $relation, $offset];
    }

    private static function assertRelation(string $relation, ?string $anchor, bool $horizontal): void
    {
        if ($anchor === null) {
            $anchor = 'paragraph';
        }

        $matrix = $horizontal ? self::HORIZONTAL_RELATIONS : self::VERTICAL_RELATIONS;
        if (!in_array($relation, $matrix[$anchor], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported %s frame relation "%s" for anchor "%s".',
                $horizontal ? 'horizontal' : 'vertical',
                $relation,
                $anchor
            ));
        }
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string> $knownKeys
     */
    private static function assertKnownKeys(array $values, array $knownKeys): void
    {
        foreach (array_keys($values) as $key) {
            if (!is_string($key) || !in_array($key, $knownKeys, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Unsupported frame layout key "%s".',
                    is_string($key) ? $key : (string) $key
                ));
            }
        }
    }

    private static function nullableString(mixed $value, string $label): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Frame %s must be a non-empty string.', $label));
        }

        return $value;
    }

    private static function nullableLength(
        mixed $value,
        string $label,
        bool $allowSigned
    ): ?string {
        $value = self::nullableString($value, $label);
        if ($value === null) {
            return null;
        }

        $pattern = $allowSigned
            ? '/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)(?:cm|mm|in|pt|pc)$/'
            : '/^(?:\d+(?:\.\d+)?|\.\d+)(?:cm|mm|in|pt|pc)$/';

        if (!preg_match($pattern, $value)) {
            throw new InvalidArgumentException(sprintf(
                'Frame %s must be an absolute ODF length.',
                $label
            ));
        }

        if (!$allowSigned && (float) $value <= 0.0) {
            throw new InvalidArgumentException(sprintf(
                'Frame %s must be greater than zero.',
                $label
            ));
        }

        return $value;
    }

    private function defaultHorizontalRelation(): string
    {
        if ($this->anchor === 'as-char') {
            throw new InvalidArgumentException(
                'Horizontal frame placement is not supported for as-char layout.'
            );
        }

        return $this->anchor === 'page' ? 'page' : 'paragraph';
    }

    private function defaultVerticalRelation(): string
    {
        if ($this->anchor === 'as-char') {
            return 'baseline';
        }

        return $this->anchor === 'page' ? 'page' : 'paragraph';
    }
}
