<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\SectionMutationException;
use OdtTemplateEngine\Document\SectionTarget;
use OdtTemplateEngine\Document\TypedTargetResolver;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class SectionTargetMutationTest extends TestCase
{
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TABLE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testParagraphReplacementPreservesSectionIdentityAttributesAndSiblings(): void
    {
        [$context, $section] = $this->contextWithSection();
        $beforeSection = $section->C14N();
        $target = $this->target($context);

        self::assertSame($target, $target->replaceContent((new Paragraph())->addText('New content')));
        self::assertSame('ExperienceEntry', $target->descriptor()->name());
        self::assertSame('New content', $target->text());
        self::assertSame('Sect1', $section->getAttribute('text:style-name'));
        self::assertSame('Outside', $section->nextSibling?->textContent);
        self::assertNotSame($beforeSection, $section->C14N());
    }

    public function testRichTextListAndTableAreMaterializedAsSectionBlocks(): void
    {
        [$context] = $this->contextWithSection();
        $rich = (new RichText())
            ->addParagraph('First')
            ->addParagraph('Second');
        $this->target($context)->replaceContent($rich);
        self::assertSame("First\nSecond", $this->target($context)->text());

        $list = new ListElement('bullet');
        $list->addItem((new Paragraph())->addText('Item'));
        $this->target($context)->replaceContent($list);
        self::assertSame('Item', $this->target($context)->text());

        $table = (new RichTable())->addRow([
            new RichTableCell('A'),
            new RichTableCell('B'),
        ]);
        $this->target($context)->replaceContent($table);
        self::assertSame("A\nB", $this->target($context)->text());
    }

    public function testEmptyRichTextClearsChildrenButKeepsSectionAddressable(): void
    {
        [$context, $section] = $this->contextWithSection();

        $this->target($context)->replaceContent(new RichText());

        self::assertSame('', $this->target($context)->text());
        self::assertSame(0, $section->childNodes->length);
        self::assertSame('ExperienceEntry', $this->target($context)->name());
    }

    public function testOldNestedIdentityDisappearsAndNewNestedIdentityAppears(): void
    {
        [$context] = $this->contextWithSection(true);
        $replacement = new RichText();
        $replacement->addTable((new RichTable())->setTableName('NewTable')->addRow(['Cell']));

        $this->target($context)->replaceContent($replacement);
        $objects = $this->target($context)->nestedNamedObjects();
        $names = array_map(static fn ($object): string => $object->name(), $objects);

        self::assertNotContains('OldTable', $names);
        self::assertContains('NewTable', $names);
    }

    public function testSameTypeCollisionFailsAtomicallyButDifferentTypeNameIsAllowed(): void
    {
        [$context, $section] = $this->contextWithSection();
        $outside = $context->contentDom()->createElementNS(self::TABLE, 'table:table');
        $outside->setAttribute('table:name', 'Shared');
        $context->contentDom()->documentElement->appendChild($outside);
        $before = $context->contentDom()->saveXML();

        $table = (new RichTable())->setTableName('Shared')->addRow(['Cell']);
        try {
            $this->target($context)->replaceContent($table);
            self::fail('Expected a same-type collision.');
        } catch (SectionMutationException $exception) {
            self::assertSame('table', $exception->conflictingType());
            self::assertSame('Shared', $exception->conflictingName());
        }
        self::assertSame($before, $context->contentDom()->saveXML());
        self::assertSame('Old content', $this->target($context)->text());

        $differentType = (new RichTable())->setTableName('ExperienceEntry')->addRow(['Cell']);
        $this->target($context)->replaceContent($differentType);
        self::assertSame('ExperienceEntry', $section->getAttribute('text:name'));
    }

    public function testResourceBearingContentIsRejectedAtomically(): void
    {
        [$context] = $this->contextWithSection();
        $before = $context->contentDom()->saveXML();
        $image = new ImageElement(__DIR__ . '/../../assets/Logo.png');

        $this->expectException(SectionMutationException::class);
        try {
            $this->target($context)->replaceContent($image);
        } finally {
            self::assertSame($before, $context->contentDom()->saveXML());
        }
    }

    public function testInlineOnlyMaterializationIsRejectedAtomically(): void
    {
        [$context] = $this->contextWithSection();
        $before = $context->contentDom()->saveXML();
        $inline = new class extends \OdtTemplateEngine\Elements\OdtElement {
            public function toDomNode(DOMDocument $dom): \DOMNode
            {
                return $dom->createElement('text:span');
            }

            public function registerStyles(): void
            {
            }
        };

        try {
            $this->target($context)->replaceContent($inline);
            self::fail('Expected inline content to be rejected.');
        } catch (SectionMutationException $exception) {
            self::assertSame('replaceContent', $exception->operation());
        }
        self::assertSame($before, $context->contentDom()->saveXML());
    }

    public function testTargetUsesCurrentSectionAfterContextReplacement(): void
    {
        [$context] = $this->contextWithSection();
        $target = $this->target($context);
        [, $replacement] = $this->contextWithSection();

        $context->replaceCoreDocuments($replacement->ownerDocument, $this->document(), $this->document());
        $target->replaceContent((new Paragraph())->addText('Current'));

        self::assertSame('Current', $target->text());
    }

    public function testReplacementSurvivesPackageSaveAndReopen(): void
    {
        $template = new class ('samples/templates/template_01_simple_variables.odt') extends OdtTemplate {
            public function addSection(): void
            {
                $dom = $this->documentContext()->contentDom();
                $section = $dom->createElementNS(
                    'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
                    'text:section'
                );
                $section->setAttribute('text:name', 'Persisted');
                $section->appendChild($dom->createElement('text:p'));
                $dom->getElementsByTagNameNS(
                    'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
                    'text'
                )->item(0)?->appendChild($section);
            }
        };
        $template->addSection();
        $template->section('Persisted')->replaceContent((new Paragraph())->addText('Saved content'));
        $output = tempnam(sys_get_temp_dir(), 'odt-section-') . '.odt';
        $template->save($output);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($output));
        foreach (['content.xml', 'styles.xml', 'meta.xml', 'META-INF/manifest.xml'] as $part) {
            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($zip->getFromName($part)));
        }
        $zip->close();
        $reopened = new OdtTemplate($output);

        self::assertSame('Saved content', $reopened->section('Persisted')->text());
        unlink($output);
    }

    private function target(OdtDocumentContext $context): SectionTarget
    {
        return (new TypedTargetResolver())->resolveSection($context, 'ExperienceEntry');
    }

    /** @return array{OdtDocumentContext, DOMElement} */
    private function contextWithSection(bool $withNestedTable = false): array
    {
        $dom = $this->document();
        $section = $dom->createElementNS(self::TEXT, 'text:section');
        $section->setAttribute('text:name', 'ExperienceEntry');
        $section->setAttribute('text:style-name', 'Sect1');
        $section->appendChild($this->paragraph($dom, 'Old content'));
        if ($withNestedTable) {
            $table = $dom->createElementNS(self::TABLE, 'table:table');
            $table->setAttribute('table:name', 'OldTable');
            $section->appendChild($table);
        }
        $dom->documentElement->appendChild($section);
        $dom->documentElement->appendChild($this->paragraph($dom, 'Outside'));

        return [new OdtDocumentContext($dom, $this->document(), $this->document()), $section];
    }

    private function paragraph(DOMDocument $dom, string $text): DOMElement
    {
        $paragraph = $dom->createElementNS(self::TEXT, 'text:p');
        $paragraph->appendChild($dom->createTextNode($text));
        return $paragraph;
    }

    private function document(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(sprintf(
            '<office:document-content xmlns:office="%s" xmlns:text="%s" xmlns:table="%s"/>',
            self::OFFICE,
            self::TEXT,
            self::TABLE
        ));
        return $dom;
    }
}
