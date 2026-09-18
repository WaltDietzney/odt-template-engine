<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Mapping;

use OdtTemplateEngine\Mapping\ApplicationDataResolution;
use OdtTemplateEngine\Mapping\ApplicationDataResolver;
use OdtTemplateEngine\Mapping\ApplicationPath;
use PHPUnit\Framework\TestCase;

final class ApplicationDataResolverTest extends TestCase
{
    public function testResolvesPresentNullAndMissingValuesSeparately(): void
    {
        $resolver = new ApplicationDataResolver();

        $present = $resolver->resolve(ApplicationPath::parse('person.name'), [
            'person' => ['name' => 'Walter'],
        ]);
        self::assertSame(ApplicationDataResolution::PRESENT, $present->status());
        self::assertSame('Walter', $present->value());

        $null = $resolver->resolve(ApplicationPath::parse('person.name'), [
            'person' => ['name' => null],
        ]);
        self::assertSame(ApplicationDataResolution::NULL, $null->status());

        $missing = $resolver->resolve(ApplicationPath::parse('person.name'), [
            'person' => [],
        ]);
        self::assertSame(ApplicationDataResolution::MISSING, $missing->status());
    }

    public function testEmptyArrayIsInterpretedAccordingToPathContext(): void
    {
        $resolver = new ApplicationDataResolver();

        $emptyCollection = $resolver->resolve(ApplicationPath::parse('jobs[]'), ['jobs' => []]);
        self::assertSame(ApplicationDataResolution::EMPTY_COLLECTION, $emptyCollection->status());

        $emptyRecord = $resolver->resolve(ApplicationPath::parse('person.name'), ['person' => []]);
        self::assertSame(ApplicationDataResolution::MISSING, $emptyRecord->status());
    }

    public function testDistinguishesCollectionMissingNullEmptyAndPresent(): void
    {
        $resolver = new ApplicationDataResolver();

        self::assertSame(
            ApplicationDataResolution::MISSING,
            $resolver->resolve(ApplicationPath::parse('jobs[]'), [])->status()
        );
        self::assertSame(
            ApplicationDataResolution::NULL,
            $resolver->resolve(ApplicationPath::parse('jobs[]'), ['jobs' => null])->status()
        );
        self::assertSame(
            ApplicationDataResolution::EMPTY_COLLECTION,
            $resolver->resolve(ApplicationPath::parse('jobs[]'), ['jobs' => []])->status()
        );

        $present = $resolver->resolve(ApplicationPath::parse('jobs[]'), [
            'jobs' => [['employer' => 'A'], ['employer' => 'B']],
        ]);
        self::assertSame(ApplicationDataResolution::PRESENT, $present->status());
        self::assertSame([0, 1], array_map(
            static fn (ApplicationDataResolution $item): ?int => $item->itemIndex(),
            $present->items()
        ));
    }

    public function testRejectsScalarAndSparseCollectionShapes(): void
    {
        $resolver = new ApplicationDataResolver();

        self::assertSame(
            ApplicationDataResolution::WRONG_SHAPE,
            $resolver->resolve(ApplicationPath::parse('jobs[]'), ['jobs' => 'foo'])->status()
        );
        self::assertSame(
            ApplicationDataResolution::WRONG_SHAPE,
            $resolver->resolve(ApplicationPath::parse('jobs[]'), [
                'jobs' => [0 => ['employer' => 'A'], 2 => ['employer' => 'B']],
            ])->status()
        );
        self::assertSame(
            ApplicationDataResolution::WRONG_SHAPE,
            $resolver->resolve(ApplicationPath::parse('jobs[]'), [
                'jobs' => ['first' => ['employer' => 'A']],
            ])->status()
        );
    }

    public function testTerminalCollectionAllowsScalarItemsAndRetainsItemIdentity(): void
    {
        $result = (new ApplicationDataResolver())->resolve(
            ApplicationPath::parse('tags[]'),
            ['tags' => ['PHP', 'ODT', 'LibreOffice']]
        );

        self::assertSame(ApplicationDataResolution::PRESENT, $result->status());
        self::assertSame(['PHP', 'ODT', 'LibreOffice'], array_map(
            static fn (ApplicationDataResolution $item): mixed => $item->value(),
            $result->items()
        ));
        self::assertSame([0, 1, 2], array_map(
            static fn (ApplicationDataResolution $item): ?int => $item->itemIndex(),
            $result->items()
        ));
    }

    public function testTraversesRecordItemsAndReportsWrongItemShapeWithIndex(): void
    {
        $result = (new ApplicationDataResolver())->resolve(
            ApplicationPath::parse('jobs[].employer'),
            ['jobs' => [['employer' => 'A'], 'B']]
        );

        self::assertSame(ApplicationDataResolution::WRONG_SHAPE, $result->status());
        self::assertSame(ApplicationDataResolution::PRESENT, $result->items()[0]->status());
        self::assertSame('A', $result->items()[0]->value());
        self::assertSame(ApplicationDataResolution::WRONG_SHAPE, $result->items()[1]->status());
        self::assertSame('B', $result->items()[1]->value());
        self::assertSame(1, $result->items()[1]->itemIndex());
    }

    public function testPreservesNestedCollectionItemStructureWithoutFlattening(): void
    {
        $result = (new ApplicationDataResolver())->resolve(
            ApplicationPath::parse('jobs[].projects[].title'),
            [
                'jobs' => [
                    [
                        'employer' => 'A',
                        'projects' => [['title' => 'X'], ['title' => 'Y']],
                    ],
                    [
                        'employer' => 'B',
                        'projects' => [],
                    ],
                ],
            ]
        );

        self::assertSame(ApplicationDataResolution::PRESENT, $result->status());
        self::assertCount(2, $result->items());
        self::assertSame(0, $result->items()[0]->itemIndex());
        self::assertSame(1, $result->items()[1]->itemIndex());

        $firstProjects = $result->items()[0];
        self::assertSame(ApplicationDataResolution::PRESENT, $firstProjects->status());
        self::assertSame([0, 1], array_map(
            static fn (ApplicationDataResolution $item): ?int => $item->itemIndex(),
            $firstProjects->items()
        ));
        self::assertSame(['X', 'Y'], array_map(
            static fn (ApplicationDataResolution $item): mixed => $item->value(),
            $firstProjects->items()
        ));

        self::assertSame(ApplicationDataResolution::EMPTY_COLLECTION, $result->items()[1]->status());
        self::assertSame([], $result->items()[1]->items());
    }

    public function testNestedCollectionMissingAndNullRemainItemLocal(): void
    {
        $resolver = new ApplicationDataResolver();

        $missing = $resolver->resolve(ApplicationPath::parse('jobs[].projects[]'), [
            'jobs' => [['employer' => 'A'], ['projects' => []]],
        ]);
        self::assertSame(ApplicationDataResolution::MISSING, $missing->items()[0]->status());
        self::assertSame(0, $missing->items()[0]->itemIndex());
        self::assertSame(ApplicationDataResolution::EMPTY_COLLECTION, $missing->items()[1]->status());
        self::assertSame(1, $missing->items()[1]->itemIndex());

        $null = $resolver->resolve(ApplicationPath::parse('jobs[].projects[]'), [
            'jobs' => [['projects' => null]],
        ]);
        self::assertSame(ApplicationDataResolution::NULL, $null->items()[0]->status());
        self::assertSame(0, $null->items()[0]->itemIndex());
    }

    public function testEmptyItemRecordIsValidAndMissingNamedValueIsNotWrongShape(): void
    {
        $result = (new ApplicationDataResolver())->resolve(
            ApplicationPath::parse('jobs[].employer'),
            ['jobs' => [[]]]
        );

        self::assertSame(ApplicationDataResolution::PRESENT, $result->status());
        self::assertSame(ApplicationDataResolution::MISSING, $result->items()[0]->status());
        self::assertSame(0, $result->items()[0]->itemIndex());
    }

    public function testDoesNotConstrainTerminalValuePayloadShapeOrMutateInput(): void
    {
        $payload = (object) ['arbitrary' => true];
        $data = ['person' => ['payload' => $payload]];
        $before = $data;

        $result = (new ApplicationDataResolver())->resolve(
            ApplicationPath::parse('person.payload'),
            $data
        );

        self::assertSame(ApplicationDataResolution::PRESENT, $result->status());
        self::assertSame($payload, $result->value());
        self::assertSame($before, $data);

        $nestedObject = (new ApplicationDataResolver())->resolve(
            ApplicationPath::parse('person.payload.arbitrary'),
            $data
        );
        self::assertSame(ApplicationDataResolution::WRONG_SHAPE, $nestedObject->status());
    }
}
