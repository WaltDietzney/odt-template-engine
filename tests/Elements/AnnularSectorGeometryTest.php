<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use InvalidArgumentException;
use OdtTemplateEngine\Elements\ShapeGeometry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AnnularSectorGeometryTest extends TestCase
{
    public function testZeroOmitsProgressAndFullSweepReusesExistingRing(): void
    {
        self::assertNull(ShapeGeometry::annularSector(0, 7000 / 10800));
        $preset = ShapeGeometry::preset('ring');
        $full = ShapeGeometry::annularSector(360, 0.5);
        self::assertSame($preset->type(), $full->type());
        self::assertSame($preset->path(), $full->path());
        self::assertSame($preset->additionalAttributes(), $full->additionalAttributes());
        self::assertSame($preset->equations(), $full->equations());
        self::assertSame($preset->handles(), $full->handles());
        $other = ShapeGeometry::annularSector(360, 7000 / 10800);
        self::assertSame('3800', $other->additionalAttributes()['modifiers']);
        self::assertSame('5400', ShapeGeometry::preset('ring')->additionalAttributes()['modifiers']);
    }

    #[DataProvider('sweeps')]
    public function testClosedContourHasExactEndpointOppositeInnerDirectionAndBoundedChords(float $sweep): void
    {
        $geometry = ShapeGeometry::annularSector($sweep, 7000 / 10800);
        self::assertSame('non-primitive', $geometry->type());
        self::assertSame('0 0 21600 21600', $geometry->viewBox());
        self::assertStringEndsWith(' Z N', $geometry->path());
        preg_match_all('/(?:M|L) ([\d.]+) ([\d.]+)/', $geometry->path(), $matches, PREG_SET_ORDER);
        $points = array_map(static fn (array $m): array => [(float) $m[1], (float) $m[2]], $matches);
        self::assertSame(0, count($points) % 2);
        self::assertLessThanOrEqual(242, count($points));
        $n = intdiv(count($points), 2);
        self::assertSame([10800.0, 0.0], $points[0]);
        self::assertSame([10800.0, 3800.0], $points[count($points) - 1]);
        $previous = -1.0;
        for ($i = 0; $i < $n; $i++) {
            [$x, $y] = $points[$i];
            self::assertEqualsWithDelta(10800, hypot($x - 10800, $y - 10800), 1e-7);
            $angle = rad2deg(atan2($x - 10800, 10800 - $y));
            if ($angle < 0) {
                $angle += 360;
            }
            self::assertGreaterThanOrEqual($previous, $angle);
            if ($i > 0) {
                self::assertLessThanOrEqual(3.00000001, $angle - $previous);
            }
            $previous = $angle;
        }
        self::assertEqualsWithDelta($sweep, $previous, 1e-8);
        $innerAngle = rad2deg(atan2($points[$n][0] - 10800, 10800 - $points[$n][1]));
        if ($innerAngle < 0) {
            $innerAngle += 360;
        }
        self::assertEqualsWithDelta($sweep, $innerAngle, 1e-8);
        for ($i = $n; $i < count($points); $i++) {
            [$x, $y] = $points[$i];
            self::assertEqualsWithDelta(7000, hypot($x - 10800, $y - 10800), 1e-7);
        }
        $area = 0.0;
        foreach ($points as $i => [$x, $y]) {
            [$nextX, $nextY] = $points[($i + 1) % count($points)];
            $area += $x * $nextY - $nextX * $y;
        }
        self::assertGreaterThan(0, $area);
        $ideal = M_PI * (10800 ** 2 - 7000 ** 2) * $sweep / 360;
        self::assertEqualsWithDelta($ideal, $area / 2, max(0.001, $ideal * 0.00046));
        self::assertFalse($this->inside($points, 10800, 10800));
    }

    public static function sweeps(): iterable
    {
        foreach ([3.6, 252.0, 356.4, 252.36, 0.001, 1.0, 180.0, 359.9] as $value) {
            yield (string) $value => [$value];
        }
    }

    #[DataProvider('invalidInputs')]
    public function testInvalidAndPrecisionCollapsingGeometryFails(float $sweep, float $ratio): void
    {
        $this->expectException(InvalidArgumentException::class);
        ShapeGeometry::annularSector($sweep, $ratio);
    }

    public static function invalidInputs(): iterable
    {
        foreach ([-1.0, 360.001, INF, -INF, NAN, 1e-20, 360 - 1e-12] as $index => $value) {
            yield 'sweep-' . $index => [$value, 0.65];
        }
        foreach ([0.0, 1.0, -0.1, 1.1, INF, NAN, 1e-20, 1 - 1e-15] as $index => $value) {
            yield 'ratio-' . $index => [252.0, $value];
        }
        yield 'zero still validates ratio' => [0.0, 0.0];
    }

    private function inside(array $points, float $x, float $y): bool
    {
        $inside = false;
        for ($i = 0, $j = count($points) - 1; $i < count($points); $j = $i++) {
            [$xi, $yi] = $points[$i];
            [$xj, $yj] = $points[$j];
            if (($yi > $y) !== ($yj > $y) && $x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi) {
                $inside = !$inside;
            }
        }
        return $inside;
    }
}
