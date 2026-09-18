<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Mapping;

use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\ApplicationPathSegment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApplicationPathTest extends TestCase
{
    public function testParsesValueCollectionAndArbitrarilyNestedCollectionPaths(): void
    {
        $path = ApplicationPath::parse('jobs[].projects[].title');

        self::assertSame('jobs[].projects[].title', $path->canonical());
        self::assertSame([
            ['jobs', ApplicationPathSegment::COLLECTION],
            ['projects', ApplicationPathSegment::COLLECTION],
            ['title', ApplicationPathSegment::VALUE],
        ], array_map(
            static fn (ApplicationPathSegment $segment): array => [$segment->name(), $segment->kind()],
            $path->segments()
        ));
        self::assertSame(['jobs[]', 'jobs[].projects[]'], $path->collectionPrefixes());
        self::assertSame($path->canonical(), (string) ApplicationPath::parse($path->canonical()));
    }

    public function testParsesSimpleValueAndTerminalCollection(): void
    {
        self::assertSame('person.name', ApplicationPath::parse('person.name')->canonical());
        self::assertSame(ApplicationPathSegment::COLLECTION, ApplicationPath::parse('jobs[]')->terminalKind());
        self::assertSame(ApplicationPathSegment::VALUE, ApplicationPath::parse('jobs[].employer')->terminalKind());
    }

    #[DataProvider('invalidPaths')]
    public function testRejectsMalformedPaths(string $path): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ApplicationPath::parse($path);
    }

    public static function invalidPaths(): iterable
    {
        yield 'empty' => [''];
        yield 'numeric index' => ['jobs[0].company'];
        yield 'wildcard index' => ['jobs[*].company'];
        yield 'named index' => ['jobs.company[foo]'];
        yield 'empty segment' => ['jobs..company'];
        yield 'leading separator' => ['.jobs'];
        yield 'trailing separator' => ['jobs.'];
        yield 'detached collection marker' => ['jobs.[]'];
        yield 'illegal identifier start' => ['9jobs.company'];
        yield 'detached bracket' => ['jobs[ ].company'];
    }
}
