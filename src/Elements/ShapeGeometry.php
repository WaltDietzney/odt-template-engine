<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Elements;

use InvalidArgumentException;

/**
 * Validated native enhanced geometry independent of physical drawing size.
 */
final readonly class ShapeGeometry
{
    private const PRESETS = [
        'rectangle' => 'M 0 0 L 21600 0 21600 21600 0 21600 0 0 Z N',
        'ellipse' => 'U 10800 10800 10800 10800 0 360 Z N',
        'diamond' => 'M 10800 0 L 21600 10800 10800 21600 0 10800 10800 0 Z N',
        'triangle' => 'M 10800 0 L 21600 21600 0 21600 Z N',
        'cross' => 'M 7200 0 L 14400 0 14400 7200 21600 7200 21600 14400 14400 14400 14400 21600 7200 21600 7200 14400 0 14400 0 7200 7200 7200 Z N',
        'speech-bubble' => 'M 0 0 L 21600 0 21600 16200 10800 16200 5400 21600 5400 16200 0 16200 Z N',
        'up-arrow' => 'M 10800 0 L 21600 10800 16200 10800 16200 21600 5400 21600 5400 10800 0 10800 Z N',
        'down-arrow' => 'M 5400 0 L 16200 0 16200 10800 21600 10800 10800 21600 0 10800 5400 10800 Z N',
        'hexagon' => 'M 5400 0 L 16200 0 21600 10800 16200 21600 5400 21600 0 10800 Z N',
        'pentagon' => 'M 10800 0 L 21600 8000 17500 21600 4100 21600 0 8000 Z N',
        'chevron' => 'M 0 0 L 14000 0 21600 10800 14000 21600 0 21600 7600 10800 Z N',
        'star5' => 'M 10800 0 L 13400 7900 21600 7900 15000 12900 17500 21600 10800 16500 4100 21600 6600 12900 0 7900 8200 7900 Z N',
        'flowchart-stored-data' => 'M 3600 21600 X 0 10800 3600 0 L 21600 0 X 18000 10800 21600 21600 Z N',
        'flowchart-off-page-connector' => 'M 0 0 L 21600 0 21600 17150 10800 21600 0 17150 0 0 Z N',
        'flowchart-punched-tape' => 'M 0 2230 C 820 3990 3410 3980 5370 4360 7430 4030 10110 3890 10690 2270 11440 300 14200 160 16150 0 18670 170 20690 390 21600 2230 L 21600 19420 C 20640 17510 18320 17490 16140 17240 14710 17370 11310 17510 10770 19430 10150 21150 7380 21290 5290 21600 3220 21250 610 21130 0 19420 Z N',
        'arrow-contour' => 'M 0 5400 L 13500 5400 13500 0 21600 10800 13500 21600 13500 16200 0 16200 Z N',
    ];

    private const REFERENCE_ATTRIBUTES = [
        'flowchart-stored-data' => ['glue-points' => '10800 0 0 10800 10800 21600 18000 10800', 'text-areas' => '3600 0 18000 21600'],
        'flowchart-off-page-connector' => ['glue-points' => '10800 0 0 10800 10800 21600 21600 10800', 'text-areas' => '0 0 21600 17150'],
        'flowchart-punched-tape' => ['glue-points' => '10800 2020 0 10800 10800 19320 21600 10800', 'text-areas' => '0 4360 21600 17240'],
    ];

    private const COMPLEX_PRESETS = [
        'left-arrow' => [
            'path' => 'M 21600 ?f0 L ?f1 ?f0 ?f1 0 0 10800 ?f1 21600 ?f1 ?f2 21600 ?f2 Z N',
            'attributes' => ['text-areas' => '?f7 ?f0 21600 ?f2', 'modifiers' => '5400 5400'],
            'equations' => [['name' => 'f0', 'formula' => '$1 '], ['name' => 'f1', 'formula' => '$0 '], ['name' => 'f2', 'formula' => '21600-$1 '], ['name' => 'f3', 'formula' => '21600-?f1 '], ['name' => 'f4', 'formula' => '?f3 *?f0 /10800'], ['name' => 'f5', 'formula' => '?f1 +?f4 '], ['name' => 'f6', 'formula' => '?f1 *?f0 /10800'], ['name' => 'f7', 'formula' => '?f1 -?f6 ']],
            'handles' => [['handle-position' => '$0 $1', 'handle-range-x-minimum' => '0', 'handle-range-x-maximum' => '21600', 'handle-range-y-minimum' => '0', 'handle-range-y-maximum' => '10800']],
        ],
        'round-rectangle' => [
            'path' => 'M ?f7 0 X 0 ?f8 L 0 ?f9 Y ?f7 21600 L ?f10 21600 X 21600 ?f9 L 21600 ?f8 Y ?f10 0 Z N',
            'attributes' => ['path-stretchpoint-x' => '10800', 'path-stretchpoint-y' => '10800', 'text-areas' => '?f3 ?f4 ?f5 ?f6', 'modifiers' => '3600'],
            'equations' => [['name' => 'f0', 'formula' => '45'], ['name' => 'f1', 'formula' => '$0 *sin(?f0 *(pi/180))'], ['name' => 'f2', 'formula' => '?f1 *3163/7636'], ['name' => 'f3', 'formula' => 'left+?f2 '], ['name' => 'f4', 'formula' => 'top+?f2 '], ['name' => 'f5', 'formula' => 'right-?f2 '], ['name' => 'f6', 'formula' => 'bottom-?f2 '], ['name' => 'f7', 'formula' => 'left+$0 '], ['name' => 'f8', 'formula' => 'top+$0 '], ['name' => 'f9', 'formula' => 'bottom-$0 '], ['name' => 'f10', 'formula' => 'right-$0 ']],
            'handles' => [['handle-position' => '$0 top', 'handle-switched' => 'true', 'handle-range-x-minimum' => '0', 'handle-range-x-maximum' => '10800']],
        ],

        'isosceles-triangle' => [
            'path' => 'M ?f0 0 L 21600 21600 0 21600 Z N',
            'attributes' => ['glue-points' => '?f0 0 ?f1 10800 0 21600 10800 21600 21600 21600 ?f7 10800', 'text-areas' => '?f1 10800 ?f2 18000 ?f3 7200 ?f4 21600', 'modifiers' => '10800'],
            'equations' => [['name' => 'f0', 'formula' => '$0 '], ['name' => 'f1', 'formula' => '$0 /2'], ['name' => 'f2', 'formula' => '?f1 +10800'], ['name' => 'f3', 'formula' => '$0 *2/3'], ['name' => 'f4', 'formula' => '?f3 +7200'], ['name' => 'f5', 'formula' => '21600-?f0 '], ['name' => 'f6', 'formula' => '?f5 /2'], ['name' => 'f7', 'formula' => '21600-?f6 ']],
            'handles' => [['handle-position' => '$0 top', 'handle-range-x-minimum' => '0', 'handle-range-x-maximum' => '21600']],
        ],
        'ring' => [
            'path' => 'U 10800 10800 10800 10800 0 360 Z U 10800 10800 ?f1 ?f1 0 360 N',
            'attributes' => ['glue-points' => '10800 0 3163 3163 0 10800 3163 18437 10800 21600 18437 18437 21600 10800 18437 3163', 'text-areas' => '3163 3163 18437 18437', 'modifiers' => '5400'],
            'equations' => [['name' => 'f0', 'formula' => '$0 '], ['name' => 'f1', 'formula' => '10800-$0 ']],
            'handles' => [['handle-position' => '$0 10800', 'handle-range-x-minimum' => '0', 'handle-range-x-maximum' => '10800']],
        ],
    ];

    private function __construct(
        private string $type,
        private string $path,
        private array $attributeOverrides = []
    ) {
    }

    public static function preset(string $type): self
    {
        if (!isset(self::PRESETS[$type]) && !isset(self::COMPLEX_PRESETS[$type])) {
            throw new InvalidArgumentException(sprintf('Unsupported shape geometry "%s".', $type));
        }

        if (isset(self::COMPLEX_PRESETS[$type])) {
            return new self($type, self::COMPLEX_PRESETS[$type]['path']);
        }

        return new self($type === 'arrow-contour' ? 'right-arrow' : $type, self::PRESETS[$type]);
    }

    /**
     * Construct a clockwise annular sector beginning at the top, or omit zero sweep.
     *
     * Angles are geometry, not percentage/rating semantics. Partial arcs use chords
     * of at most three degrees and explicitly include the supplied endpoint.
     */
    public static function annularSector(float $sweepDegrees, float $innerRadiusRatio): ?self
    {
        if (!is_finite($sweepDegrees) || $sweepDegrees < 0 || $sweepDegrees > 360
            || !is_finite($innerRadiusRatio) || $innerRadiusRatio <= 0 || $innerRadiusRatio >= 1) {
            throw new InvalidArgumentException('Annular sector requires finite sweep [0,360] and inner radius ratio (0,1).');
        }
        $inner = round(10800 * $innerRadiusRatio, 8);
        if ($inner <= 0 || $inner >= 10800) {
            throw new InvalidArgumentException('Annular sector thickness collapses at the supported coordinate precision.');
        }
        if ($sweepDegrees === 0.0) {
            return null;
        }
        if ($sweepDegrees === 360.0) {
            $ring = self::preset('ring');
            return new self($ring->type, $ring->path, ['modifiers' => self::coordinate(10800 - $inner)]);
        }
        $angles = [];
        for ($angle = 0.0; $angle < $sweepDegrees; $angle += 3.0) {
            $angles[] = $angle;
        }
        $angles[] = $sweepDegrees;
        $point = static function (float $radius, float $angle): string {
            $radians = deg2rad($angle);
            return self::coordinate(10800 + $radius * sin($radians)) . ' '
                . self::coordinate(10800 - $radius * cos($radians));
        };
        $outer = array_map(static fn (float $angle): string => $point(10800, $angle), $angles);
        if ($outer[0] === $outer[count($outer) - 1]) {
            throw new InvalidArgumentException('Annular sector endpoint collapses at the supported coordinate precision.');
        }
        $inside = array_map(static fn (float $angle): string => $point($inner, $angle), array_reverse($angles));
        if ($inside[0] === $inside[count($inside) - 1]) {
            throw new InvalidArgumentException('Inner annular sector endpoint collapses at the supported coordinate precision.');
        }
        return new self('non-primitive', 'M ' . implode(' L ', [...$outer, ...$inside]) . ' Z N');
    }

    private static function coordinate(float $value): string
    {
        $formatted = rtrim(rtrim(number_format($value, 8, '.', ''), '0'), '.');
        return $formatted === '-0' ? '0' : $formatted;
    }

    public function additionalAttributes(): array
    {
        return array_replace(
            self::COMPLEX_PRESETS[$this->type]['attributes'] ?? self::REFERENCE_ATTRIBUTES[$this->type] ?? [],
            $this->attributeOverrides
        );
    }

    public function equations(): array
    {
        return self::COMPLEX_PRESETS[$this->type]['equations'] ?? [];
    }

    public function handles(): array
    {
        return self::COMPLEX_PRESETS[$this->type]['handles'] ?? [];
    }

    public function type(): string
    {
        return $this->type;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function viewBox(): string
    {
        return '0 0 21600 21600';
    }
}
