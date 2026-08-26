<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use OdtTemplateEngine\Document\AmbiguousAddressableTargetException;
use OdtTemplateEngine\Document\BookmarkDescriptor;
use OdtTemplateEngine\Document\DocumentInspector;
use OdtTemplateEngine\Document\MalformedTargetException;
use OdtTemplateEngine\Document\TargetNotFoundException;
use OdtTemplateEngine\Document\TypedTargetResolver;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class TypedTargetResolverTest extends TestCase
{
    private const DRAWING_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TABLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testResolvesEachNativeTypeToItsOwnTypedReadOnlyHandle(): void
    {
        $context = $this->contextWithNamedObjects();
        $resolver = new TypedTargetResolver();

        $section = $resolver->resolveSection($context, 'Profile');
        $bookmark = $resolver->resolveBookmark($context, 'Profile');
        $table = $resolver->resolveTable($context, 'Profile');
        $frame = $resolver->resolveFrame($context, 'Profile');

        self::assertSame('section', $section->type());
        self::assertSame('bookmark', $bookmark->type());
        self::assertSame('table', $table->type());
        self::assertSame('frame', $frame->type());
        self::assertSame('Profile', $section->descriptor()->name());
        self::assertSame('Profile', $bookmark->descriptor()->name());
        self::assertSame('Profile', $table->descriptor()->name());
        self::assertSame('Profile', $frame->descriptor()->name());
    }

    public function testMissingTargetFailsWithStructuredStrictResolutionError(): void
    {
        $context = $this->contextWithNamedObjects();

        try {
            (new TypedTargetResolver())->resolveSection($context, 'Missing');
            self::fail('Expected target resolution to fail.');
        } catch (TargetNotFoundException $exception) {
            self::assertSame('section', $exception->targetType());
            self::assertSame('Missing', $exception->targetName());
        }
    }

    public function testDuplicateSameTypeIdentityIsAmbiguousButDifferentTypesRemainIndependent(): void
    {
        $context = $this->contextWithNamedObjects(true);
        $resolver = new TypedTargetResolver();

        $this->expectException(AmbiguousAddressableTargetException::class);
        $this->expectExceptionMessage('Multiple section targets');
        $resolver->resolveSection($context, 'Profile');
    }

    public function testMalformedBookmarkRemainsInspectableButCannotBecomeTarget(): void
    {
        $context = $this->contextWithMalformedBookmark();
        $descriptor = (new DocumentInspector())->inspect($context->contentDom(), $context->stylesDom())->bookmark('Broken');

        self::assertNotNull($descriptor);
        self::assertSame(BookmarkDescriptor::TOPOLOGY_MALFORMED, $descriptor->topology());

        try {
            (new TypedTargetResolver())->resolveBookmark($context, 'Broken');
            self::fail('Expected malformed bookmark resolution to fail.');
        } catch (MalformedTargetException $exception) {
            self::assertSame('bookmark', $exception->targetType());
            self::assertSame('Broken', $exception->targetName());
        }
    }

    public function testIdentityBackedHandleResolvesAgainstCurrentContextAfterDocumentReplacement(): void
    {
        $context = $this->contextWithNamedObjects();
        $target = (new TypedTargetResolver())->resolveSection($context, 'Profile');

        $replacement = $this->document();
        $section = $replacement->createElementNS(self::TEXT_NAMESPACE, 'text:section');
        $section->setAttribute('text:name', 'Profile');
        $section->appendChild($replacement->createElementNS(self::TEXT_NAMESPACE, 'text:p'));
        $replacement->documentElement->appendChild($section);
        $context->replaceCoreDocuments($replacement, $this->document(), $this->document());

        self::assertSame('Profile', $target->descriptor()->name());
        self::assertSame(1, $target->descriptor()->childSummary()['paragraphs']);

        $context->replaceCoreDocuments($this->document(), $this->document(), $this->document());
        $this->expectException(TargetNotFoundException::class);
        $target->descriptor();
    }

    public function testHandlesRemainBoundToTheirOwnDocumentContext(): void
    {
        $firstContext = $this->contextWithNamedObjects();
        $secondContext = $this->contextWithNamedObjects();
        $first = (new TypedTargetResolver())->resolveSection($firstContext, 'Profile');
        $second = (new TypedTargetResolver())->resolveSection($secondContext, 'Profile');

        $firstContext->replaceCoreDocuments($this->document(), $this->document(), $this->document());

        self::assertSame('Profile', $second->descriptor()->name());
        $this->expectException(TargetNotFoundException::class);
        $first->descriptor();
    }

    public function testFacadeResolvesTypedTargetsWithoutExposingMutation(): void
    {
        $template = new class ('samples/templates/template_01_simple_variables.odt') extends OdtTemplate {
            public function addSection(): void
            {
                $section = $this->documentContext()->contentDom()->createElementNS(
                    'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
                    'text:section'
                );
                $section->setAttribute('text:name', 'ExperienceEntry');
                $this->documentContext()->contentDom()->documentElement->appendChild($section);
            }
        };
        $template->addSection();

        self::assertSame('ExperienceEntry', $template->section('ExperienceEntry')->name());
        self::assertFalse(method_exists($template->section('ExperienceEntry'), 'replaceContent'));
    }

    public function testFacadeHandleFailsDeterministicallyAfterLoadRemovesItsIdentity(): void
    {
        $template = new class ('samples/templates/template_01_simple_variables.odt') extends OdtTemplate {
            public function addSection(): void
            {
                $section = $this->documentContext()->contentDom()->createElementNS(
                    'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
                    'text:section'
                );
                $section->setAttribute('text:name', 'Transient');
                $this->documentContext()->contentDom()->documentElement->appendChild($section);
            }
        };
        $template->addSection();
        $target = $template->section('Transient');
        $template->load();

        $this->expectException(TargetNotFoundException::class);
        $target->descriptor();
    }

    public function testFacadeHandleFailsDeterministicallyAfterRefreshRemovesItsIdentity(): void
    {
        $template = new class ('samples/templates/template_01_simple_variables.odt') extends OdtTemplate {
            public function addSection(): void
            {
                $section = $this->documentContext()->contentDom()->createElementNS(
                    'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
                    'text:section'
                );
                $section->setAttribute('text:name', 'Transient');
                $this->documentContext()->contentDom()->documentElement->appendChild($section);
            }
        };
        $template->addSection();
        $target = $template->section('Transient');
        $template->refresh();

        $this->expectException(TargetNotFoundException::class);
        $target->descriptor();
    }

    public function testSeparateFacadeInstancesCannotShareTargetContext(): void
    {
        $first = new class ('samples/templates/template_01_simple_variables.odt') extends OdtTemplate {
            public function addSection(): void
            {
                $section = $this->documentContext()->contentDom()->createElementNS(
                    'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
                    'text:section'
                );
                $section->setAttribute('text:name', 'Profile');
                $this->documentContext()->contentDom()->documentElement->appendChild($section);
            }
        };
        $second = new class ('samples/templates/template_01_simple_variables.odt') extends OdtTemplate {
            public function addSection(): void
            {
                $section = $this->documentContext()->contentDom()->createElementNS(
                    'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
                    'text:section'
                );
                $section->setAttribute('text:name', 'Profile');
                $this->documentContext()->contentDom()->documentElement->appendChild($section);
            }
        };
        $first->addSection();
        $second->addSection();

        $firstTarget = $first->section('Profile');
        $secondTarget = $second->section('Profile');
        $first->load();

        self::assertSame('Profile', $secondTarget->descriptor()->name());
        $this->expectException(TargetNotFoundException::class);
        $firstTarget->descriptor();
    }

    private function contextWithNamedObjects(bool $duplicateSection = false): OdtDocumentContext
    {
        $content = $this->document();
        $section = $content->createElementNS(self::TEXT_NAMESPACE, 'text:section');
        $section->setAttribute('text:name', 'Profile');
        $section->appendChild($content->createElementNS(self::TEXT_NAMESPACE, 'text:p'));
        $content->documentElement->appendChild($section);
        if ($duplicateSection) {
            $duplicate = $content->createElementNS(self::TEXT_NAMESPACE, 'text:section');
            $duplicate->setAttribute('text:name', 'Profile');
            $content->documentElement->appendChild($duplicate);
        }

        $bookmarkStart = $content->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-start');
        $bookmarkStart->setAttribute('text:name', 'Profile');
        $bookmarkEnd = $content->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-end');
        $bookmarkEnd->setAttribute('text:name', 'Profile');
        $paragraph = $content->createElementNS(self::TEXT_NAMESPACE, 'text:p');
        $paragraph->appendChild($bookmarkStart);
        $paragraph->appendChild($content->createTextNode('value'));
        $paragraph->appendChild($bookmarkEnd);
        $content->documentElement->appendChild($paragraph);

        $table = $content->createElementNS(self::TABLE_NAMESPACE, 'table:table');
        $table->setAttribute('table:name', 'Profile');
        $content->documentElement->appendChild($table);
        $frame = $content->createElementNS(self::DRAWING_NAMESPACE, 'draw:frame');
        $frame->setAttribute('draw:name', 'Profile');
        $content->documentElement->appendChild($frame);

        return new OdtDocumentContext($content, $this->document(), $this->document());
    }

    private function contextWithMalformedBookmark(): OdtDocumentContext
    {
        $content = $this->document();
        $bookmark = $content->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-start');
        $bookmark->setAttribute('text:name', 'Broken');
        $content->documentElement->appendChild($bookmark);

        return new OdtDocumentContext($content, $this->document(), $this->document());
    }

    private function document(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->createElementNS(self::OFFICE_NAMESPACE, 'office:document-content'));

        return $dom;
    }
}
