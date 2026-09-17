<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Mapping;

use DOMDocument;
use OdtTemplateEngine\Document\DocumentInspection;
use OdtTemplateEngine\Document\DocumentInspector;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\ConcreteMappingPreflight;
use OdtTemplateEngine\Mapping\ConcretePreflightOperation;
use OdtTemplateEngine\Mapping\ConcretePreflightResult;
use OdtTemplateEngine\Mapping\DependencyMapping;
use OdtTemplateEngine\Mapping\DocumentCapabilityMapping;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\Mapping\NativeObjectActionMapping;
use OdtTemplateEngine\Mapping\ApplicationDataResolution;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Template\TemplateContractInspector;
use OdtTemplateEngine\Tests\Support\MappingTemplateFixture;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ConcreteMappingPreflightTest extends TestCase
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

    public function testCvBenchmarkIsReadyFromOneRealContractAndPreservesAllResolutionLayers(): void
    {
        $path = $this->fixture();
        [$contract, $document] = $this->inspect($path);
        $image = $this->png();
        $data = [
            'person' => [
                'name' => 'Ada Lovelace',
                'content' => new Paragraph('Profile replacement'),
                'signature' => 'Ada',
                'photo' => ['source' => $image, 'options' => []],
                'author' => 'Ada Lovelace',
            ],
            'jobs' => [[
                'employer' => 'Analytical Engines',
                'projects' => [['title' => 'Notes']],
            ]],
        ];
        $before = serialize($data);
        $contractBefore = $contract->toArray();

        $result = (new ConcreteMappingPreflight())->preflight(
            $this->cvMappings(),
            $contract,
            $data,
            $document
        );

        self::assertSame(ConcretePreflightResult::READY, $result->status());
        self::assertSame([], $result->diagnostics());
        self::assertSame($before, serialize($data));
        self::assertSame($contractBefore, $contract->toArray());
        self::assertSame('EXPLICIT', $this->operation($result, 'dependency', 'name')->resolution()->provenance());
        self::assertSame('jobs[].employer', $this->operation($result, 'dependency', 'experience[].company')->resolution()->source()->canonical());
        self::assertSame('APPLICABLE', $this->operation($result, 'native_action', 'frame:Portrait')->applicability());
        self::assertSame('ODT_ELEMENT', $this->operation($result, 'native_action', 'section:Profile')->payloadKind());
        self::assertSame('STRING', $this->operation($result, 'native_action', 'bookmark:Signature')->payloadKind());
        self::assertSame('SCALAR', $this->operation($result, 'document_capability', 'metadata.author')->payloadKind());
    }

    public function testUnresolvedMissingWrongShapeAndIncompatibleConsumersAreAllDiagnosed(): void
    {
        $path = $this->fixture(
            '<text:p>{{foo}}</text:p><text:p>{{upper:foo}}</text:p>'
        );
        [$contract, $document] = $this->inspect($path);
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('missing.name'), 'name'),
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
            new DependencyMapping(ApplicationPath::parse('customer.foo'), 'foo'),
        ]);
        $result = (new ConcreteMappingPreflight())->preflight(
            $definition,
            $contract,
            ['jobs' => 'not-a-list', 'customer' => ['foo' => ['not', 'scalar']]],
            $document
        );
        $codes = array_map(static fn ($diagnostic): string => $diagnostic->code(), $result->diagnostics());

        self::assertSame(ConcretePreflightResult::ERROR, $result->status());
        self::assertContains('UNRESOLVED_APPLICATION_SOURCE', $codes);
        self::assertContains('MISSING_SOURCE_VALUE', $codes);
        self::assertContains('WRONG_APPLICATION_SHAPE', $codes);
        self::assertContains('INCOMPATIBLE_DEPENDENCY_PAYLOAD', $codes);
        self::assertGreaterThanOrEqual(3, count($result->diagnostics()));

        $scalarFiltered = (new ConcreteMappingPreflight())->preflight(
            new MappingDefinition([new DependencyMapping(ApplicationPath::parse('customer.foo'), 'foo')]),
            $contract,
            ['customer' => ['foo' => 12]],
            $document
        );
        self::assertSame(ConcretePreflightOperation::READY, $this->operation($scalarFiltered, 'dependency', 'foo')->status());
    }

    public function testUserFieldConsumersRequireStringAndNestedCollectionFailuresKeepItemPath(): void
    {
        $path = $this->fixture(
            '<text:user-field-decl text:name="field" office:value-type="string" office:string-value=""/>'
            . '<text:user-field-get text:name="field"/>'
        );
        [$contract, $document] = $this->inspect($path);
        $preflight = new ConcreteMappingPreflight();
        $fieldMapping = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('person.field'), 'field'),
        ]);

        $field = $preflight->preflight($fieldMapping, $contract, ['person' => ['field' => 'value']], $document);
        self::assertSame(ConcretePreflightOperation::READY, $this->operation($field, 'dependency', 'field')->status());
        $nullField = $preflight->preflight($fieldMapping, $contract, ['person' => ['field' => null]], $document);
        self::assertSame(ConcretePreflightOperation::ERROR, $this->operation($nullField, 'dependency', 'field')->status());
        $typedField = $preflight->preflight($fieldMapping, $contract, ['person' => ['field' => 5]], $document);
        self::assertContains('INCOMPATIBLE_DEPENDENCY_PAYLOAD', array_map(
            static fn ($diagnostic): string => $diagnostic->code(),
            $typedField->diagnostics()
        ));

        $nestedMapping = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
            new DependencyMapping(ApplicationPath::parse('jobs[].projects[]'), 'experience[].projects[]'),
        ]);
        $nested = $preflight->preflight(
            $nestedMapping,
            $contract,
            ['jobs' => [['employer' => 'A']]],
            $document
        );
        $nestedDiagnostics = array_values(array_filter(
            $nested->diagnostics(),
            static fn ($diagnostic): bool => $diagnostic->targetIdentity() === 'experience[].projects[]'
        ));
        self::assertNotEmpty($nestedDiagnostics);
        self::assertSame('0', $nestedDiagnostics[0]->context()['item_path']);
    }

    public function testNestedNullAndWrongShapeRemainDistinctConcreteFailures(): void
    {
        $path = $this->fixture();
        [$contract, $document] = $this->inspect($path);
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
            new DependencyMapping(ApplicationPath::parse('jobs[].projects[]'), 'experience[].projects[]'),
        ]);
        $preflight = new ConcreteMappingPreflight();

        foreach ([
            [[['employer' => 'A', 'projects' => null]], 'NULL_SOURCE_VALUE'],
            [[['employer' => 'A', 'projects' => 'not-a-list']], 'WRONG_APPLICATION_SHAPE'],
        ] as [$items, $expectedCode]) {
            $result = $preflight->preflight($definition, $contract, ['jobs' => $items], $document);
            self::assertContains($expectedCode, array_map(
                static fn ($diagnostic): string => $diagnostic->code(),
                $result->diagnostics()
            ));
        }
    }

    public function testConditionNullIsReadyButMissingConditionIsAnError(): void
    {
        $path = $this->fixture('<text:p>{{#if:flag}}Visible{{#endif}}</text:p>');
        [$contract, $document] = $this->inspect($path);
        $definition = new MappingDefinition();
        $preflight = new ConcreteMappingPreflight();

        $null = $preflight->preflight($definition, $contract, ['flag' => null], $document);
        self::assertSame(ConcretePreflightOperation::READY, $this->operation($null, 'dependency', 'flag')->status());
        self::assertSame(ApplicationDataResolution::NULL, $this->operation($null, 'dependency', 'flag')->resolution()->dataResolution()->status());

        $missing = $preflight->preflight($definition, $contract, [], $document);
        self::assertSame(ConcretePreflightOperation::ERROR, $this->operation($missing, 'dependency', 'flag')->status());
        self::assertContains('MISSING_SOURCE_VALUE', array_map(
            static fn ($diagnostic): string => $diagnostic->code(),
            $missing->diagnostics()
        ));
    }

    public function testForeachEmptyCollectionIsReadyButItemsMustBeNamedRecords(): void
    {
        $path = $this->fixture();
        [$contract, $document] = $this->inspect($path);
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
        ]);
        $preflight = new ConcreteMappingPreflight();

        $empty = $preflight->preflight($definition, $contract, ['jobs' => []], $document);
        self::assertSame(ConcretePreflightOperation::READY, $this->operation($empty, 'dependency', 'experience[]')->status());

        foreach ([[null], ['scalar'], [[0 => 'positional']]] as $items) {
            $result = $preflight->preflight($definition, $contract, ['jobs' => $items], $document);
            self::assertContains('INVALID_COLLECTION_ITEM_RECORD', array_map(
                static fn ($diagnostic): string => $diagnostic->code(),
                $result->diagnostics()
            ));
        }
        $emptyRecord = $preflight->preflight($definition, $contract, ['jobs' => [[]]], $document);
        self::assertSame(ConcretePreflightOperation::READY, $this->operation($emptyRecord, 'dependency', 'experience[]')->status());

        foreach ([
            [[], 'MISSING_SOURCE_VALUE'],
            [['jobs' => null], 'NULL_SOURCE_VALUE'],
            [['jobs' => 'scalar'], 'WRONG_APPLICATION_SHAPE'],
        ] as [$data, $expectedCode]) {
            $invalid = $preflight->preflight($definition, $contract, $data, $document);
            self::assertContains($expectedCode, array_map(
                static fn ($diagnostic): string => $diagnostic->code(),
                $invalid->diagnostics()
            ));
        }
    }

    public function testSectionBookmarkAndMetadataPayloadBoundariesAreStrict(): void
    {
        $path = $this->fixture();
        [$contract, $document] = $this->inspect($path);
        $definition = new MappingDefinition(
            [],
            [
                new NativeObjectActionMapping(ApplicationPath::parse('section.content'), 'section', 'Profile', 'replace-content'),
                new NativeObjectActionMapping(ApplicationPath::parse('sign.text'), 'bookmark', 'Signature', 'replace-text'),
            ],
            [new DocumentCapabilityMapping(ApplicationPath::parse('meta.author'), 'metadata', 'author')]
        );
        $result = (new ConcreteMappingPreflight())->preflight(
            $definition,
            $contract,
            ['section' => ['content' => 'not-an-element'], 'sign' => ['text' => 42], 'meta' => ['author' => null]],
            $document
        );
        $codes = array_map(static fn ($diagnostic): string => $diagnostic->code(), $result->diagnostics());
        self::assertContains('INCOMPATIBLE_NATIVE_ACTION_PAYLOAD', $codes);
        self::assertContains('NULL_SOURCE_VALUE', $codes);

        foreach (['ok', 4, 2.5, false] as $value) {
            $ready = (new ConcreteMappingPreflight())->preflight(
                new MappingDefinition([], [], [new DocumentCapabilityMapping(ApplicationPath::parse('meta.author'), 'metadata', 'author')]),
                $contract,
                ['meta' => ['author' => $value]],
                $document
            );
            self::assertSame(ConcretePreflightOperation::READY, $this->operation($ready, 'document_capability', 'metadata.author')->status());
        }
        foreach ([[], new \stdClass()] as $value) {
            $invalid = (new ConcreteMappingPreflight())->preflight(
                new MappingDefinition([], [], [new DocumentCapabilityMapping(ApplicationPath::parse('meta.author'), 'metadata', 'author')]),
                $contract,
                ['meta' => ['author' => $value]],
                $document
            );
            self::assertContains('INCOMPATIBLE_DOCUMENT_CAPABILITY_PAYLOAD', array_map(
                static fn ($diagnostic): string => $diagnostic->code(),
                $invalid->diagnostics()
            ));
        }
    }

    public function testImagePayloadAndConcreteFrameApplicabilityAreCheckedWithoutMutation(): void
    {
        $path = $this->fixture('<draw:frame draw:name="EmptyFrame"/>');
        [$contract, $document] = $this->inspect($path);
        $image = $this->png();
        $definition = new MappingDefinition([], [
            new NativeObjectActionMapping(ApplicationPath::parse('person.photo'), 'frame', 'Portrait', 'replace-image'),
        ]);
        $data = ['person' => ['photo' => ['source' => $image, 'options' => ['width' => '4cm']]]];
        $before = serialize($data);

        $ready = (new ConcreteMappingPreflight())->preflight($definition, $contract, $data, $document);
        self::assertSame(ConcretePreflightOperation::READY, $this->operation($ready, 'native_action', 'frame:Portrait')->status());
        self::assertSame($before, serialize($data));

        $invalidDefinition = new MappingDefinition([], [
            new NativeObjectActionMapping(ApplicationPath::parse('person.photo'), 'frame', 'EmptyFrame', 'replace-image'),
        ]);
        $invalid = (new ConcreteMappingPreflight())->preflight(
            $invalidDefinition,
            $contract,
            ['person' => ['photo' => ['source' => $image, 'options' => ['keepRatio' => true]]]],
            $document
        );
        $codes = array_map(static fn ($diagnostic): string => $diagnostic->code(), $invalid->diagnostics());
        self::assertContains('INVALID_REPLACEMENT_OPTION', $codes);
        self::assertContains('ACTION_NOT_APPLICABLE', $codes);

        $badSource = (new ConcreteMappingPreflight())->preflight(
            $definition,
            $contract,
            ['person' => ['photo' => ['source' => $image . '.missing']]],
            $document
        );
        self::assertContains('INVALID_IMAGE_SOURCE', array_map(
            static fn ($diagnostic): string => $diagnostic->code(),
            $badSource->diagnostics()
        ));
    }

    private function cvMappings(): MappingDefinition
    {
        return new MappingDefinition(
            [
                new DependencyMapping(ApplicationPath::parse('person.name'), 'name'),
                new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
                new DependencyMapping(ApplicationPath::parse('jobs[].employer'), 'experience[].company'),
                new DependencyMapping(ApplicationPath::parse('jobs[].projects[]'), 'experience[].projects[]'),
                new DependencyMapping(ApplicationPath::parse('jobs[].projects[].title'), 'experience[].projects[].title'),
            ],
            [
                new NativeObjectActionMapping(ApplicationPath::parse('person.content'), 'section', 'Profile', 'replace-content'),
                new NativeObjectActionMapping(ApplicationPath::parse('person.signature'), 'bookmark', 'Signature', 'replace-text'),
                new NativeObjectActionMapping(ApplicationPath::parse('person.photo'), 'frame', 'Portrait', 'replace-image'),
            ],
            [new DocumentCapabilityMapping(ApplicationPath::parse('person.author'), 'metadata', 'author')]
        );
    }

    private function operation($result, string $family, string $identity): ConcretePreflightOperation
    {
        foreach ($result->operations() as $operation) {
            if ($operation->targetFamily() === $family && $operation->targetIdentity() === $identity) {
                return $operation;
            }
        }
        self::fail(sprintf('Missing preflight operation %s:%s.', $family, $identity));
    }

    private function fixture(string $additionalBody = ''): string
    {
        $path = MappingTemplateFixture::create($additionalBody);
        $this->paths[] = $path;
        return $path;
    }

    /** @return array{TemplateContract, DocumentInspection} */
    private function inspect(string $path): array
    {
        $zip = new ZipArchive();
        self::assertSame(true, $zip->open($path));
        $content = $this->dom((string) $zip->getFromName('content.xml'));
        $styles = $this->dom((string) $zip->getFromName('styles.xml'));
        $zip->close();
        return [
            (new TemplateContractInspector())->inspect($content, $styles),
            (new DocumentInspector())->inspect($content, $styles),
        ];
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($xml, LIBXML_NONET));
        return $dom;
    }

    private function png(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'phase-e-image-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.png';
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p0sAAAAASUVORK5CYII=',
            true
        ));
        $this->paths[] = $path;
        return $path;
    }
}
