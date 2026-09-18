<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Mapping;

use OdtTemplateEngine\Document\DocumentInspection;
use OdtTemplateEngine\Mapping\ApplicationDataResolution;
use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\ConcreteMappingPreflight;
use OdtTemplateEngine\Mapping\ConcretePreflightOperation;
use OdtTemplateEngine\Mapping\ConcretePreflightResult;
use OdtTemplateEngine\Mapping\DependencyMapping;
use OdtTemplateEngine\Mapping\DependencyMappingResolution;
use OdtTemplateEngine\Mapping\DependencyScopeProjection;
use OdtTemplateEngine\Mapping\DependencyScopeProjector;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\Mapping\MappingResolution;
use OdtTemplateEngine\Mapping\ProjectedDependencyValue;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\DependencyDescriptor;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Tests\Support\MappingTemplateFixture;
use PHPUnit\Framework\TestCase;

final class DependencyScopeProjectorTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testProjectsRootSiblingAndThreeNestedCollectionScopesByTemplateIdentity(): void
    {
        $contract = $this->contract(
            '<text:p>{{company}}</text:p><text:p>{{position}}</text:p>'
            . '<text:section text:name="#foreach:projects"><text:p>{{title}}</text:p>'
            . '<text:section text:name="#foreach:tasks"><text:p>{{label}}</text:p></text:section>'
            . '</text:section>'
        );
        $definition = $this->nestedDefinition();
        $data = [
            'person' => ['name' => 'Walter'],
            'jobs' => [
                [
                    'employer' => 'Firma A',
                    'company' => 'wrong same-name value',
                    'position' => 'Developer',
                    'projects' => [
                        ['title' => 'A1', 'tasks' => [['label' => 'A1-X'], ['label' => 'A1-Y']]],
                        ['title' => 'A2', 'tasks' => [['label' => 'A2-X']]],
                    ],
                ],
                [
                    'employer' => 'Firma B',
                    'position' => 'Consultant',
                    'projects' => [
                        ['title' => 'B1', 'tasks' => [['label' => 'B1-X']]],
                    ],
                ],
            ],
        ];
        [$preflight] = $this->readyPreflight($contract, $definition, $data);
        self::assertSame(ConcretePreflightResult::READY, $preflight->status());

        $contractBefore = $contract->toArray();
        $mappingBefore = serialize($preflight->mappingResolution());
        $preflightBefore = serialize($preflight);
        $dataBefore = serialize($data);
        $projection = (new DependencyScopeProjector())->project($contract, $preflight);

        self::assertSame('Walter', $this->value($projection, $contract, 'name'));
        self::assertSame(DependencyMappingResolution::EXPLICIT, $this->projected($projection, $contract, 'name')->provenance());

        $experienceId = $this->dependency($contract, 'experience[]')->id();
        self::assertSame(
            ApplicationDataResolution::PRESENT,
            $this->projected($projection, $contract, 'experience[]')->status()
        );
        self::assertNull($this->value($projection, $contract, 'experience[]'));
        $experienceItems = $projection->collectionItems($experienceId);
        self::assertCount(2, $experienceItems);
        self::assertSame([0, 1], array_map(
            static fn (DependencyScopeProjection $item): ?int => $item->itemIndex(),
            $experienceItems
        ));

        self::assertSame('Firma A', $this->value($experienceItems[0], $contract, 'experience[].company'));
        self::assertSame('experience[].company', $this->projected(
            $experienceItems[0],
            $contract,
            'experience[].company'
        )->target()->path());
        self::assertSame('Developer', $this->value($experienceItems[0], $contract, 'experience[].position'));
        self::assertSame('Firma B', $this->value($experienceItems[1], $contract, 'experience[].company'));
        self::assertSame('Consultant', $this->value($experienceItems[1], $contract, 'experience[].position'));
        self::assertSame(
            DependencyMappingResolution::EXPLICIT,
            $this->projected($experienceItems[0], $contract, 'experience[].company')->provenance()
        );
        self::assertSame(
            DependencyMappingResolution::SCOPED_SAME_NAME,
            $this->projected($experienceItems[0], $contract, 'experience[].position')->provenance()
        );

        $projectsId = $this->dependency($contract, 'experience[].projects[]')->id();
        $firstProjects = $experienceItems[0]->collectionItems($projectsId);
        $secondProjects = $experienceItems[1]->collectionItems($projectsId);
        self::assertCount(2, $firstProjects);
        self::assertCount(1, $secondProjects);
        self::assertSame(0, $firstProjects[0]->itemIndex());
        self::assertSame(0, $secondProjects[0]->itemIndex());
        self::assertSame('A1', $this->value($firstProjects[0], $contract, 'experience[].projects[].title'));
        self::assertSame('B1', $this->value($secondProjects[0], $contract, 'experience[].projects[].title'));

        $tasksId = $this->dependency($contract, 'experience[].projects[].tasks[]')->id();
        $a1Tasks = $firstProjects[0]->collectionItems($tasksId);
        self::assertSame(['A1-X', 'A1-Y'], array_map(
            fn (DependencyScopeProjection $item): mixed => $this->value(
                $item,
                $contract,
                'experience[].projects[].tasks[].label'
            ),
            $a1Tasks
        ));
        self::assertSame('A2-X', $this->value(
            $firstProjects[1]->collectionItems($tasksId)[0],
            $contract,
            'experience[].projects[].tasks[].label'
        ));
        self::assertSame('B1-X', $this->value(
            $secondProjects[0]->collectionItems($tasksId)[0],
            $contract,
            'experience[].projects[].tasks[].label'
        ));

        self::assertSame($contractBefore, $contract->toArray());
        self::assertSame($mappingBefore, serialize($preflight->mappingResolution()));
        self::assertSame($preflightBefore, serialize($preflight));
        self::assertSame($dataBefore, serialize($data));
        self::assertStringNotContainsString('employer', serialize($projection));
    }

    public function testEmptyCollectionProducesNoPhantomScopeItems(): void
    {
        $contract = $this->contract('<text:p>constant content</text:p>');
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('person.name'), 'name'),
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
        ]);
        [$preflight] = $this->readyPreflight($contract, $definition, [
            'person' => ['name' => 'Walter'],
            'jobs' => [],
        ]);

        $projection = (new DependencyScopeProjector())->project($contract, $preflight);

        self::assertSame(
            ApplicationDataResolution::EMPTY_COLLECTION,
            $this->projected($projection, $contract, 'experience[]')->status()
        );
        self::assertSame([], $projection->collectionItems($this->dependency($contract, 'experience[]')->id()));
    }

    public function testProjectionRequiresReadyPreflightAndRejectsInconsistentSiblingLineage(): void
    {
        $contract = $this->contract('<text:p>{{company}}</text:p><text:p>{{position}}</text:p>');
        $definition = $this->nestedDefinition(onlyExperience: true);
        [$preflight, $mappingResolution] = $this->readyPreflight($contract, $definition, [
            'person' => ['name' => 'Walter'],
            'jobs' => [
                ['employer' => 'A', 'position' => 'Developer'],
                ['employer' => 'B', 'position' => 'Consultant'],
            ],
        ]);

        $invalidDefinition = new MappingDefinition();
        $failedPreflight = (new ConcreteMappingPreflight())->preflight(
            $invalidDefinition,
            $contract,
            [],
            new DocumentInspection([], [], [], [])
        );
        try {
            (new DependencyScopeProjector())->project($contract, $failedPreflight);
            self::fail('Projection must reject a non-READY preflight.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('READY', $exception->getMessage());
        }

        $dependencies = $mappingResolution->dependencies();
        $operations = $preflight->operations();
        foreach ($dependencies as $index => $resolution) {
            if ($resolution->target()->path() !== 'experience[].position') {
                continue;
            }
            $inconsistentData = new ApplicationDataResolution(
                ApplicationDataResolution::PRESENT,
                [['position' => 'Developer']],
                null,
                [new ApplicationDataResolution(ApplicationDataResolution::PRESENT, 'Developer', 0)]
            );
            $inconsistent = new DependencyMappingResolution(
                $resolution->target(),
                DependencyMappingResolution::RESOLVED,
                $resolution->source(),
                $resolution->provenance(),
                $inconsistentData
            );
            $dependencies[$index] = $inconsistent;
            foreach ($operations as $operationIndex => $operation) {
                if ($operation->resolution() !== $resolution) {
                    continue;
                }
                $operations[$operationIndex] = new ConcretePreflightOperation(
                    $operation->targetFamily(),
                    $operation->targetIdentity(),
                    $inconsistent,
                    $operation->capabilityId(),
                    $operation->payloadKind(),
                    $operation->applicability(),
                    ConcretePreflightOperation::READY,
                    []
                );
            }
        }
        $inconsistentPreflight = new ConcretePreflightResult(
            new MappingResolution($dependencies, [], []),
            $operations
        );
        self::assertTrue($inconsistentPreflight->ready());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('inconsistent with template scope');
        (new DependencyScopeProjector())->project($contract, $inconsistentPreflight);
    }

    private function nestedDefinition(bool $onlyExperience = false): MappingDefinition
    {
        $rules = [
            new DependencyMapping(ApplicationPath::parse('person.name'), 'name'),
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
            new DependencyMapping(ApplicationPath::parse('jobs[].employer'), 'experience[].company'),
        ];
        if (!$onlyExperience) {
            $rules[] = new DependencyMapping(
                ApplicationPath::parse('jobs[].projects[]'),
                'experience[].projects[]'
            );
            $rules[] = new DependencyMapping(
                ApplicationPath::parse('jobs[].projects[].tasks[]'),
                'experience[].projects[].tasks[]'
            );
        }
        return new MappingDefinition($rules);
    }

    /** @return array{ConcretePreflightResult, MappingResolution} */
    private function readyPreflight(TemplateContract $contract, MappingDefinition $definition, array $data): array
    {
        $preflight = (new ConcreteMappingPreflight())->preflight(
            $definition,
            $contract,
            $data,
            new DocumentInspection([], [], [], [])
        );
        return [$preflight, $preflight->mappingResolution()];
    }

    private function contract(string $experienceBody): TemplateContract
    {
        $path = MappingTemplateFixture::create('', $experienceBody);
        $this->paths[] = $path;
        return (new OdtTemplate($path))->inspectTemplate();
    }

    private function dependency(TemplateContract $contract, string $path): DependencyDescriptor
    {
        foreach ($contract->dependencies() as $dependency) {
            if ($dependency->path() === $path) {
                return $dependency;
            }
        }
        self::fail(sprintf('Missing TemplateContract dependency %s.', $path));
    }

    private function projected(
        DependencyScopeProjection $scope,
        TemplateContract $contract,
        string $path
    ): ProjectedDependencyValue
    {
        $dependency = $this->dependency($contract, $path);
        $projected = $scope->dependency($dependency->id());
        self::assertNotNull($projected, sprintf('Dependency %s is not projected in this scope.', $path));
        return $projected;
    }

    private function value(DependencyScopeProjection $scope, TemplateContract $contract, string $path): mixed
    {
        return $this->projected($scope, $contract, $path)->value();
    }
}
