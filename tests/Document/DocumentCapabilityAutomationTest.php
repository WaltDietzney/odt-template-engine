<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use LogicException;
use OdtTemplateEngine\Document\DocumentCapabilityAutomationExecutor;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\ConcreteMappingPreflight;
use OdtTemplateEngine\Mapping\ConcretePreflightOperation;
use OdtTemplateEngine\Mapping\ConcretePreflightResult;
use OdtTemplateEngine\Mapping\DependencyMapping;
use OdtTemplateEngine\Mapping\DocumentCapabilityMapping;
use OdtTemplateEngine\Mapping\DocumentCapabilityResolution;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\Mapping\NativeObjectActionMapping;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Template\TemplateContractInspector;
use OdtTemplateEngine\Tests\Support\MappingTemplateFixture;
use PHPUnit\Framework\TestCase;

final class DocumentCapabilityAutomationTest extends TestCase
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

    public function testMultipleReadyTargetsAreAppliedTogetherAndSurviveSaveReload(): void
    {
        $template = $this->template();
        $values = [
            'creator' => 'Phase E Creator',
            'initial_creator' => 'Original Creator',
            'keywords' => ['finance', 'report', '2026'],
            'coverage' => 'Europe, 2026',
        ];
        $mappings = $this->metadataMappings(array_keys($values));
        $preflight = $this->preflight($template, $mappings, $values);
        self::assertTrue($preflight->ready());

        $contentBefore = $template->partXml('content.xml');
        $stylesBefore = $template->partXml('styles.xml');
        $output = $this->newPath('e5-roundtrip');
        self::assertFileDoesNotExist($output);

        $template->automateDocumentCapabilities($preflight);

        self::assertSame($contentBefore, $template->partXml('content.xml'));
        self::assertSame($stylesBefore, $template->partXml('styles.xml'));
        self::assertFileDoesNotExist($output, 'E5 must not save implicitly.');
        self::assertSame('Phase E Creator', $template->getMeta()['creator'] ?? null);
        self::assertSame('Original Creator', $template->getMeta()['initial_creator'] ?? null);
        self::assertSame(['finance', 'report', '2026'], $template->getMeta()['keywords'] ?? null);
        self::assertSame('Europe, 2026', $template->getMeta()['coverage'] ?? null);

        $template->save($output);
        $reopened = new OdtTemplate($output);
        self::assertSame('Phase E Creator', $reopened->getMeta()['creator'] ?? null);
        self::assertSame('Original Creator', $reopened->getMeta()['initial_creator'] ?? null);
        self::assertSame(['finance', 'report', '2026'], $reopened->getMeta()['keywords'] ?? null);
        self::assertSame('Europe, 2026', $reopened->getMeta()['coverage'] ?? null);
    }

    public function testAllApprovedPayloadKindsArePassedThroughMetadataOwner(): void
    {
        $template = $this->template();
        $values = [
            'title' => 'Title',
            'subject' => 'Subject',
            'description' => 'Description',
            'keywords' => ['one', 'two'],
            'initial_creator' => 'Initial',
            'creator' => 'Creator',
            'language' => 'en-US',
            'creation_date' => '2026-09-18T10:30:00Z',
            'date' => '2026-09-18T10:31:00+02:00',
            'editing_cycles' => 0,
            'editing_duration' => 'P1DT2H',
            'generator' => 'E5 test',
            'coverage' => 'World',
        ];
        $preflight = $this->preflight($template, $this->metadataMappings(array_keys($values)), $values);
        self::assertTrue($preflight->ready());
        foreach ($preflight->operations() as $operation) {
            if ($operation->targetFamily() === 'document_capability') {
                self::assertSame(ConcretePreflightOperation::READY, $operation->status());
            }
        }

        $template->automateDocumentCapabilities($preflight);
        $metadata = $template->getMeta();
        self::assertSame('Title', $metadata['title'] ?? null);
        self::assertSame('Subject', $metadata['subject'] ?? null);
        self::assertSame('Description', $metadata['description'] ?? null);
        self::assertSame(['one', 'two'], $metadata['keywords'] ?? null);
        self::assertSame('Initial', $metadata['initial_creator'] ?? null);
        self::assertSame('Creator', $metadata['creator'] ?? null);
        self::assertSame('en-US', $metadata['language'] ?? null);
        self::assertSame('2026-09-18T10:30:00Z', $metadata['creation_date'] ?? null);
        self::assertSame('2026-09-18T10:31:00+02:00', $metadata['date'] ?? null);
        self::assertSame('0', $metadata['editing_cycles'] ?? null);
        self::assertSame('P1DT2H', $metadata['editing_duration'] ?? null);
        self::assertSame('E5 test', $metadata['generator'] ?? null);
        self::assertSame('World', $metadata['coverage'] ?? null);
    }

    public function testNonReadyMixedPreflightDoesNotMutateAnyMetadata(): void
    {
        $template = $this->template();
        $preflight = $this->preflight(
            $template,
            [new DocumentCapabilityMapping(ApplicationPath::parse('data.absent'), 'metadata', 'creator')],
            [],
            [new NativeObjectActionMapping(ApplicationPath::parse('data.content'), 'section', 'Profile', 'replace-content')],
            ['content' => new Paragraph('Must not execute')]
        );
        self::assertFalse($preflight->ready());
        $metadataBefore = $template->getMeta();
        $contentBefore = $template->partXml('content.xml');

        try {
            $template->automateDocumentCapabilities($preflight);
            self::fail('A non-READY mixed preflight must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('READY', $exception->getMessage());
        }

        self::assertSame($metadataBefore, $template->getMeta());
        self::assertSame($contentBefore, $template->partXml('content.xml'));
    }

    public function testE5IgnoresReadyDependencyAndNativeActionOperations(): void
    {
        $template = $this->template();
        $preflight = $this->preflight(
            $template,
            [new DocumentCapabilityMapping(ApplicationPath::parse('data.creator'), 'metadata', 'creator')],
            ['creator' => 'Automated Creator'],
            [new NativeObjectActionMapping(ApplicationPath::parse('data.content'), 'section', 'Profile', 'replace-content')],
            ['content' => new Paragraph('Not E5')]
        );
        self::assertTrue($preflight->ready());
        $contentBefore = $template->partXml('content.xml');

        $template->automateDocumentCapabilities($preflight);

        self::assertSame('Automated Creator', $template->getMeta()['creator'] ?? null);
        self::assertSame($contentBefore, $template->partXml('content.xml'));
    }

    public function testContradictoryReadyOperationEvidenceFailsBeforeMutation(): void
    {
        $template = $this->template();
        $preflight = $this->preflight(
            $template,
            [new DocumentCapabilityMapping(ApplicationPath::parse('data.creator'), 'metadata', 'creator')],
            ['creator' => 'Must not be written']
        );
        $operations = $preflight->operations();
        foreach ($operations as $index => $operation) {
            if ($operation->targetFamily() === 'document_capability') {
                $operations[$index] = new ConcretePreflightOperation(
                    'document_capability',
                    'metadata.coverage',
                    $operation->resolution(),
                    'metadata.coverage',
                    $operation->payloadKind(),
                    $operation->applicability(),
                    ConcretePreflightOperation::READY,
                    []
                );
            }
        }
        $forged = new ConcretePreflightResult($preflight->mappingResolution(), $operations);
        $metadataBefore = $template->getMeta();

        try {
            $template->automateDocumentCapabilities($forged);
            self::fail('Contradictory operation identity must be rejected.');
        } catch (LogicException $exception) {
            self::assertStringContainsString('disagrees', $exception->getMessage());
        }

        self::assertSame($metadataBefore, $template->getMeta());
    }

    public function testResolutionNotContainedInPreflightMappingResolutionIsRejected(): void
    {
        $template = $this->template();
        $preflight = $this->preflight(
            $template,
            [new DocumentCapabilityMapping(ApplicationPath::parse('data.creator'), 'metadata', 'creator')],
            ['creator' => 'Must not be written']
        );
        $original = $this->documentOperation($preflight);
        $foreign = new DocumentCapabilityResolution(
            new DocumentCapabilityMapping(ApplicationPath::parse('data.creator'), 'metadata', 'coverage'),
            $original->resolution()->dataResolution()
        );
        $operations = $preflight->operations();
        foreach ($operations as $index => $operation) {
            if ($operation->targetFamily() === 'document_capability') {
                $operations[$index] = new ConcretePreflightOperation(
                    'document_capability',
                    'metadata.coverage',
                    $foreign,
                    'metadata.coverage',
                    'STRING',
                    'APPLICABLE',
                    ConcretePreflightOperation::READY,
                    []
                );
            }
        }
        $forged = new ConcretePreflightResult($preflight->mappingResolution(), $operations);
        $metadataBefore = $template->getMeta();

        $this->expectException(LogicException::class);
        try {
            $template->automateDocumentCapabilities($forged);
        } finally {
            self::assertSame($metadataBefore, $template->getMeta());
        }
    }

    public function testDuplicateMetadataTargetIsRejectedBeforeMutation(): void
    {
        $template = $this->template();
        $sameMapping = new DocumentCapabilityMapping(ApplicationPath::parse('data.creator'), 'metadata', 'creator');
        $preflight = $this->preflight(
            $template,
            [$sameMapping, $sameMapping],
            ['creator' => 'Conflicting repetition']
        );
        self::assertTrue($preflight->ready(), 'Identical-source duplicates pass current static conflict validation.');
        $metadataBefore = $template->getMeta();

        try {
            $template->automateDocumentCapabilities($preflight);
            self::fail('Repeated target operations must not be applied by ordering.');
        } catch (LogicException $exception) {
            self::assertStringContainsString('Multiple READY operations', $exception->getMessage());
        }

        self::assertSame($metadataBefore, $template->getMeta());
    }

    public function testImperativeMetadataAliasesRemainAvailableAlongsideE5(): void
    {
        $template = $this->template();
        $template->setMeta(['author' => 'Legacy author', 'initial_author' => 'Legacy origin']);
        self::assertSame('Legacy author', $template->getMeta()['creator'] ?? null);
        self::assertSame('Legacy author', $template->getMeta()['author'] ?? null);
        self::assertSame('Legacy origin', $template->getMeta()['initial_creator'] ?? null);
        self::assertSame('Legacy origin', $template->getMeta()['initial_author'] ?? null);
    }

    /** @param list<string> $targets @return list<DocumentCapabilityMapping> */
    private function metadataMappings(array $targets): array
    {
        return array_map(
            static fn (string $target): DocumentCapabilityMapping => new DocumentCapabilityMapping(
                ApplicationPath::parse('data.' . $target),
                'metadata',
                $target
            ),
            $targets
        );
    }

    /**
     * @param list<DocumentCapabilityMapping> $documentMappings
     * @param array<string,mixed> $metadataValues
     * @param list<NativeObjectActionMapping> $nativeMappings
     * @param array<string,mixed> $nativeValues
     */
    private function preflight(
        InspectableE5Template $template,
        array $documentMappings,
        array $metadataValues,
        array $nativeMappings = [],
        array $nativeValues = []
    ): ConcretePreflightResult {
        $contract = $template->inspectTemplate();
        $data = ['data' => array_merge(
            ['name' => 'unchanged dependency', 'experience' => []],
            $metadataValues,
            $nativeValues
        )];
        $definition = new MappingDefinition(
            [
                new DependencyMapping(ApplicationPath::parse('data.name'), 'name'),
                new DependencyMapping(ApplicationPath::parse('data.experience[]'), 'experience[]'),
            ],
            $nativeMappings,
            $documentMappings
        );

        return (new ConcreteMappingPreflight())->preflight($definition, $contract, $data, $template->inspect());
    }

    private function documentOperation(ConcretePreflightResult $preflight): ConcretePreflightOperation
    {
        foreach ($preflight->operations() as $operation) {
            if ($operation->targetFamily() === 'document_capability') {
                return $operation;
            }
        }

        throw new LogicException('Test preflight has no document capability operation.');
    }

    private function template(): InspectableE5Template
    {
        $path = MappingTemplateFixture::create('', '<text:p>Experience template</text:p>');
        $this->paths[] = $path;

        return new InspectableE5Template($path);
    }

    private function newPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/odt-e5-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->paths[] = $path;
        return $path;
    }
}

/** Exposes read-only snapshots of the parts for mutation-boundary assertions. */
final class InspectableE5Template extends OdtTemplate
{
    public function partXml(string $part): string
    {
        $document = match ($part) {
            'content.xml' => $this->documentContext()->contentDom(),
            'styles.xml' => $this->documentContext()->stylesDom(),
            default => throw new \InvalidArgumentException('Unsupported test part.'),
        };

        return $document->saveXML() ?: '';
    }
}
