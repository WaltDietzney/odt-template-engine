<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Mapping;

use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\DependencyMapping;
use OdtTemplateEngine\Mapping\DocumentCapabilityMapping;
use OdtTemplateEngine\Mapping\EngineCapabilityCatalog;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\Mapping\NativeObjectActionMapping;
use OdtTemplateEngine\Mapping\StaticMappingValidator;
use OdtTemplateEngine\Mapping\TemplateCapabilityProjector;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateContractCapabilities;
use OdtTemplateEngine\Tests\Support\MappingTemplateFixture;
use PHPUnit\Framework\TestCase;

final class StaticMappingValidatorTest extends TestCase
{
    private ?string $path = null;

    protected function tearDown(): void
    {
        if ($this->path !== null && is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function testValidRootAndNestedCollectionMappingsAndAllTargetFamilies(): void
    {
        [$contract, $projection, $catalog] = $this->model();
        $definition = new MappingDefinition(
            [
                new DependencyMapping(ApplicationPath::parse('person.name'), 'name'),
                new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[]'),
                new DependencyMapping(ApplicationPath::parse('jobs[].employer'), 'experience[].company'),
                new DependencyMapping(ApplicationPath::parse('jobs[].projects[]'), 'experience[].projects[]'),
                new DependencyMapping(
                    ApplicationPath::parse('jobs[].projects[].title'),
                    'experience[].projects[].title'
                ),
            ],
            [
                new NativeObjectActionMapping(ApplicationPath::parse('profile.content'), 'section', 'Profile', 'replace-content'),
                new NativeObjectActionMapping(ApplicationPath::parse('signature'), 'bookmark', 'Signature', 'replace-text'),
                new NativeObjectActionMapping(ApplicationPath::parse('person.photo'), 'frame', 'Portrait', 'replace-image'),
            ],
            [new DocumentCapabilityMapping(ApplicationPath::parse('person.author'), 'metadata', 'creator')]
        );

        $result = (new StaticMappingValidator())->validate($definition, $contract, $projection, $catalog);

        self::assertTrue($result->valid(), implode('; ', array_map(
            static fn ($diagnostic): string => $diagnostic->code() . ': ' . $diagnostic->message(),
            $result->diagnostics()
        )));
        self::assertCount(1, $result->deferredChecks());
        self::assertSame('native_action_applicability_unknown', $result->deferredChecks()[0]->code());
        self::assertSame('STRING', $projection->documentCapability('metadata', 'creator')?->payloadKind());
        self::assertSame('LIST<STRING>', $projection->documentCapability('metadata', 'keywords')?->payloadKind());
    }

    public function testOneApplicationSourceMayExplicitlyFeedDifferentTargetFamilies(): void
    {
        [$contract, $projection, $catalog] = $this->model();
        $source = ApplicationPath::parse('person.name');
        $definition = new MappingDefinition(
            [new DependencyMapping($source, 'name')],
            [new NativeObjectActionMapping($source, 'bookmark', 'Signature', 'replace-text')],
            [new DocumentCapabilityMapping($source, 'metadata', 'creator')]
        );

        $result = (new StaticMappingValidator())->validate($definition, $contract, $projection, $catalog);

        self::assertTrue($result->valid());
        self::assertSame([], $result->diagnostics());
    }

    public function testLimitedTemplateReadinessRemainsUnknownAndIsDeferred(): void
    {
        [$contract, $projection, $catalog] = $this->model('<text:p>{{foo bar}}</text:p>');
        self::assertSame(
            TemplateContractCapabilities::LIMITED,
            $contract->capabilities()->readiness('dependency_mapping')
        );

        $result = (new StaticMappingValidator())->validate(
            new MappingDefinition([new DependencyMapping(ApplicationPath::parse('person.name'), 'name')]),
            $contract,
            $projection,
            $catalog
        );

        self::assertTrue($result->valid());
        self::assertSame('dependency_mapping_readiness_unknown', $result->deferredChecks()[0]->code());
    }

    public function testReportsUnknownDependencyValueCollectionMismatchAndInvalidNestedScope(): void
    {
        [$contract, $projection, $catalog] = $this->model();
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('person.missing'), 'missing'),
            new DependencyMapping(ApplicationPath::parse('jobs[]'), 'experience[].company'),
            new DependencyMapping(ApplicationPath::parse('orders[].employer'), 'experience[].company'),
        ]);

        $result = (new StaticMappingValidator())->validate($definition, $contract, $projection, $catalog);
        $codes = array_map(static fn ($diagnostic): string => $diagnostic->code(), $result->diagnostics());

        self::assertFalse($result->valid());
        self::assertContains('unknown_dependency', $codes);
        self::assertContains('dependency_value_collection_mismatch', $codes);
        self::assertContains('invalid_collection_scope_relationship', $codes);
    }

    public function testValidSameSourceCanFeedDependencyAndMetadataAndConflictingSourcesAreReported(): void
    {
        [$contract, $projection, $catalog] = $this->model();
        $definition = new MappingDefinition(
            [
                new DependencyMapping(ApplicationPath::parse('person.name'), 'name'),
                new DependencyMapping(ApplicationPath::parse('account.name'), 'name'),
            ],
            [
                new NativeObjectActionMapping(ApplicationPath::parse('photo.main'), 'frame', 'Portrait', 'replace-image'),
                new NativeObjectActionMapping(ApplicationPath::parse('photo.backup'), 'frame', 'Portrait', 'replace-image'),
            ],
            [
                new DocumentCapabilityMapping(ApplicationPath::parse('person.name'), 'metadata', 'creator'),
                new DocumentCapabilityMapping(ApplicationPath::parse('account.name'), 'metadata', 'creator'),
            ]
        );

        $result = (new StaticMappingValidator())->validate($definition, $contract, $projection, $catalog);
        $codes = array_map(static fn ($diagnostic): string => $diagnostic->code(), $result->diagnostics());

        self::assertFalse($result->valid());
        self::assertContains('duplicate_dependency_target', $codes);
        self::assertContains('duplicate_native_action_target', $codes);
        self::assertContains('duplicate_document_capability_target', $codes);
        self::assertCount(2, $result->deferredChecks());
    }

    public function testNativeValidationDistinguishesUnknownKindUnsupportedAndUnknownApplicability(): void
    {
        [$contract, $projection, $catalog] = $this->model();
        $definition = new MappingDefinition([], [
            new NativeObjectActionMapping(ApplicationPath::parse('a'), 'section', 'Absent', 'replace-content'),
            new NativeObjectActionMapping(ApplicationPath::parse('b'), 'section', 'Portrait', 'replace-content'),
            new NativeObjectActionMapping(ApplicationPath::parse('c'), 'table', 'Skills', 'populate'),
            new NativeObjectActionMapping(ApplicationPath::parse('d'), 'table', 'Skills', 'replace-image'),
            new NativeObjectActionMapping(ApplicationPath::parse('e'), 'frame', 'Portrait', 'replace-image'),
        ]);

        $result = (new StaticMappingValidator())->validate($definition, $contract, $projection, $catalog);
        $codes = array_map(static fn ($diagnostic): string => $diagnostic->code(), $result->diagnostics());

        self::assertContains('unknown_native_object', $codes);
        self::assertContains('native_object_kind_mismatch', $codes);
        self::assertContains('unsupported_native_action', $codes);
        self::assertContains('unsupported_native_action_for_kind', $codes);
        self::assertSame(['native_action_applicability_unknown'], array_map(
            static fn ($check): string => $check->code(),
            $result->deferredChecks()
        ));
    }

    public function testMetadataTargetsAreStrictAndMultipleIndependentErrorsAreCollected(): void
    {
        [$contract, $projection, $catalog] = $this->model();
        $definition = new MappingDefinition(
            [new DependencyMapping(ApplicationPath::parse('x'), 'not_a_dependency')],
            [new NativeObjectActionMapping(ApplicationPath::parse('x'), 'frame', 'Portrait', 'unknown-action')],
            [
                new DocumentCapabilityMapping(ApplicationPath::parse('x'), 'metadata', 'author'),
                new DocumentCapabilityMapping(ApplicationPath::parse('y'), 'metadata', 'fictional_field'),
            ]
        );

        $result = (new StaticMappingValidator())->validate($definition, $contract, $projection, $catalog);
        $codes = array_map(static fn ($diagnostic): string => $diagnostic->code(), $result->diagnostics());

        self::assertGreaterThanOrEqual(3, count($codes));
        self::assertContains('unknown_dependency', $codes);
        self::assertContains('unsupported_native_action', $codes);
        self::assertContains('unsupported_document_capability', $codes);
    }

    /** @return array{\OdtTemplateEngine\Template\TemplateContract, \OdtTemplateEngine\Mapping\TemplateCapabilityProjection, EngineCapabilityCatalog} */
    private function model(string $additionalBody = ''): array
    {
        $this->path = MappingTemplateFixture::create($additionalBody);
        $contract = (new OdtTemplate($this->path))->inspectTemplate();
        $catalog = EngineCapabilityCatalog::phaseE1();

        return [$contract, (new TemplateCapabilityProjector())->project($contract, $catalog), $catalog];
    }
}
