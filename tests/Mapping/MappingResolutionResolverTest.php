<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Mapping;

use OdtTemplateEngine\Mapping\ApplicationDataResolution;
use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\DependencyMapping;
use OdtTemplateEngine\Mapping\DependencyMappingResolution;
use OdtTemplateEngine\Mapping\DocumentCapabilityMapping;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\Mapping\MappingResolutionException;
use OdtTemplateEngine\Mapping\MappingResolutionResolver;
use OdtTemplateEngine\Mapping\NativeObjectActionMapping;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Tests\Support\MappingTemplateFixture;
use PHPUnit\Framework\TestCase;

final class MappingResolutionResolverTest extends TestCase
{
    private ?string $path = null;

    protected function tearDown(): void
    {
        if ($this->path !== null && is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function testRootSameNameUsesOnlyTheRootKey(): void
    {
        $contract = $this->contract();
        $resolution = (new MappingResolutionResolver())->resolve(
            new MappingDefinition(),
            $contract,
            ['name' => 'Walter']
        );

        $name = $this->dependency($resolution, 'name');
        self::assertSame(DependencyMappingResolution::RESOLVED, $name->status());
        self::assertSame('name', $name->source()?->canonical());
        self::assertSame(DependencyMappingResolution::SCOPED_SAME_NAME, $name->provenance());
        self::assertSame(ApplicationDataResolution::PRESENT, $name->dataResolution()?->status());
        self::assertSame('Walter', $name->dataResolution()?->value());
    }

    public function testMissingRootSameNameDoesNotSearchApplicationDescendants(): void
    {
        $resolution = (new MappingResolutionResolver())->resolve(
            new MappingDefinition(),
            $this->contract(),
            ['person' => ['name' => 'Walter']]
        );

        $name = $this->dependency($resolution, 'name');
        self::assertSame(DependencyMappingResolution::RESOLVED, $name->status());
        self::assertSame('name', $name->source()?->canonical());
        self::assertSame(DependencyMappingResolution::SCOPED_SAME_NAME, $name->provenance());
        self::assertSame(ApplicationDataResolution::MISSING, $name->dataResolution()?->status());
    }

    public function testExplicitRootMappingResolvesAndOverridesSameName(): void
    {
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('person.name'), 'name'),
        ]);
        $resolution = (new MappingResolutionResolver())->resolve(
            $definition,
            $this->contract(),
            ['name' => 'Root value', 'person' => ['name' => 'Mapped value']]
        );

        $name = $this->dependency($resolution, 'name');
        self::assertSame(DependencyMappingResolution::RESOLVED, $name->status());
        self::assertSame('person.name', $name->source()?->canonical());
        self::assertSame(DependencyMappingResolution::EXPLICIT, $name->provenance());
        self::assertSame('Mapped value', $name->dataResolution()?->value());
    }

    public function testExplicitCollectionScopeEnablesImmediateSameNameOnly(): void
    {
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
        ]);
        $resolution = (new MappingResolutionResolver())->resolve(
            $definition,
            $this->contract(),
            ['jobs' => [['company' => 'A', 'address' => ['city' => 'Hidden']]]]
        );

        $company = $this->dependency($resolution, 'experience[].company');
        self::assertSame(DependencyMappingResolution::RESOLVED, $company->status());
        self::assertSame('jobs[].company', $company->source()?->canonical());
        self::assertSame(DependencyMappingResolution::SCOPED_SAME_NAME, $company->provenance());
        self::assertSame('A', $company->dataResolution()?->items()[0]->value());
    }

    public function testMissingAndNullDataDoNotMakeAConstructibleMappingUnresolved(): void
    {
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
        ]);
        $resolver = new MappingResolutionResolver();

        $missingResolution = $resolver->resolve(
            $definition,
            $this->contract('<text:section text:name="#foreach:experience"><text:p>{{location}}</text:p></text:section>'),
            ['jobs' => [[]]]
        );
        $location = $this->dependency($missingResolution, 'experience[].location');
        self::assertSame(DependencyMappingResolution::RESOLVED, $location->status());
        self::assertSame('jobs[].location', $location->source()?->canonical());
        self::assertSame(DependencyMappingResolution::SCOPED_SAME_NAME, $location->provenance());
        self::assertSame(ApplicationDataResolution::MISSING, $location->dataResolution()?->items()[0]->status());

        $nullResolution = $resolver->resolve(
            $definition,
            $this->contract('<text:section text:name="#foreach:experience"><text:p>{{location}}</text:p></text:section>'),
            ['jobs' => [['location' => null]]]
        );
        $nullLocation = $this->dependency($nullResolution, 'experience[].location');
        self::assertSame(DependencyMappingResolution::RESOLVED, $nullLocation->status());
        self::assertSame(ApplicationDataResolution::NULL, $nullLocation->dataResolution()?->items()[0]->status());
    }

    public function testNestedSameNameRequiresEachCollectionScopeToBeExplicitlyMapped(): void
    {
        $onlyParentMapped = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
        ]);
        $unmappedResolution = (new MappingResolutionResolver())->resolve(
            $onlyParentMapped,
            $this->contract(),
            ['jobs' => [['projects' => [['title' => 'A']]]]]
        );
        $title = $this->dependency($unmappedResolution, 'experience[].projects[].title');
        self::assertSame(DependencyMappingResolution::UNRESOLVED, $title->status());

        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
            new DependencyMapping(
                ApplicationPath::parse('jobs[].projects[]'),
                'experience[].projects[]'
            ),
        ]);
        $resolution = (new MappingResolutionResolver())->resolve(
            $definition,
            $this->contract(),
            ['jobs' => [['projects' => [['title' => 'X'], ['title' => 'Y']]]]]
        );

        $title = $this->dependency($resolution, 'experience[].projects[].title');
        self::assertSame(DependencyMappingResolution::RESOLVED, $title->status());
        self::assertSame('jobs[].projects[].title', $title->source()?->canonical());
        self::assertSame(DependencyMappingResolution::SCOPED_SAME_NAME, $title->provenance());
        self::assertSame([0, 1], array_map(
            static fn (ApplicationDataResolution $item): ?int => $item->itemIndex(),
            $title->dataResolution()?->items()[0]->items() ?? []
        ));
        self::assertSame(['X', 'Y'], array_map(
            static fn (ApplicationDataResolution $item): mixed => $item->value(),
            $title->dataResolution()?->items()[0]->items() ?? []
        ));
    }

    public function testNestedExplicitOverrideSuppressesSameNameCandidate(): void
    {
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
            new DependencyMapping(
                ApplicationPath::parse('jobs[].projects[]'),
                'experience[].projects[]'
            ),
            new DependencyMapping(
                ApplicationPath::parse('jobs[].projects[].clientName'),
                'experience[].projects[].customer'
            ),
        ]);
        $resolution = (new MappingResolutionResolver())->resolve(
            $definition,
            $this->contract(
                '<text:section text:name="#foreach:experience">'
                . '<text:section text:name="#foreach:projects"><text:p>{{customer}}</text:p>'
                . '</text:section></text:section>'
            ),
            ['jobs' => [[
                'projects' => [[
                    'clientName' => 'Explicit customer',
                    'customer' => 'Same-name candidate',
                ]],
            ]]]
        );

        $customer = $this->dependency($resolution, 'experience[].projects[].customer');
        self::assertSame(DependencyMappingResolution::RESOLVED, $customer->status());
        self::assertSame('jobs[].projects[].clientName', $customer->source()?->canonical());
        self::assertSame(DependencyMappingResolution::EXPLICIT, $customer->provenance());
        self::assertSame(
            'Explicit customer',
            $customer->dataResolution()?->items()[0]->items()[0]->value()
        );
    }

    public function testSameNameResolutionSupportsThreeExplicitlyMappedCollectionLevels(): void
    {
        $nestedControls = '<text:section text:name="#foreach:departments">'
            . '<text:section text:name="#foreach:teams">'
            . '<text:section text:name="#foreach:members"><text:p>{{name}}</text:p>'
            . '</text:section></text:section></text:section>';
        $contract = $this->contract($nestedControls);
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('units[]'), 'departments[]'),
            new DependencyMapping(ApplicationPath::parse('units[].teams[]'), 'departments[].teams[]'),
            new DependencyMapping(
                ApplicationPath::parse('units[].teams[].members[]'),
                'departments[].teams[].members[]'
            ),
        ]);
        $data = ['units' => [[
            'teams' => [[
                'members' => [['name' => 'Ada']],
            ]],
        ]]];

        $resolution = (new MappingResolutionResolver())->resolve($definition, $contract, $data);
        $name = $this->dependency($resolution, 'departments[].teams[].members[].name');

        self::assertSame(DependencyMappingResolution::RESOLVED, $name->status());
        self::assertSame('units[].teams[].members[].name', $name->source()?->canonical());
        self::assertSame(DependencyMappingResolution::SCOPED_SAME_NAME, $name->provenance());
        self::assertSame(
            'Ada',
            $name->dataResolution()?->items()[0]->items()[0]->items()[0]->value()
        );
    }

    public function testNativeAndDocumentTargetsResolveExplicitSourcesWithoutPayloadValidation(): void
    {
        $photo = (object) ['not' => 'validated'];
        $definition = new MappingDefinition(
            [],
            [new NativeObjectActionMapping(
                ApplicationPath::parse('person.photo'),
                'frame',
                'Portrait',
                'replace-image'
            )],
            [new DocumentCapabilityMapping(ApplicationPath::parse('person.author'), 'metadata', 'author')]
        );
        $resolution = (new MappingResolutionResolver())->resolve(
            $definition,
            $this->contract(),
            ['person' => ['photo' => $photo, 'author' => null]]
        );

        self::assertCount(1, $resolution->nativeObjectActions());
        self::assertSame('RESOLVED', $resolution->nativeObjectActions()[0]->status());
        self::assertSame('EXPLICIT', $resolution->nativeObjectActions()[0]->provenance());
        self::assertSame('person.photo', $resolution->nativeObjectActions()[0]->source()->canonical());
        self::assertSame(ApplicationDataResolution::PRESENT, $resolution->nativeObjectActions()[0]->dataResolution()->status());
        self::assertSame($photo, $resolution->nativeObjectActions()[0]->dataResolution()->value());
        self::assertCount(1, $resolution->documentCapabilities());
        self::assertSame('RESOLVED', $resolution->documentCapabilities()[0]->status());
        self::assertSame('EXPLICIT', $resolution->documentCapabilities()[0]->provenance());
        self::assertSame('metadata', $resolution->documentCapabilities()[0]->mapping()->group());
        self::assertSame('author', $resolution->documentCapabilities()[0]->mapping()->target());
        self::assertSame(ApplicationDataResolution::NULL, $resolution->documentCapabilities()[0]->dataResolution()->status());
    }

    public function testWrongShapeRemainsDataStatusOnAResolvedMapping(): void
    {
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
        ]);
        $resolution = (new MappingResolutionResolver())->resolve(
            $definition,
            $this->contract(),
            ['jobs' => 'not a list']
        );

        $experience = $this->dependency($resolution, 'experience[]');
        self::assertSame(DependencyMappingResolution::RESOLVED, $experience->status());
        self::assertSame(DependencyMappingResolution::EXPLICIT, $experience->provenance());
        self::assertSame(ApplicationDataResolution::WRONG_SHAPE, $experience->dataResolution()?->status());
    }

    public function testRejectsStaticallyInvalidDefinitionUsingExistingDiagnostics(): void
    {
        try {
            (new MappingResolutionResolver())->resolve(
                new MappingDefinition([
                    new DependencyMapping(ApplicationPath::parse('person.name'), 'unknown'),
                ]),
                $this->contract(),
                ['person' => ['name' => 'Walter']]
            );
            self::fail('A statically invalid mapping definition must not produce a resolution.');
        } catch (MappingResolutionException $exception) {
            self::assertFalse($exception->validationResult()->valid());
            self::assertSame('unknown_dependency', $exception->validationResult()->diagnostics()[0]->code());
        }
    }

    public function testDoesNotMutateDataDefinitionOrContract(): void
    {
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
        ]);
        $contract = $this->contract();
        $data = ['jobs' => [['company' => 'Before']]];
        $definitionBefore = [$definition->dependencies(), $definition->nativeObjectActions(), $definition->documentCapabilities()];
        $contractBefore = $contract->toArray();
        $dataBefore = $data;

        (new MappingResolutionResolver())->resolve($definition, $contract, $data);

        self::assertSame($dataBefore, $data);
        self::assertSame($definitionBefore, [
            $definition->dependencies(),
            $definition->nativeObjectActions(),
            $definition->documentCapabilities(),
        ]);
        self::assertSame($contractBefore, $contract->toArray());
    }

    private function dependency(\OdtTemplateEngine\Mapping\MappingResolution $resolution, string $path): DependencyMappingResolution
    {
        foreach ($resolution->dependencies() as $dependency) {
            if ($dependency->target()->path() === $path) {
                return $dependency;
            }
        }

        self::fail(sprintf('Dependency target "%s" was not resolved.', $path));
    }

    private function contract(string $additionalBody = ''): \OdtTemplateEngine\Template\TemplateContract
    {
        $this->path = MappingTemplateFixture::create($additionalBody);

        return (new OdtTemplate($this->path))->inspectTemplate();
    }
}
