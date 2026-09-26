<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Mapping\Applicability;
use OdtTemplateEngine\Mapping\EngineCapabilityCatalog;
use OdtTemplateEngine\Mapping\TemplateCapabilityProjector;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\NativeObjectDescriptor;
use OdtTemplateEngine\Tests\Support\MappingTemplateFixture;
use PHPUnit\Framework\TestCase;

final class TemplateAuthoring01E1AuthoringUxProjectionTest extends TestCase
{
    private ?string $path = null;

    protected function tearDown(): void
    {
        if ($this->path !== null && is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function testRealTemplateInspectionProjectsAuthoringCapabilitiesWithoutMutation(): void
    {
        $this->path = MappingTemplateFixture::create();
        $template = new OdtTemplate($this->path);
        $contract = $template->inspectTemplate();
        $sourceEvidenceBefore = $contract->toArray();

        $projection = (new TemplateCapabilityProjector())->project(
            $contract,
            EngineCapabilityCatalog::phaseE1()
        );

        self::assertTrue($projection->dependency('name')?->supported());
        self::assertSame('COLLECTION', $projection->dependency('experience[]')?->dependency()->kind());
        self::assertSame('experience[].company', $projection->dependency('experience[].company')?->dependency()->path());
        self::assertSame('experience[].projects[]', $projection->dependency('experience[].projects[]')?->dependency()->path());

        $profile = $projection->nativeObject('section', 'Profile');
        self::assertNotNull($profile);
        self::assertSame(Applicability::APPLICABLE, $profile->action('replace-content')?->applicability());
        self::assertSame('ODT_ELEMENT', $profile->action('replace-content')?->capability()->payloadKind());

        $signature = $projection->nativeObject('bookmark', 'Signature');
        self::assertNotNull($signature);
        self::assertSame(Applicability::APPLICABLE, $signature->action('replace-text')?->applicability());
        self::assertSame('STRING', $signature->action('replace-text')?->capability()->payloadKind());

        $portrait = $projection->nativeObject('frame', 'Portrait');
        self::assertNotNull($portrait);
        self::assertSame(Applicability::UNKNOWN, $portrait->action('replace-image')?->applicability());
        self::assertStringContainsString('does not establish', $portrait->action('replace-image')?->reason());

        $skills = $projection->nativeObject('table', 'Skills');
        self::assertNotNull($skills);
        self::assertNull($skills->action('populate'));
        self::assertSame([], array_map(
            static fn ($item): string => $item->capability()->actionId(),
            $skills->actions()
        ));
        self::assertNull($projection->nativeObject('section', 'Missing'));

        $metadataTargets = array_map(
            static fn ($capability): string => $capability->target(),
            $projection->documentCapabilities()
        );
        self::assertContains('creator', $metadataTargets);
        self::assertContains('initial_creator', $metadataTargets);
        self::assertNotContains('author', $metadataTargets);
        self::assertNotContains('initial_author', $metadataTargets);
        self::assertSame($sourceEvidenceBefore, $contract->toArray());
        self::assertSame(
            $contract->nativeObjects()[0],
            $projection->nativeObjectTargets()[0]->nativeObject()
        );
    }

    public function testUnnamedNativeObjectProjectsNotApplicableWithoutWorkingDomInspection(): void
    {
        $this->path = MappingTemplateFixture::create('<draw:frame/>');
        $contract = (new OdtTemplate($this->path))->inspectTemplate();
        $projection = (new TemplateCapabilityProjector())->project($contract, EngineCapabilityCatalog::phaseE1());

        $unnamed = array_values(array_filter(
            $projection->nativeObjectTargets(),
            static fn ($target): bool => $target->nativeObject()->kind() === 'frame'
                && $target->nativeObject()->name() === null
        ));

        self::assertCount(1, $unnamed);
        self::assertSame(Applicability::NOT_APPLICABLE, $unnamed[0]->action('replace-image')?->applicability());
    }
}
