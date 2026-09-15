<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\SectionCloneService;
use OdtTemplateEngine\Document\SectionResolutionException;
use OdtTemplateEngine\Document\SectionWorkingTargetResolver;
use OdtTemplateEngine\Document\TargetNotFoundException;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Template\NativeObjectDescriptor;
use OdtTemplateEngine\Template\SourceProvenance;
use PHPUnit\Framework\TestCase;

final class SectionWorkingTargetResolverTest extends TestCase
{
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testResolvesBodySectionFromBodyProvenance(): void
    {
        [$context, $descriptors] = $this->context();

        $target = (new SectionWorkingTargetResolver())->resolve($context, $descriptors['body']);

        self::assertSame('BodyShared', $target->name());
        self::assertSame('content.xml', $target->provenance()->sourcePart());
        self::assertSame('BODY', $target->provenance()->regionKind());
        self::assertSame('BODY CONTENT', $target->section()->textContent);
    }

    public function testResolvesSameNamedHeaderAndFooterByCarrierRegion(): void
    {
        [$context, $descriptors] = $this->context();
        $resolver = new SectionWorkingTargetResolver();

        $header = $resolver->resolve($context, $descriptors['header']);
        $footer = $resolver->resolve($context, $descriptors['footer']);

        self::assertSame('MASTER HEADER', $header->section()->textContent);
        self::assertSame('MASTER FOOTER', $footer->section()->textContent);
        self::assertSame('Standard', $header->provenance()->regionOwner());
        self::assertSame('Standard', $footer->provenance()->regionOwner());
    }

    public function testResolvesSameNamedHeadersByMasterPageOwner(): void
    {
        [$context, $descriptors] = $this->context();
        $resolver = new SectionWorkingTargetResolver();

        $standard = $resolver->resolve($context, $descriptors['header']);
        $first = $resolver->resolve($context, $descriptors['firstHeader']);

        self::assertSame('MASTER HEADER', $standard->section()->textContent);
        self::assertSame('FIRST HEADER', $first->section()->textContent);
        self::assertSame('Standard', $standard->provenance()->regionOwner());
        self::assertSame('First', $first->provenance()->regionOwner());
    }

    public function testResolvedNonBodyTargetCanUseExistingCloneIdentityMechanics(): void
    {
        [$context, $descriptors] = $this->context();
        $target = (new SectionWorkingTargetResolver())->resolve($context, $descriptors['header']);

        $clone = (new SectionCloneService())->cloneWithRewrittenIdentitiesInWorkingTarget($target);

        self::assertSame('Shared_1', $clone->getAttribute('text:name'));
        self::assertSame('MASTER HEADER', $clone->textContent);
        self::assertSame(2, $target->regionRoot()->getElementsByTagNameNS(self::TEXT, 'section')->length);
        self::assertSame(1, $context->contentDom()->getElementsByTagNameNS(self::TEXT, 'section')->length);
    }

    public function testContradictoryOrUnresolvableProvenanceFailsWithoutGlobalFallback(): void
    {
        [$context, $descriptors] = $this->context();
        $resolver = new SectionWorkingTargetResolver();

        $this->expectException(SectionResolutionException::class);
        $resolver->resolve($context, $this->descriptor(
            'Missing',
            'content.xml',
            'BODY',
            null,
            'style:header'
        ));
    }

    public function testMissingSectionInCorrectRegionFailsExplicitly(): void
    {
        [$context] = $this->context();

        $this->expectException(TargetNotFoundException::class);
        (new SectionWorkingTargetResolver())->resolve($context, $this->descriptor(
            'Missing',
            'styles.xml',
            'MASTER_PAGE_CONTENT',
            'Standard',
            'style:header'
        ));
    }

    /** @return array{0:OdtDocumentContext,1:array<string,NativeObjectDescriptor>} */
    private function context(): array
    {
        $content = $this->document('office:document-content');
        $body = $content->createElementNS(self::OFFICE, 'office:body');
        $text = $content->createElementNS(self::OFFICE, 'office:text');
        $bodySection = $this->section($content, 'BodyShared', 'BODY CONTENT');
        $text->appendChild($bodySection);
        $body->appendChild($text);
        $content->documentElement->appendChild($body);

        $styles = $this->document('office:document-styles');
        $standard = $this->masterPage($styles, 'Standard', 'MASTER HEADER', 'MASTER FOOTER');
        $first = $this->masterPage($styles, 'First', 'FIRST HEADER', 'FIRST FOOTER');
        $styles->documentElement->appendChild($standard);
        $styles->documentElement->appendChild($first);

        return [
            new OdtDocumentContext($content, $styles, $this->document('office:document-meta')),
            [
                'body' => $this->descriptor('BodyShared', 'content.xml', 'BODY', null, 'office:text'),
                'header' => $this->descriptor('Shared', 'styles.xml', 'MASTER_PAGE_CONTENT', 'Standard', 'style:header'),
                'footer' => $this->descriptor('Shared', 'styles.xml', 'MASTER_PAGE_CONTENT', 'Standard', 'style:footer'),
                'firstHeader' => $this->descriptor('Shared', 'styles.xml', 'MASTER_PAGE_CONTENT', 'First', 'style:header'),
            ],
        ];
    }

    private function descriptor(
        string $name,
        string $part,
        string $region,
        ?string $owner,
        string $carrier
    ): NativeObjectDescriptor {
        return new NativeObjectDescriptor(
            'section',
            $name,
            new SourceProvenance(
                'e_' . md5(implode('|', [$name, $part, $region, $owner, $carrier])),
                $part,
                $region,
                $owner,
                $carrier,
                'native_object_name',
                0
            ),
            'n_' . md5(implode('|', [$name, $part, $region, $owner, $carrier]))
        );
    }

    private function masterPage(DOMDocument $dom, string $name, string $headerText, string $footerText): DOMElement
    {
        $master = $dom->createElementNS(self::STYLE, 'style:master-page');
        $master->setAttribute('style:name', $name);
        foreach ([['style:header', $headerText], ['style:footer', $footerText]] as [$kind, $text]) {
            $carrier = $dom->createElementNS(self::STYLE, $kind);
            $carrier->appendChild($this->section($dom, 'Shared', $text));
            $master->appendChild($carrier);
        }
        return $master;
    }

    private function section(DOMDocument $dom, string $name, string $text): DOMElement
    {
        $section = $dom->createElementNS(self::TEXT, 'text:section');
        $section->setAttribute('text:name', $name);
        $paragraph = $dom->createElementNS(self::TEXT, 'text:p');
        $paragraph->appendChild($dom->createTextNode($text));
        $section->appendChild($paragraph);
        return $section;
    }

    private function document(string $root): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(sprintf(
            '<%s xmlns:office="%s" xmlns:style="%s" xmlns:text="%s"/>',
            $root,
            self::OFFICE,
            self::STYLE,
            self::TEXT
        ));
        return $dom;
    }
}
