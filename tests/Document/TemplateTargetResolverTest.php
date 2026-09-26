<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use OdtTemplateEngine\Document\AmbiguousTemplateTargetException;
use OdtTemplateEngine\Document\TemplateTarget;
use OdtTemplateEngine\Document\TemplateTargetResolver;
use PHPUnit\Framework\TestCase;

final class TemplateTargetResolverTest extends TestCase
{
    private const DRAWING_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const TABLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';

    public function testResolvesNamedFrameWithoutInspectingItsPayload(): void
    {
        $dom = $this->document();
        $frame = $this->appendFrame($dom, 'ProfileBox');

        $target = (new TemplateTargetResolver())->resolveFrame($dom, 'ProfileBox');

        self::assertNotNull($target);
        self::assertSame(TemplateTarget::TYPE_FRAME, $target->type());
        self::assertSame('ProfileBox', $target->name());
        self::assertSame($frame, $target->node());
    }

    public function testResolvesNamedImageFrameAndTextBoxFrameAsFrames(): void
    {
        $dom = $this->document();
        $imageFrame = $this->appendFrame($dom, 'Avatar');
        $imageFrame->appendChild($dom->createElementNS(self::DRAWING_NAMESPACE, 'draw:image'));
        $textBoxFrame = $this->appendFrame($dom, 'ProfileBox');
        $textBoxFrame->appendChild($dom->createElementNS(self::DRAWING_NAMESPACE, 'draw:text-box'));

        $resolver = new TemplateTargetResolver();

        self::assertSame($imageFrame, $resolver->resolveFrame($dom, 'Avatar')?->node());
        self::assertSame($textBoxFrame, $resolver->resolveFrame($dom, 'ProfileBox')?->node());
    }

    public function testResolvesNamedTableUsingTableIdentity(): void
    {
        $dom = $this->document();
        $table = $dom->createElementNS(self::TABLE_NAMESPACE, 'table:table');
        $table->setAttributeNS(self::TABLE_NAMESPACE, 'table:name', 'ExperienceTable');
        $dom->documentElement->appendChild($table);

        $target = (new TemplateTargetResolver())->resolveTable($dom, 'ExperienceTable');

        self::assertNotNull($target);
        self::assertSame(TemplateTarget::TYPE_TABLE, $target->type());
        self::assertSame($table, $target->node());
    }

    public function testFrameAndTableWithSameNameRemainTypedAndIndependent(): void
    {
        $dom = $this->document();
        $frame = $this->appendFrame($dom, 'SharedName');
        $table = $dom->createElementNS(self::TABLE_NAMESPACE, 'table:table');
        $table->setAttributeNS(self::TABLE_NAMESPACE, 'table:name', 'SharedName');
        $dom->documentElement->appendChild($table);

        $resolver = new TemplateTargetResolver();

        self::assertSame($frame, $resolver->resolveFrame($dom, 'SharedName')?->node());
        self::assertSame($table, $resolver->resolveTable($dom, 'SharedName')?->node());
    }

    public function testMissingTargetReturnsNull(): void
    {
        $target = (new TemplateTargetResolver())->resolveFrame($this->document(), 'Missing');

        self::assertNull($target);
    }

    public function testAmbiguousFrameNameIsReported(): void
    {
        $dom = $this->document();
        $this->appendFrame($dom, 'Duplicate');
        $this->appendFrame($dom, 'Duplicate');

        $this->expectException(AmbiguousTemplateTargetException::class);

        (new TemplateTargetResolver())->resolveFrame($dom, 'Duplicate');
    }

    public function testAmbiguousTableNameIsReported(): void
    {
        $dom = $this->document();
        for ($i = 0; $i < 2; $i++) {
            $table = $dom->createElementNS(self::TABLE_NAMESPACE, 'table:table');
            $table->setAttributeNS(self::TABLE_NAMESPACE, 'table:name', 'Duplicate');
            $dom->documentElement->appendChild($table);
        }

        $this->expectException(AmbiguousTemplateTargetException::class);

        (new TemplateTargetResolver())->resolveTable($dom, 'Duplicate');
    }

    public function testTechnicalIdsDoNotSubstituteForNativeTemplateNames(): void
    {
        $dom = $this->document();
        $frame = $this->appendFrame($dom, 'NamedFrame');
        $frame->setAttributeNS(self::XML_NAMESPACE, 'xml:id', 'technical-id');

        $resolver = new TemplateTargetResolver();

        self::assertNull($resolver->resolveFrame($dom, 'technical-id'));
        self::assertSame($frame, $resolver->resolveFrame($dom, 'NamedFrame')?->node());
    }

    public function testResolutionDoesNotMutateTheDocument(): void
    {
        $dom = $this->document();
        $this->appendFrame($dom, 'Avatar');
        $before = $dom->saveXML();

        (new TemplateTargetResolver())->resolveFrame($dom, 'Avatar');

        self::assertSame($before, $dom->saveXML());
    }

    private function document(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->createElementNS(self::OFFICE_NAMESPACE, 'office:document-content'));

        return $dom;
    }

    private function appendFrame(DOMDocument $dom, string $name): \DOMElement
    {
        $frame = $dom->createElementNS(self::DRAWING_NAMESPACE, 'draw:frame');
        $frame->setAttributeNS(self::DRAWING_NAMESPACE, 'draw:name', $name);
        $dom->documentElement->appendChild($frame);

        return $frame;
    }
}
