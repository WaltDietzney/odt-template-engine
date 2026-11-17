<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Document\SectionMutationException;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\SectionElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class SectionAuthoring01ConstructionTest extends TestCase
{
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

    public function testEmptySectionUsesMinimalNativeRepresentationAndNestedContentIsOrdered(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->appendSection(new SectionElement('SA01Empty'));
        $section = (new SectionElement('SA01Outer'))->addSection(
            (new SectionElement('SA01Nested'))->addElement((new Paragraph())->addText('Nested'))
        );

        $target = $template->appendSection($section);
        self::assertSame('SA01Outer', $target->name());
        self::assertSame('Nested', $template->section('SA01Nested')->text());

        $output = $this->outputPath();
        $template->save($output);
        $xml = $this->entry($output, 'content.xml');
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($xml));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $empty = $xpath->query('//text:section[@text:name="SA01Empty"]')->item(0);
        self::assertNotNull($empty);
        self::assertSame(0, $empty->childNodes->length);

        $reopened = new OdtTemplate($output);
        self::assertSame('Nested', $reopened->section('SA01Nested')->text());
    }

    public function testAppendBodyIsWorkingOnlyAndLoadResetsConstructedContent(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $sourceContract = $template->inspectTemplate()->toArray();
        $template->appendBody((new RichText())->addParagraph('SA01 body'));
        self::assertSame($sourceContract, $template->inspectTemplate()->toArray());

        $output = $this->outputPath();
        $template->save($output);
        self::assertStringContainsString('SA01 body', $this->entry($output, 'content.xml'));
        $template->load();
        self::assertStringNotContainsString('SA01 body', $this->workingContent($template));
    }

    public function testConstructionRejectsStandaloneTextAndMalformedBookmarkWithSectionDiagnostics(): void
    {
        $template = new OdtTemplate($this->templatePath());
        try {
            $template->appendBody(new Sa01CustomElement('text'));
            self::fail('Expected standalone text to be rejected.');
        } catch (SectionMutationException $exception) {
            self::assertSame('appendBody', $exception->operation());
            self::assertSame('', $exception->sectionName());
        }

        try {
            $template->appendSection((new SectionElement('SA01Text'))->addElement(new Sa01CustomElement('text')));
            self::fail('Expected standalone Section text to be rejected.');
        } catch (SectionMutationException $exception) {
            self::assertSame('appendSection', $exception->operation());
            self::assertSame('SA01Text', $exception->sectionName());
        }

        try {
            $template->appendSection((new SectionElement('SA01Broken'))->addElement(new Sa01CustomElement('bookmark')));
            self::fail('Expected malformed bookmark to be rejected.');
        } catch (SectionMutationException $exception) {
            self::assertSame('appendSection', $exception->operation());
            self::assertSame('SA01Broken', $exception->sectionName());
            self::assertStringContainsString('bookmark', $exception->reason());
        }
    }

    public function testParagraphHyperlinkIsLegalInlineContent(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->appendBody((new Paragraph())->addHyperlink('Example', 'https://example.org'));

        self::assertStringContainsString('Example', $this->workingContent($template));
    }

    public function testConstructionRejectsSectionInsideParagraph(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $paragraph = new Sa01CustomElement('paragraph-with-section');

        $this->expectException(SectionMutationException::class);
        $this->expectExceptionMessage('Section outside legal block flow');
        $template->appendBody($paragraph);
    }

    public function testConstructionRejectsBookmarkEndBeforeStart(): void
    {
        $template = new OdtTemplate($this->templatePath());

        $this->expectException(SectionMutationException::class);
        $this->expectExceptionMessage('malformed bookmark');
        $template->appendBody(new Sa01CustomElement('bookmark-end-first'));
    }

    public function testConstructionRejectsExplicitlyWrongNamespace(): void
    {
        $template = new OdtTemplate($this->templatePath());

        $this->expectException(SectionMutationException::class);
        $this->expectExceptionMessage('wrong namespace');
        $template->appendBody(new Sa01CustomElement('wrong-namespace'));
    }

    public function testBodyAndSectionAcceptSupportedMixedBlockCompositesAndDirectFrames(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $mixed = (new RichText())
            ->addParagraph((new Paragraph())->addHyperlink('Example', 'https://example.org'))
            ->addBulletList(['listed'])
            ->addTable((new RichTable())->addRow(['cell']))
            ->addImage(new ImageElement(dirname(__DIR__, 2) . '/assets/Logo.png'))
            ->addElement((new DrawTextBox('SA01BodyBox'))->addElement((new Paragraph())->addText('box')));
        $template->appendBody($mixed);

        $section = (new SectionElement('SA01MixedSection'))
            ->addElement((new Paragraph())->addText('paragraph'))
            ->addElement(new ListElement())
            ->addElement((new RichTable())->addRow(['cell']))
            ->addElement(new ImageElement(dirname(__DIR__, 2) . '/assets/Logo.png'))
            ->addElement((new DrawTextBox('SA01SectionBox'))->addElement((new Paragraph())->addText('box')));
        $template->appendSection($section);

        $output = $this->outputPath();
        $template->save($output);
        $content = $this->entry($output, 'content.xml');
        $manifest = $this->entry($output, 'META-INF/manifest.xml');
        self::assertStringContainsString('Example', $content);
        self::assertStringContainsString('SA01BodyBox', $content);
        self::assertStringContainsString('SA01SectionBox', $content);
        self::assertSame(2, substr_count($content, 'Pictures/Logo.png'));
        self::assertSame(1, substr_count($manifest, 'Pictures/Logo.png'));
    }

    public function testIdentityCollisionsAreTypeScopedAndRejectExistingAndIntroducedDuplicates(): void
    {
        $template = new OdtTemplate($this->templatePath());

        try {
            $template->appendSection(new SectionElement('SimpleSection'));
            self::fail('Existing section collision was not rejected.');
        } catch (SectionMutationException $exception) {
            self::assertSame('section', $exception->conflictingType());
            self::assertSame('SimpleSection', $exception->conflictingName());
        }

        try {
            $template->appendBody(new Sa01CustomElement('duplicate-section'));
            self::fail('Introduced section collision was not rejected.');
        } catch (SectionMutationException $exception) {
            self::assertSame('section', $exception->conflictingType());
        }

        $template->appendBody(new Sa01CustomElement('different-type-shared-name'));
        self::assertStringContainsString('TypeShared', $this->workingContent($template));
    }

    public function testExistingAndIntroducedTableFrameAndBookmarkNamesAreRejected(): void
    {
        foreach ([
            ['named-table', 'duplicate-table', 'table'],
            ['named-frame', 'duplicate-frame', 'frame'],
            ['named-bookmark', 'duplicate-bookmark', 'bookmark'],
        ] as [$single, $introducedDuplicate, $type]) {
            $template = new OdtTemplate($this->templatePath());
            $template->appendBody(new Sa01CustomElement($single));

            try {
                $template->appendBody(new Sa01CustomElement($single));
                self::fail("Existing {$type} collision was not rejected.");
            } catch (SectionMutationException $exception) {
                self::assertSame($type, $exception->conflictingType());
            }

            try {
                $template->appendBody(new Sa01CustomElement($introducedDuplicate));
                self::fail("Introduced {$type} collision was not rejected.");
            } catch (SectionMutationException $exception) {
                self::assertSame($type, $exception->conflictingType());
            }
        }
    }

    public function testLateRendererFailureRestoresLiveDomStylesAndPackageManifest(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $before = $this->outputPath();
        $template->save($before);
        $beforeContent = $this->entry($before, 'content.xml');
        $beforeStyles = $this->entry($before, 'styles.xml');
        $beforeManifest = $this->entry($before, 'META-INF/manifest.xml');

        try {
            $template->appendBody(new Sa01CustomElement('mutate-and-fail'));
            self::fail('Expected custom renderer failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('SA01 late renderer failure', $exception->getMessage());
        }

        $after = $this->outputPath();
        $template->save($after);
        self::assertSame($beforeContent, $this->entry($after, 'content.xml'));
        self::assertSame($beforeStyles, $this->entry($after, 'styles.xml'));
        self::assertSame($beforeManifest, $this->entry($after, 'META-INF/manifest.xml'));
    }

    public function testRepeatedSaveReloadInspectReplaceAndSourceResetRemainStable(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $source = $template->inspectTemplate()->toArray();
        $template->appendSection((new SectionElement('SA01Lifecycle'))->addElement((new Paragraph())->addText('first')));

        $first = $this->outputPath();
        $second = $this->outputPath();
        $template->save($first);
        $template->save($second);
        self::assertSame($this->entry($first, 'content.xml'), $this->entry($second, 'content.xml'));
        self::assertSame($source, $template->inspectTemplate()->toArray());

        $template->load();
        self::assertStringNotContainsString('SA01Lifecycle', $this->workingContent($template));

        $reloaded = new OdtTemplate($second);
        self::assertSame('first', $reloaded->section('SA01Lifecycle')->text());
        $reloaded->section('SA01Lifecycle')->replaceContent((new Paragraph())->addText('replaced'));
        self::assertSame('replaced', $reloaded->section('SA01Lifecycle')->text());
    }

    public function testIndirectOwnershipCycleAndNestedCompositeOwnershipAreCovered(): void
    {
        $first = new Sa01CustomElement('paragraph');
        $second = new Sa01CustomElement('paragraph');
        $first->addElement($second);
        $second->addElement($first);

        $template = new OdtTemplate($this->templatePath());
        $this->expectException(SectionMutationException::class);
        $this->expectExceptionMessage('cycle');
        $template->appendBody($first);
    }

    public function testConstructionRejectsOwnershipCyclesButAllowsRepeatedReferences(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $cycle = new Sa01CustomElement('paragraph');
        $cycle->addElement($cycle);
        try {
            $template->appendBody($cycle);
            self::fail('Expected ownership cycle to be rejected.');
        } catch (SectionMutationException $exception) {
            self::assertStringContainsString('cycle', $exception->reason());
        }

        $paragraph = (new Paragraph())->addText('repeated');
        $content = (new RichText())->addElement($paragraph)->addElement($paragraph);
        $template->appendBody($content);
        self::assertSame(2, substr_count($this->workingContent($template), 'repeated'));
    }

    public function testConstructionRejectsImageReferenceWithoutPreparedOrExistingResource(): void
    {
        $template = new OdtTemplate($this->templatePath());
        try {
            $template->appendBody(new Sa01CustomElement('mismatched-image'));
            self::fail('Expected mismatched image resource to be rejected.');
        } catch (SectionMutationException $exception) {
            self::assertStringContainsString('resource', $exception->reason());
        }
        self::assertStringNotContainsString('SA01 mismatch', $this->workingContent($template));
    }

    public function testLegacyReplacementRetainsCountBasedBookmarkValidation(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->section('SimpleSection')->replaceContent(new class extends OdtElement {
            public function toDomNode(DOMDocument $dom): DOMNode
            {
                $paragraph = $dom->createElement('text:p');
                foreach (['end', 'start'] as $kind) {
                    $marker = $dom->createElement('text:bookmark-' . $kind);
                    $marker->setAttribute('text:name', 'LegacyReverse');
                    $paragraph->appendChild($marker);
                }
                return $paragraph;
            }
        });
        $xml = $this->workingContent($template);
        self::assertStringContainsString('text:bookmark-end text:name="LegacyReverse"', $xml);
        self::assertStringContainsString('text:bookmark-start text:name="LegacyReverse"', $xml);
    }

    public function testSuccessfulCustomRendererCannotMutateWorkingDocument(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->appendBody(new class extends OdtElement {
            public function toDomNode(DOMDocument $dom): DOMNode
            {
                $body = $dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0', 'text')->item(0);
                $body->appendChild($dom->createElementNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'text:p', 'SA01 unrequested mutation'));
                return $dom->createElement('text:p', 'SA01 requested content');
            }
        });
        $xml = $this->workingContent($template);
        self::assertStringContainsString('SA01 requested content', $xml);
        self::assertStringNotContainsString('SA01 unrequested mutation', $xml);
    }

    private function templatePath(): string
    {
        return __DIR__ . '/../fixtures/libreoffice-reference/odt/SECTION-AUTHORING-01-reference.odt';
    }

    private function outputPath(): string
    {
        $path = sys_get_temp_dir() . '/sa01-' . uniqid('', true) . '.odt';
        $this->outputs[] = $path;
        return $path;
    }

    private function entry(string $path, string $name): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            $value = $zip->getFromName($name);
            self::assertIsString($value);
            return $value;
        } finally {
            $zip->close();
        }
    }

    private function workingContent(OdtTemplate $template): string
    {
        $output = $this->outputPath();
        $template->save($output);
        return $this->entry($output, 'content.xml');
    }
}

final class Sa01CustomElement extends OdtElement
{
    public function __construct(private readonly string $kind)
    {
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        return match ($this->kind) {
            'text' => $dom->createTextNode('illegal'),
            'bookmark' => $this->bookmark($dom),
            'bookmark-end-first' => $this->bookmarkEndFirst($dom),
            'paragraph-with-section' => $this->paragraphWithSection($dom),
            'wrong-namespace' => $dom->createElementNS('urn:wrong', 'text:p', 'wrong'),
            'duplicate-section' => $this->duplicateSection($dom),
            'different-type-shared-name' => $this->differentTypeSharedName($dom),
            'named-table' => $this->namedTable($dom, 'SA01Table'),
            'duplicate-table' => $this->duplicateNamedTables($dom),
            'named-frame' => $this->namedFrame($dom, 'SA01Frame'),
            'duplicate-frame' => $this->duplicateNamedFrames($dom),
            'named-bookmark' => $this->namedBookmark($dom, 'SA01Bookmark'),
            'duplicate-bookmark' => $this->duplicateNamedBookmarks($dom),
            'mutate-and-fail' => $this->mutateAndFail($dom),
            'mismatched-image' => $this->mismatchedImage($dom),
            default => $dom->createElement('text:p', 'SA01 mismatch'),
        };
    }

    public function getOwnImageAssets(): array
    {
        return $this->kind === 'mismatched-image'
            ? [[
                'id' => 'Logo.png',
                'path' => dirname(__DIR__, 2) . '/assets/Logo.png',
            ]]
            : [];
    }

    private function bookmark(DOMDocument $dom): DOMNode
    {
        $paragraph = $dom->createElement('text:p');
        $start = $dom->createElement('text:bookmark-start');
        $start->setAttribute('text:name', 'SA01Broken');
        $paragraph->appendChild($start);
        return $paragraph;
    }

    private function mismatchedImage(DOMDocument $dom): DOMNode
    {
        $frame = $dom->createElement('draw:frame');
        $image = $dom->createElement('draw:image');
        $image->setAttribute('xlink:href', 'Pictures/NotLogo.png');
        $frame->appendChild($image);
        return $frame;
    }

    private function bookmarkEndFirst(DOMDocument $dom): DOMNode
    {
        $paragraph = $dom->createElement('text:p');
        $end = $dom->createElement('text:bookmark-end');
        $end->setAttribute('text:name', 'SA01Order');
        $start = $dom->createElement('text:bookmark-start');
        $start->setAttribute('text:name', 'SA01Order');
        $paragraph->appendChild($end);
        $paragraph->appendChild($start);
        return $paragraph;
    }

    private function paragraphWithSection(DOMDocument $dom): DOMNode
    {
        $paragraph = $dom->createElement('text:p');
        $section = $dom->createElement('text:section');
        $section->setAttribute('text:name', 'IllegalNested');
        $paragraph->appendChild($section);
        return $paragraph;
    }

    private function duplicateSection(DOMDocument $dom): DOMNode
    {
        $fragment = $dom->createDocumentFragment();
        foreach (['SA01Duplicate', 'SA01Duplicate'] as $name) {
            $section = $dom->createElement('text:section');
            $section->setAttribute('text:name', $name);
            $fragment->appendChild($section);
        }
        return $fragment;
    }

    private function differentTypeSharedName(DOMDocument $dom): DOMNode
    {
        $fragment = $dom->createDocumentFragment();
        $section = $dom->createElement('text:section');
        $section->setAttribute('text:name', 'TypeShared');
        $table = $dom->createElement('table:table');
        $table->setAttribute('table:name', 'TypeShared');
        $fragment->appendChild($section);
        $fragment->appendChild($table);
        return $fragment;
    }

    private function namedTable(DOMDocument $dom, string $name): DOMNode
    {
        $table = $dom->createElement('table:table');
        $table->setAttribute('table:name', $name);
        return $table;
    }

    private function duplicateNamedTables(DOMDocument $dom): DOMNode
    {
        $fragment = $dom->createDocumentFragment();
        $fragment->appendChild($this->namedTable($dom, 'SA01TableIntroduced'));
        $fragment->appendChild($this->namedTable($dom, 'SA01TableIntroduced'));
        return $fragment;
    }

    private function namedFrame(DOMDocument $dom, string $name): DOMNode
    {
        $frame = $dom->createElement('draw:frame');
        $frame->setAttribute('draw:name', $name);
        return $frame;
    }

    private function duplicateNamedFrames(DOMDocument $dom): DOMNode
    {
        $fragment = $dom->createDocumentFragment();
        $fragment->appendChild($this->namedFrame($dom, 'SA01FrameIntroduced'));
        $fragment->appendChild($this->namedFrame($dom, 'SA01FrameIntroduced'));
        return $fragment;
    }

    private function namedBookmark(DOMDocument $dom, string $name): DOMNode
    {
        $paragraph = $dom->createElement('text:p');
        $start = $dom->createElement('text:bookmark-start');
        $start->setAttribute('text:name', $name);
        $end = $dom->createElement('text:bookmark-end');
        $end->setAttribute('text:name', $name);
        $paragraph->appendChild($start);
        $paragraph->appendChild($end);
        return $paragraph;
    }

    private function duplicateNamedBookmarks(DOMDocument $dom): DOMNode
    {
        $fragment = $dom->createDocumentFragment();
        $fragment->appendChild($this->namedBookmark($dom, 'SA01BookmarkIntroduced'));
        $fragment->appendChild($this->namedBookmark($dom, 'SA01BookmarkIntroduced'));
        return $fragment;
    }

    private function mutateAndFail(DOMDocument $dom): DOMNode
    {
        $body = $dom->getElementsByTagName('office:text')->item(0);
        $body?->appendChild($dom->createElement('text:p', 'mutated'));
        throw new \RuntimeException('SA01 late renderer failure');
    }
}
