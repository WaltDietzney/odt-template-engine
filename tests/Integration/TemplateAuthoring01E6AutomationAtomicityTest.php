<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Document\FillImageRequirement;
use OdtTemplateEngine\Document\FontFaceRequirement;
use OdtTemplateEngine\Document\PhaseEAutomationRollbackException;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\ConcreteMappingPreflight;
use OdtTemplateEngine\Mapping\DocumentCapabilityMapping;
use OdtTemplateEngine\Mapping\DependencyMapping;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\Mapping\NativeObjectActionMapping;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Tests\Support\MappingTemplateFixture;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01E6AutomationAtomicityTest extends TestCase
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

    public function testRollbackFailureRetainsBothExecutionAndRollbackCauses(): void
    {
        $execution = new \RuntimeException('execution failed');
        $rollback = new \RuntimeException('restore failed');
        $combined = new PhaseEAutomationRollbackException($execution, $rollback);

        self::assertSame($execution, $combined->executionFailure());
        self::assertSame($rollback, $combined->rollbackFailure());
        self::assertSame($execution, $combined->getPrevious());
    }

    public function testCommonInvocationExecutesAllFamiliesAndSurvivesSaveReload(): void
    {
        $template = new E6InspectableTemplate($this->fixture());
        $contract = $template->inspectTemplate();
        $preflight = $this->readyPreflight($template, $contract);
        self::assertTrue($preflight->ready(), json_encode(array_map(
            static fn ($diagnostic): array => [$diagnostic->code(), $diagnostic->message(), $diagnostic->targetIdentity()],
            $preflight->diagnostics()
        )));

        $template->automate($contract, $preflight);

        self::assertSame(0, $template->renderCalls);
        self::assertSame(0, $template->saveCalls);
        self::assertStringContainsString('Phase E name', $template->workingContent()->textContent);
        self::assertStringContainsString('Phase E headline', $template->workingStyles()->textContent);
        self::assertStringContainsString('Structured profile', $template->workingContent()->textContent);
        self::assertSame(0, $this->countSectionsNamed($template->workingContent(), '#foreach:experience'));
        self::assertSame('Pictures/' . basename($this->imagePath()), $this->frameHref($template->workingContent()));
        self::assertSame('E6 creator', $template->getMeta()['creator']);

        $output = $this->temporaryPath('.odt');
        self::assertFileDoesNotExist($output);
        $template->save($output);
        self::assertSame(1, $template->saveCalls);
        $zip = new ZipArchive();
        self::assertSame(true, $zip->open($output));
        try {
            self::assertNotFalse($zip->locateName('Pictures/' . basename($this->imagePath())));
            self::assertNotFalse($zip->locateName('Pictures/template-existing.bin'));
            self::assertNotFalse($zip->getFromName('styles.xml'));
            self::assertNotFalse($zip->getFromName('meta.xml'));
        } finally {
            $zip->close();
        }

        $reopened = new OdtTemplate($output);
        $savedZip = new ZipArchive();
        self::assertSame(true, $savedZip->open($output));
        try {
            self::assertStringContainsString('Phase E name', $savedZip->getFromName('content.xml'));
            self::assertStringContainsString('Phase E headline', $savedZip->getFromName('styles.xml'));
        } finally {
            $savedZip->close();
        }
        self::assertSame('E6 creator', $reopened->getMeta()['creator']);
    }

    public function testFailureAfterDependencyAndNativeMutationsRestoresEveryWorkingPartAndAllowsRetry(): void
    {
        $template = new E6InspectableTemplate($this->fixture());
        $template->setMeta(['subject' => 'Prior imperative value']);
        $template->styles()->defineParagraph('PriorStyle', ['margin-top' => '0.2cm']);
        $template->registerPriorRequirements();
        $contract = $template->inspectTemplate();
        $preflight = $this->readyPreflight($template, $contract);
        $beforeContent = $this->semanticDom($template->workingContent()->documentElement);
        $beforeStyles = $this->semanticDom($template->workingStyles()->documentElement);
        $beforeMeta = $this->semanticDom($template->workingMeta()->documentElement);
        $beforeDefinitions = $template->styleDefinitions();
        $beforeFonts = $template->fontRequirements();
        $beforeFillImages = $template->fillImageRequirements();

        $lateFailure = new \RuntimeException('late E5 failure');
        $template->documentCapabilityFailure = $lateFailure;
        $template->mutateTransientDocumentState = true;
        try {
            $template->automate($contract, $preflight);
            self::fail('Expected the injected late E5 failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame($lateFailure, $exception);
            self::assertSame('late E5 failure', $exception->getMessage());
        }

        self::assertSame($beforeContent, $this->semanticDom($template->workingContent()->documentElement));
        self::assertSame($beforeStyles, $this->semanticDom($template->workingStyles()->documentElement));
        self::assertSame($beforeMeta, $this->semanticDom($template->workingMeta()->documentElement));
        self::assertSame($beforeDefinitions, $template->styleDefinitions());
        self::assertSame($beforeFonts, $template->fontRequirements());
        self::assertSame($beforeFillImages, $template->fillImageRequirements());
        self::assertSame('Prior imperative value', $template->getMeta()['subject']);

        $output = $this->temporaryPath('.odt');
        $template->save($output);
        $zip = new ZipArchive();
        self::assertSame(true, $zip->open($output));
        try {
            self::assertFalse($zip->locateName('Pictures/' . basename($this->imagePath())) !== false);
            self::assertNotFalse($zip->locateName('Pictures/template-existing.bin'));
        } finally {
            $zip->close();
        }

        $template->documentCapabilityFailure = null;
        $template->mutateTransientDocumentState = false;
        $template->automate($contract, $preflight);
        self::assertStringContainsString('Phase E name', $template->workingContent()->textContent);
        self::assertSame('E6 creator', $template->getMeta()['creator']);
    }

    public function testNonReadyPreflightIsRejectedWithoutMutationAndSuccessfulInvocationIsSingleUse(): void
    {
        $template = new E6InspectableTemplate($this->fixture());
        $contract = $template->inspectTemplate();
        $bad = (new ConcreteMappingPreflight())->preflight(
            new MappingDefinition(),
            $contract,
            ['name' => null, 'headline' => 'Headline'],
            $template->inspect()
        );
        self::assertFalse($bad->ready());
        $before = $template->workingContent()->C14N();
        try {
            $template->automate($contract, $bad);
            self::fail('Expected non-READY preflight rejection.');
        } catch (\InvalidArgumentException) {
            self::assertSame($before, $template->workingContent()->C14N());
        }

        $ready = $this->readyPreflight($template, $contract);
        $template->automate($contract, $ready);
        try {
            $template->automate($contract, $ready);
            self::fail('Expected a second successful invocation to be rejected.');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('already ran', $exception->getMessage());
        }

        $template->setMeta(['description' => 'Imperative after automation']);
        self::assertSame('Imperative after automation', $template->getMeta()['description']);
        $template->automateDocumentCapabilities($ready);
        self::assertSame('E6 creator', $template->getMeta()['creator']);
        $output = $this->temporaryPath('.odt');
        $template->save($output);
        self::assertSame('Imperative after automation', (new OdtTemplate($output))->getMeta()['description']);

        $template->load();
        $template->automate($contract, $ready);
        self::assertSame('E6 creator', $template->getMeta()['creator']);
    }

    private function readyPreflight(E6InspectableTemplate $template, TemplateContract $contract): \OdtTemplateEngine\Mapping\ConcretePreflightResult
    {
        $definition = new MappingDefinition([
            new DependencyMapping(ApplicationPath::parse('experience[]'), 'experience[]'),
        ], [
            new NativeObjectActionMapping(ApplicationPath::parse('profile.content'), 'section', 'Profile', 'replace-content'),
            new NativeObjectActionMapping(ApplicationPath::parse('photo'), 'frame', 'Portrait', 'replace-image'),
        ], [
            new DocumentCapabilityMapping(ApplicationPath::parse('metadata.creator'), 'metadata', 'creator'),
        ]);
        try {
            return (new ConcreteMappingPreflight())->preflight(
                $definition,
                $contract,
                [
                    'name' => 'Phase E name',
                    'headline' => 'Phase E headline',
                    'experience' => [],
                    'profile' => ['content' => (new Paragraph())->addText('Structured profile')],
                    'photo' => ['source' => $this->imagePath(), 'options' => []],
                    'metadata' => ['creator' => 'E6 creator'],
                ],
                $template->inspect()
            );
        } catch (\OdtTemplateEngine\Mapping\MappingResolutionException $exception) {
            throw new \RuntimeException(json_encode(array_map(
                static fn ($diagnostic): array => [$diagnostic->code(), $diagnostic->message(), $diagnostic->context()],
                $exception->validationResult()->diagnostics()
            ), JSON_THROW_ON_ERROR), 0, $exception);
        }
    }

    private function fixture(): string
    {
        // The collection prototype precedes Profile and Portrait to characterize E4-before-E3 localization.
        $source = MappingTemplateFixture::create(
            experienceBody: '<text:p>Unused collection</text:p>',
            stylesBody: '<style:header><text:p>{{headline}}</text:p></style:header>'
        );
        $zip = new ZipArchive();
        self::assertSame(true, $zip->open($source));
        $zip->addFromString('Pictures/template-existing.bin', 'pre-invocation package asset');
        $manifest = $zip->getFromName('META-INF/manifest.xml');
        self::assertIsString($manifest);
        $manifest = str_replace(
            '</manifest:manifest>',
            '<manifest:file-entry manifest:full-path="Pictures/template-existing.bin" manifest:media-type="application/octet-stream"/></manifest:manifest>',
            $manifest
        );
        $zip->addFromString('META-INF/manifest.xml', $manifest);
        $zip->close();
        $this->paths[] = $source;
        return $source;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/banner.png';
    }

    private function temporaryPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/e6-automation-' . bin2hex(random_bytes(8)) . $suffix;
        $this->paths[] = $path;
        return $path;
    }

    private function frameHref(DOMDocument $document): string
    {
        foreach ($document->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'frame') as $frame) {
            if ($frame instanceof \DOMElement && $frame->getAttribute('draw:name') === 'Portrait') {
                return $frame->firstChild?->getAttribute('xlink:href') ?? '';
            }
        }
        return '';
    }

    private function countSectionsNamed(DOMDocument $document, string $name): int
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        return $xpath->query('//text:section[@text:name=' . $this->xpathLiteral($name) . ']')->length;
    }

    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }
        return '"' . $value . '"';
    }

    private function semanticDom(\DOMNode $node): array
    {
        $attributes = [];
        if ($node instanceof \DOMElement) {
            foreach ($node->attributes as $attribute) {
                if ($attribute->namespaceURI === 'http://www.w3.org/2000/xmlns/') {
                    continue;
                }
                $attributes[] = [$attribute->namespaceURI, $attribute->localName, $attribute->nodeValue];
            }
            sort($attributes);
        }

        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $this->semanticDom($child);
        }

        return [$node->nodeType, $node->namespaceURI, $node->localName, $node->nodeValue, $attributes, $children];
    }
}

final class E6InspectableTemplate extends OdtTemplate
{
    public ?\Throwable $documentCapabilityFailure = null;
    public bool $mutateTransientDocumentState = false;
    public int $renderCalls = 0;
    public int $saveCalls = 0;

    public function automateDocumentCapabilities(\OdtTemplateEngine\Mapping\ConcretePreflightResult $preflight): void
    {
        if ($this->documentCapabilityFailure !== null) {
            throw $this->documentCapabilityFailure;
        }
        parent::automateDocumentCapabilities($preflight);
    }

    public function automateNativeObjectActions(TemplateContract $contract, \OdtTemplateEngine\Mapping\ConcretePreflightResult $preflight): void
    {
        if ($this->mutateTransientDocumentState) {
            $this->documentContext()->registerFontFaceRequirement(
                new FontFaceRequirement(FontFaceRequirement::PART_CONTENT, 'TransientFace', 'Transient Family')
            );
            $this->documentContext()->registerFillImageRequirement(
                new FillImageRequirement(FillImageRequirement::PART_STYLES, 'TransientFill', 'Pictures/transient.png')
            );
            $this->styles()->defineParagraph('TransientStyle', ['margin-top' => '0.3cm']);
        }
        parent::automateNativeObjectActions($contract, $preflight);
    }

    public function workingContent(): DOMDocument { return $this->documentContext()->contentDom(); }
    public function workingStyles(): DOMDocument { return $this->documentContext()->stylesDom(); }
    public function workingMeta(): DOMDocument { return $this->documentContext()->metaDom(); }
    public function styleDefinitions(): array { return $this->documentContext()->styleContext()->semanticDefinitions(); }
    public function fontRequirements(): array { return $this->documentContext()->fontFaceRequirements()->requirements(); }
    public function fillImageRequirements(): array { return $this->documentContext()->fillImageRequirements()->requirements(); }

    public function registerPriorRequirements(): void
    {
        $this->documentContext()->registerFontFaceRequirement(
            new FontFaceRequirement(FontFaceRequirement::PART_CONTENT, 'ExistingFace', 'Existing Family')
        );
        $this->documentContext()->registerFillImageRequirement(
            new FillImageRequirement(FillImageRequirement::PART_STYLES, 'ExistingFill', 'Pictures/existing.png')
        );
    }

    public function render(): void
    {
        ++$this->renderCalls;
        parent::render();
    }

    public function save(string $outputPath): void
    {
        ++$this->saveCalls;
        parent::save($outputPath);
    }
}
