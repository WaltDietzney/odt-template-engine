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
}
