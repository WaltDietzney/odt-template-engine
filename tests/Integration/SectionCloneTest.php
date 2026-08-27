<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\AmbiguousAddressableTargetException;
use OdtTemplateEngine\Document\DocumentInspector;
use OdtTemplateEngine\Document\SectionCloneException;
use OdtTemplateEngine\Document\SectionCloneService;
use OdtTemplateEngine\Document\TypedTargetResolver;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\OdtPackage;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class SectionCloneTest extends TestCase
{
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    private const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';

    /** @var list<string> */
    private array $outputs = [];

    protected function tearDown(): void
    {
        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testExactClonePreservesNativeSubtreeAndInsertsAfterSource(): void
    {
        $package = new OdtPackage($this->templatePath());
        try {
            $context = $package->context();
            $source = $this->section($context->contentDom(), 'ExperienceEntry');
            $sourceXml = $source->C14N();
            $stylesXml = $context->stylesDom()->saveXML();

            $clone = (new SectionCloneService())->cloneExact($context, 'ExperienceEntry');

            self::assertSame($source, $clone->previousSibling);
            self::assertSame($sourceXml, $clone->C14N());
            self::assertSame($sourceXml, $source->C14N());
            self::assertSame($stylesXml, $context->stylesDom()->saveXML());
            self::assertSame(2, $this->sections($context->contentDom(), 'ExperienceEntry')->length);
            self::assertSame(2, $this->sections($context->contentDom(), 'ActivityEntry')->length);
            self::assertSame(4, $this->elementsInSections($context->contentDom(), 'text:bookmark-start', 'ExperienceEntry'));
            self::assertSame(2, $this->elementsInSections($context->contentDom(), 'text:bookmark', 'ExperienceEntry'));
            self::assertSame(4, $this->elementsInSections($context->contentDom(), 'text:list', 'ExperienceEntry'));
        } finally {
            $package->cleanup();
        }
    }

    public function testExactClonePreservesExpressionsFragmentationAndStyles(): void
    {
        $package = new OdtPackage($this->templatePath());
        try {
            $context = $package->context();
            $source = $this->section($context->contentDom(), 'ExperienceEntry');
            $sourceXml = $source->C14N();
            $clone = (new SectionCloneService())->cloneExact($context, 'ExperienceEntry');

            self::assertSame($sourceXml, $clone->C14N());
            self::assertStringContainsString('{{note}}', $clone->textContent);
            self::assertStringContainsString('{{position}}', $clone->textContent);
            self::assertStringContainsString('{{activity}}', $clone->textContent);
            self::assertSame(
                $this->attributeValues($source, 'text:style-name'),
                $this->attributeValues($clone, 'text:style-name')
            );
            self::assertSame(
                $this->attributeValues($source, 'text:name'),
                $this->attributeValues($clone, 'text:name')
            );
        } finally {
            $package->cleanup();
        }
    }

    public function testInspectorReportsTemporaryDuplicateIdentitiesAndResolverStaysStrict(): void
    {
        $package = new OdtPackage($this->templatePath());
        try {
            $context = $package->context();
            (new SectionCloneService())->cloneExact($context, 'ExperienceEntry');
            $inspection = (new DocumentInspector())->inspect($context->contentDom(), $context->stylesDom());
            $duplicates = array_map(
                static fn ($diagnostic): string => $diagnostic->targetType() . ':' . $diagnostic->targetName(),
                array_filter($inspection->diagnostics(), static fn ($diagnostic): bool => $diagnostic->code() === 'duplicate_native_name')
            );
            $bookmarkMarkerDiagnostics = array_map(
                static fn ($diagnostic): string => $diagnostic->targetType() . ':' . $diagnostic->targetName(),
                array_filter($inspection->diagnostics(), static fn ($diagnostic): bool => $diagnostic->code() === 'duplicate_bookmark_markers')
            );

            self::assertContains('section:ExperienceEntry', $duplicates);
            self::assertContains('section:ActivityEntry', $duplicates);
            self::assertContains('bookmark:Company', $bookmarkMarkerDiagnostics);
            self::assertContains('bookmark:FromTo', $bookmarkMarkerDiagnostics);
            self::assertContains('bookmark:Activity', $bookmarkMarkerDiagnostics);
            $this->expectException(AmbiguousAddressableTargetException::class);
            (new TypedTargetResolver())->resolveSection($context, 'ExperienceEntry');
        } finally {
            $package->cleanup();
        }
    }

    public function testRepeatedExactCloneFromOriginalSourceCreatesEquivalentCopies(): void
    {
        $package = new OdtPackage($this->templatePath());
        try {
            $context = $package->context();
            $source = $this->section($context->contentDom(), 'ExperienceEntry');
            $service = new SectionCloneService();
            $service->cloneExact($context, 'ExperienceEntry');
            $service->cloneExactSource($source);

            $sections = $this->sections($context->contentDom(), 'ExperienceEntry');
            self::assertSame(3, $sections->length);
            for ($index = 0; $index < $sections->length; $index++) {
                self::assertSame($source->C14N(), $sections->item($index)?->C14N());
            }
        } finally {
            $package->cleanup();
        }
    }

    public function testCloneSurvivesSaveAndReopenWithSharedResources(): void
    {
        $package = new OdtPackage($this->templatePath());
        $output = $this->outputPath();
        try {
            (new SectionCloneService())->cloneExact($package->context(), 'ExperienceEntry');
            $package->saveAs($output);
        } finally {
            $package->cleanup();
        }

        $zip = new ZipArchive();
        self::assertTrue($zip->open($output) === true);
        foreach (['content.xml', 'styles.xml', 'meta.xml', 'META-INF/manifest.xml'] as $part) {
            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($zip->getFromName($part)));
        }
        $zip->close();

        $reopened = new OdtPackage($output);
        try {
            self::assertSame(2, $this->sections($reopened->contentDom(), 'ExperienceEntry')->length);
            self::assertSame(2, $this->sections($reopened->contentDom(), 'ActivityEntry')->length);
        } finally {
            $reopened->cleanup();
        }
    }

    public function testTechnicalIdentityFailureLeavesDocumentUnchanged(): void
    {
        $package = new OdtPackage($this->templatePath());
        try {
            $dom = $package->contentDom();
            $source = $this->section($dom, 'ExperienceEntry');
            $source->setAttributeNS(self::XML_NAMESPACE, 'xml:id', 'duplicate-id');
            $outside = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:p');
            $outside->setAttributeNS(self::XML_NAMESPACE, 'xml:id', 'duplicate-id');
            $dom->documentElement->appendChild($outside);
            $before = $dom->saveXML();

            try {
                (new SectionCloneService())->cloneExact($package->context(), 'ExperienceEntry');
                self::fail('Expected duplicate technical identity to be rejected.');
            } catch (SectionCloneException $exception) {
                self::assertStringContainsString('xml:id', $exception->reason());
            }
            self::assertSame($before, $dom->saveXML());
        } finally {
            $package->cleanup();
        }
    }

    public function testRewrittenCloneIsUniquelyAddressableAndPreservesNativeFragmentation(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $source = $template->section('ExperienceEntry');
        $sourceXml = $source->descriptor()->toArray();

        $clone = $source->clone();

        self::assertSame('ExperienceEntry_1', $clone->name());
        self::assertSame('ExperienceEntry_1', $clone->descriptor()->name());
        self::assertStringContainsString('{{note_1}}', $clone->text());
        self::assertStringContainsString('{{position_1}}', $clone->text());
        self::assertStringContainsString('{{activity_1}}', $clone->text());
        self::assertSame('ExperienceEntry', $source->descriptor()->name());
        self::assertSame('ActivityEntry_1', $this->nestedName($clone->descriptor()->nestedNamedObjects(), 'section'));
        self::assertContains('Company_1', $this->nestedNames($clone->descriptor()->nestedNamedObjects(), 'bookmark'));
        self::assertContains('FromTo_1', $this->nestedNames($clone->descriptor()->nestedNamedObjects(), 'bookmark'));
        self::assertContains('Activity_1', $this->nestedNames($clone->descriptor()->nestedNamedObjects(), 'bookmark'));
        self::assertSame([], $template->inspect()->diagnostics());
        self::assertSame('ExperienceEntry', $sourceXml['name']);
    }

    public function testRepeatedPrototypeClonesAllocateDeterministicIndexesAndLeaveSourceExpressionUntouched(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $prototype = $template->section('ExperienceEntry');

        $first = $prototype->clone();
        $second = $prototype->clone();
        $third = $prototype->clone();

        $sectionNames = array_map(
            static fn ($section): string => $section->name(),
            $template->inspect()->sections()
        );
        self::assertSame(1, count(array_keys($sectionNames, 'ExperienceEntry', true)));
        self::assertSame(1, count(array_keys($sectionNames, 'ExperienceEntry_1', true)));
        self::assertSame(1, count(array_keys($sectionNames, 'ExperienceEntry_2', true)));
        self::assertSame(1, count(array_keys($sectionNames, 'ExperienceEntry_3', true)));
        self::assertSame('ExperienceEntry_1', $first->name());
        self::assertSame('ExperienceEntry_2', $second->name());
        self::assertSame('ExperienceEntry_3', $third->name());
        self::assertStringContainsString('{{position}}', $prototype->text());
        self::assertStringContainsString('{{position_3}}', $third->text());
        self::assertSame([], $template->inspect()->diagnostics());
    }

    public function testExistingCloneCannotBeClonedInThisSlice(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $clone = $template->section('ExperienceEntry')->clone();

        $this->expectException(SectionCloneException::class);
        $clone->clone();
    }

    public function testCloneIndexUsesTheNextDocumentAvailableIndexForAllNestedIdentities(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $dom = $this->contentDom($template);
        $root = $dom->documentElement;
        foreach (['ExperienceEntry_1', 'ExperienceEntry_3'] as $name) {
            $section = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:section');
            $section->setAttribute('text:name', $name);
            $root->appendChild($section);
        }

        $clone = $template->section('ExperienceEntry')->clone();

        self::assertSame('ExperienceEntry_2', $clone->name());
        self::assertSame('ActivityEntry_2', $this->nestedName($clone->descriptor()->nestedNamedObjects(), 'section'));
        self::assertStringContainsString('{{activity_2}}', $clone->text());
    }

    public function testFilterExpressionIsRewrittenAtTheVariableIdentityOnly(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $source = $this->section($this->contentDom($template), 'ExperienceEntry');
        $paragraph = null;
        foreach ($source->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'p') as $candidate) {
            if ($candidate->textContent === '{{note}}') {
                $paragraph = $candidate;
                break;
            }
        }
        self::assertInstanceOf(DOMElement::class, $paragraph);
        self::assertNotNull($paragraph->firstChild);
        $paragraph->firstChild->nodeValue = '{{upper:note}}';

        $clone = $template->section('ExperienceEntry')->clone();

        self::assertStringContainsString('{{upper:note_1}}', $clone->text());
    }

    public function testRewrittenCloneSurvivesSaveAndReopen(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->section('ExperienceEntry')->clone();
        $output = $this->outputPath();
        $template->save($output);

        $reopened = new OdtTemplate($output);
        self::assertStringContainsString('{{position_1}}', $reopened->section('ExperienceEntry_1')->text());
    }

    public function testPrototypeTargetResolvesAgainstCurrentContextAfterReload(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $prototype = $template->section('ExperienceEntry');
        $template->load();

        $clone = $prototype->clone();

        self::assertSame('ExperienceEntry_1', $clone->name());
        self::assertStringContainsString('{{note_1}}', $clone->text());
    }

    public function testTechnicalIdsAreRewrittenOnTheDetachedClone(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $dom = $this->contentDom($template);
        $source = $this->section($dom, 'ExperienceEntry');
        $source->setAttributeNS(self::XML_NAMESPACE, 'xml:id', 'experience-id');

        $clone = $template->section('ExperienceEntry')->clone();

        self::assertSame('experience-id', $source->getAttributeNS(self::XML_NAMESPACE, 'id'));
        $clonedDom = $this->section($dom, 'ExperienceEntry_1');
        self::assertSame('experience-id_1', $clonedDom->getAttributeNS(self::XML_NAMESPACE, 'id'));
    }

    public function testUnsupportedCloneExpressionFailsBeforeLiveDomInsertion(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $dom = $this->contentDom($template);
        $source = $this->section($dom, 'ExperienceEntry');
        foreach ($source->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'p') as $paragraph) {
            if ($paragraph->textContent === '{{note}}') {
                self::assertNotNull($paragraph->firstChild);
                $paragraph->firstChild->nodeValue = '{{unsupported:expression:shape}}';
                break;
            }
        }
        $before = $dom->saveXML();

        try {
            $template->section('ExperienceEntry')->clone();
            self::fail('Expected unsupported expression to be rejected.');
        } catch (SectionCloneException $exception) {
            self::assertStringContainsString('unsupported template expression', $exception->reason());
        }

        self::assertSame($before, $dom->saveXML());
        self::assertSame(1, $this->sections($dom, 'ExperienceEntry')->length);
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/sample_25_sectionClone.odt';
    }

    private function outputPath(): string
    {
        $output = sys_get_temp_dir() . '/odt-section-clone-' . uniqid('', true) . '.odt';
        $this->outputs[] = $output;
        return $output;
    }

    private function section(DOMDocument $dom, string $name): DOMElement
    {
        $sections = $this->sections($dom, $name);
        self::assertSame(1, $sections->length);
        self::assertInstanceOf(DOMElement::class, $sections->item(0));
        return $sections->item(0);
    }

    private function contentDom(OdtTemplate $template): DOMDocument
    {
        $reflection = new \ReflectionClass($template);
        $property = $reflection->getProperty('package');
        $property->setAccessible(true);
        /** @var OdtPackage $package */
        $package = $property->getValue($template);
        return $package->contentDom();
    }

    private function sections(DOMDocument $dom, string $name): \DOMNodeList
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('text', self::TEXT_NAMESPACE);
        return $xpath->query(sprintf('//text:section[@text:name="%s"]', $name));
    }

    private function elementsInSections(DOMDocument $dom, string $name, string $sectionName): int
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('text', self::TEXT_NAMESPACE);
        return $xpath->query(sprintf('//text:section[@text:name="%s"]//%s', $sectionName, $name))->length;
    }

    /** @return list<string> */
    private function attributeValues(DOMElement $element, string $name): array
    {
        $values = [];
        foreach ($element->getElementsByTagName('*') as $child) {
            if ($child instanceof DOMElement && $child->hasAttribute($name)) {
                $values[] = $child->getAttribute($name);
            }
        }
        return $values;
    }

    /** @param list<\OdtTemplateEngine\Document\NamedObjectReference> $objects */
    private function nestedNames(array $objects, string $type): array
    {
        return array_values(array_map(
            static fn ($object): string => $object->name(),
            array_filter($objects, static fn ($object): bool => $object->type() === $type)
        ));
    }

    /** @param list<\OdtTemplateEngine\Document\NamedObjectReference> $objects */
    private function nestedName(array $objects, string $type): string
    {
        $names = $this->nestedNames($objects, $type);
        self::assertNotEmpty($names);
        return $names[0];
    }
}
