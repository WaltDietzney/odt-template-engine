<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class NativeHeadingConstructionTest extends TestCase
{
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

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

    public function testDefaultAndHeadingNamedStylesRemainOrdinaryParagraphs(): void
    {
        foreach ([null, 'Heading 1', 'Heading_20_1', 'CustomHeading'] as $style) {
            $p = (new Paragraph($style))->addText('Same text');
            $node = $p->toDomNode(new DOMDocument());
            self::assertSame('text:p', $node->nodeName);
            self::assertFalse($node->hasAttribute('text:outline-level'));
            self::assertSame($style ?? 'Standard', $node->getAttribute('text:style-name'));
        }
    }

    public function testLegacyTextBoxSerializationStillSuppressesParagraphStyle(): void
    {
        $node = (new Paragraph('Heading 1'))->addText('Text')->toDomNode(new DOMDocument(), true);
        self::assertSame('text:p', $node->nodeName);
        self::assertFalse($node->hasAttribute('text:style-name'));
    }

    public function testLegacyListWrapperRemainsUnchanged(): void
    {
        $node = (new Paragraph())->setBulleted()->addText('Item')->toDomNode(new DOMDocument());
        self::assertSame('text:list', $node->nodeName);
        self::assertSame('text:list-item', $node->firstChild->nodeName);
        self::assertSame('text:p', $node->firstChild->firstChild->nodeName);
    }

    public function testExistingSubclassSerializationDispatchIsPreserved(): void
    {
        $p = new class extends Paragraph {
            public bool $called = false;

            public function toDomNode(DOMDocument $dom, bool $insideTextBox = false): DOMNode
            {
                $this->called = true;
                return parent::toDomNode($dom, $insideTextBox);
            }
        };
        (new RichText())->addParagraph($p)->toDomNode(new DOMDocument());
        self::assertTrue($p->called);
    }

    public function testAllSupportedLevelsAreNativeAndIndependentOfStyles(): void
    {
        for ($level = 1; $level <= 10; ++$level) {
            foreach ([null, 'CustomBodyStyle'] as $style) {
                $p = new Paragraph($style);
                self::assertSame($p, $p->setOutlineLevel($level));
                $node = $p->addText('Überschrift & <Text>')->toDomNode(new DOMDocument());
                self::assertSame('h', $node->localName);
                self::assertSame(self::TEXT, $node->namespaceURI);
                self::assertSame((string) $level, $node->getAttributeNS(self::TEXT, 'outline-level'));
                self::assertSame($style ?? 'Standard', $node->getAttribute('text:style-name'));
                self::assertSame('Überschrift & <Text>', $node->textContent);
            }
        }
    }

    public function testInvalidLevelDoesNotChangeTheExistingParagraphAndNullResets(): void
    {
        $p = (new Paragraph('KeepStyle'))->setOutlineLevel(3)->addText('Keep text');
        $dom = new DOMDocument();
        $before = $dom->saveXML($p->toDomNode($dom));
        foreach ([-1, 0, 11, PHP_INT_MAX] as $level) {
            try {
                $p->setOutlineLevel($level);
                self::fail('Expected invalid outline level');
            } catch (\InvalidArgumentException) {
                self::assertSame($before, $dom->saveXML($p->toDomNode($dom)));
            }
        }
        self::assertSame($p, $p->setOutlineLevel(null));
        $node = $p->toDomNode($dom);
        self::assertSame('text:p', $node->nodeName);
        self::assertFalse($node->hasAttribute('text:outline-level'));
        self::assertSame('KeepStyle', $node->getAttribute('text:style-name'));
        self::assertSame('Keep text', $node->textContent);
    }

    public function testListAndTextBoxOptionsDoNotSilentlyRemoveExplicitHeadingSemantics(): void
    {
        $p = (new Paragraph())->setOutlineLevel(2)->setBulleted()->addText('Heading');
        $node = $p->toDomNode(new DOMDocument());
        self::assertSame('text:list', $node->nodeName);
        self::assertSame('text:h', $node->firstChild->firstChild->nodeName);
        $inner = $p->toDomNode(new DOMDocument(), true);
        self::assertSame('text:h', $inner->nodeName);
        self::assertSame('2', $inner->getAttributeNS(self::TEXT, 'outline-level'));
        self::assertFalse($inner->hasAttribute('text:style-name'));
    }

    public function testConstructRenderBindRepeatSaveAndReloadPreserveNativeHeadingAndFields(): void
    {
        $t = new OdtTemplate($this->fixture());
        try {
            $source = $t->inspectTemplate()->toArray();
            $t->declareUserField('subject', 'initial');
            $p = (new Paragraph(null, ['font-size' => '15pt', 'bold' => true]))
                ->setOutlineLevel(2)->addText('Vor ')->addUserField('subject')->addText(' Nach');
            $t->appendBody((new RichText())->addParagraph($p)->addParagraph('Body text'));
            $t->setUserField('subject', 'Native Überschrift');
            $t->render();
            $a = $this->path();
            $b = $this->path();
            $t->save($a);
            $t->save($b);
            foreach (['content.xml', 'styles.xml'] as $part) {
                self::assertSame($this->part($a, $part), $this->part($b, $part));
            }
            $dom = new DOMDocument();
            $dom->loadXML($this->part($a, 'content.xml'));
            $headings = $dom->getElementsByTagNameNS(self::TEXT, 'h');
            self::assertCount(1, $headings);
            self::assertSame('2', $headings->item(0)->getAttributeNS(self::TEXT, 'outline-level'));
            self::assertCount(1, $headings->item(0)->getElementsByTagNameNS(self::TEXT, 'user-field-get'));
            self::assertSame('Vor  Nach', $headings->item(0)->textContent);
            self::assertSame($source, $t->inspectTemplate()->toArray());
            $reopened = new OdtTemplate($a);
            try {
                $reopened->setUserField('subject', 'Reopened');
                $reopened->save($b);
                $dom->loadXML($this->part($b, 'content.xml'));
                self::assertCount(1, $dom->getElementsByTagNameNS(self::TEXT, 'h'));
                self::assertStringContainsString('Reopened', $this->part($b, 'content.xml'));
            } finally {
                $reopened->cleanup();
            }
        } finally {
            $t->cleanup();
        }
    }

    public function testPlaceholderMaterializationConstructsAHeadingInsteadOfAnOrdinaryParagraph(): void
    {
        $path = $this->fixture();
        $zip = new ZipArchive();
        $zip->open($path);
        $dom = new DOMDocument();
        $dom->loadXML($zip->getFromName('content.xml'));
        $body = $dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0', 'text')->item(0);
        $p = $dom->createElementNS(self::TEXT, 'text:p');
        $p->appendChild($dom->createTextNode('{{heading}}'));
        $body->appendChild($p);
        $zip->addFromString('content.xml', $dom->saveXML());
        $zip->close();
        $t = new OdtTemplate($path);
        try {
            $t->setElement('heading', (new Paragraph())->setOutlineLevel(1)->addText('Constructed'));
            $t->render();
            $out = $this->path();
            $t->save($out);
            $dom->loadXML($this->part($out, 'content.xml'));
            self::assertCount(1, $dom->getElementsByTagNameNS(self::TEXT, 'h'));
            self::assertStringNotContainsString('{{heading}}', $this->part($out, 'content.xml'));
        } finally {
            $t->cleanup();
        }
    }

    public function testInlineFrameMaterializationPreservesTheConstructedHeadingCarrier(): void
    {
        $t = new OdtTemplate($this->fixture());
        try {
            $t->appendBody((new Paragraph())->setOutlineLevel(2)->addText('Before {{badge}} after'));
            $badge = (new \OdtTemplateEngine\Elements\DrawTextBox('badge'))
                ->setFrameLayout(['anchor' => 'as-char', 'width' => '1cm', 'height' => '0.5cm'])
                ->addElement((new Paragraph())->addText('Tag'));
            $t->setElement('badge', $badge);
            $t->render();
            $out = $this->path();
            $t->save($out);
            $dom = new DOMDocument();
            $dom->loadXML($this->part($out, 'content.xml'));
            $headings = $dom->getElementsByTagNameNS(self::TEXT, 'h');
            self::assertCount(1, $headings);
            $heading = $headings->item(0);
            self::assertSame('2', $heading->getAttributeNS(self::TEXT, 'outline-level'));
            self::assertSame('Before Tag after', $heading->textContent);
            self::assertCount(1, $heading->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'frame'));
        } finally {
            $t->cleanup();
        }
    }

    public function testHeaderAndFooterSerializeHeadingsWithoutClaimingBodyOutlineMembership(): void
    {
        $t = new \OdtTemplateEngine\PageLayoutOdtTemplate($this->fixture());
        try {
            $t->definePageLayout('HeadingLayout', ['page-width' => '21cm', 'page-height' => '29.7cm']);
            $t->definePageStyle('HeadingPage', 'HeadingLayout', [
                'header' => (new Paragraph())->setOutlineLevel(2)->addText('Header'),
                'footer' => (new Paragraph())->setOutlineLevel(3)->addText('Footer'),
            ]);
            $out = $this->path();
            $t->save($out);
            $dom = new DOMDocument();
            $dom->loadXML($this->part($out, 'styles.xml'));
            $headings = $dom->getElementsByTagNameNS(self::TEXT, 'h');
            self::assertCount(2, $headings);
            self::assertSame('2', $headings->item(0)->getAttributeNS(self::TEXT, 'outline-level'));
            self::assertSame('3', $headings->item(1)->getAttributeNS(self::TEXT, 'outline-level'));
        } finally {
            $t->cleanup();
        }
    }

    private function path(): string
    {
        $path = sys_get_temp_dir() . '/native-heading-' . bin2hex(random_bytes(8)) . '.odt';
        $this->paths[] = $path;
        return $path;
    }

    private function fixture(): string
    {
        $path = $this->path();
        copy(dirname(__DIR__) . '/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt', $path);
        $zip = new ZipArchive();
        $zip->open($path);
        $dom = new DOMDocument();
        $dom->loadXML($zip->getFromName('content.xml'));
        $body = $dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0', 'text')->item(0);
        while ($body->firstChild !== null) {
            $body->removeChild($body->firstChild);
        }
        $zip->addFromString('content.xml', $dom->saveXML());
        $zip->close();
        return $path;
    }

    private function part(string $path, string $part): string
    {
        $zip = new ZipArchive();
        $zip->open($path);
        $result = $zip->getFromName($part);
        $zip->close();
        return $result;
    }
}
