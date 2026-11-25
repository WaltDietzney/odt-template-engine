<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class NativeInlineFlowTest extends TestCase
{
    public function testExplicitInlineOrderAndLegacyTailAreIndependent(): void
    {
        $a = $this->box('inline');
        $tail = $this->box('legacy');
        $p = (new Paragraph())->addText('A')->addElement($tail)->addInlineElement($a)
            ->addText('B')->addUserField('field')->addText('C');
        $node = $p->toDomNode(new DOMDocument());
        self::assertSame(['#text', 'draw:frame', '#text', 'text:user-field-get', '#text', 'draw:frame'],
            array_map(static fn ($n) => $n->nodeName, iterator_to_array($node->childNodes)));
        self::assertSame([$a, $tail], $p->getEmbeddedElements());
        self::assertSame([$a, $tail], iterator_to_array($p->ownedElements()));
        $legacy = (new Paragraph())->addText('A')->addElement($this->box('old'))->addText('B');
        self::assertSame('ABold', $legacy->toDomNode(new DOMDocument())->textContent);
    }

    public function testBlockContentIsRejectedAndChangedAnchorsAreRevalidated(): void
    {
        $p = (new Paragraph())->addText('keep');
        foreach ([new Paragraph(), new DrawTextBox('block')] as $block) {
            try {
                $p->addInlineElement($block);
                self::fail('Expected block rejection');
            } catch (\InvalidArgumentException) {
                self::assertSame('keep', $p->toDomNode(new DOMDocument())->textContent);
            }
        }
        $box = $this->box('mutable');
        $p->addInlineElement($box);
        $box->setFrameAnchor('paragraph');
        $this->expectException(\InvalidArgumentException::class);
        $p->toDomNode(new DOMDocument());
    }

    public function testInlineImageFrameStylesAndFieldsSurviveBothDocumentPartsAndRepeatedSave(): void
    {
        $root = dirname(__DIR__, 2);
        $out = sys_get_temp_dir() . '/ordered-flow-' . bin2hex(random_bytes(6)) . '.odt';
        $repeat = $out . '.repeat.odt';
        $t = new PageLayoutOdtTemplate($root . '/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt');
        try {
            $t->declareUserField('value', 'native');
            $image = new ImageElement($root . '/assets/banner.png', ['anchor' => 'as-char', 'width' => '1cm']);
            $p = (new Paragraph())->setOutlineLevel(2)->addText('A')
                ->addInlineElement($image)->addText('B')->addInlineElement($this->box('body-box'))
                ->addText('C')->addUserField('value')->addText('D');
            $t->appendBody($p);
            $t->definePageLayout('FlowLayout', ['page-width' => '21cm', 'page-height' => '29.7cm']);
            $t->definePageStyle('FlowPage', 'FlowLayout', [
                'header' => (new Paragraph())->addText('H')->addInlineElement($this->box('header-box'))->addText('I'),
                'footer' => (new Paragraph())->addText('J')->addInlineElement($this->box('footer-box'))->addText('K'),
            ]);
            $t->save($out);
            $t->save($repeat);
            $a = new ZipArchive();
            $b = new ZipArchive();
            $a->open($out);
            $b->open($repeat);
            try {
                foreach (['content.xml', 'styles.xml', 'META-INF/manifest.xml'] as $part) {
                    self::assertSame($a->getFromName($part), $b->getFromName($part));
                }
                self::assertNotFalse($a->getFromName('Pictures/banner.png'));
                $d = new DOMDocument();
                $d->loadXML($a->getFromName('content.xml'));
                $h = $d->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'h')->item(0);
                self::assertNotNull($h);
                self::assertSame(['#text', 'draw:frame', '#text', 'draw:frame', '#text', 'text:user-field-get', '#text'],
                    array_map(static fn ($n) => $n->nodeName, iterator_to_array($h->childNodes)));
                $d->loadXML($a->getFromName('styles.xml'));
                self::assertSame(2, $d->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'frame')->length);
                self::assertStringContainsString('fo:background-color="#DCE8F2"', $a->getFromName('styles.xml'));
            } finally {
                $a->close();
                $b->close();
            }
            $reopened = new PageLayoutOdtTemplate($out);
            try {
                $reopened->setUserField('value', 'rebound');
                $reopened->save($repeat);
            } finally {
                $reopened->cleanup();
            }
        } finally {
            $t->cleanup();
            foreach ([$out, $repeat] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function testOwnershipCycleIsRejectedWithoutChangingTheWorkingDocument(): void
    {
        $root = dirname(__DIR__, 2);
        $t = new FlowInspectableTemplate($root . '/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt');
        try {
            $before = $t->xml();
            $p = new Paragraph();
            $box = $this->box('cycle');
            $box->addElement($p);
            $p->addInlineElement($box);
            try {
                $t->appendBody($p);
                self::fail('Expected ownership-cycle rejection');
            } catch (\OdtTemplateEngine\Document\SectionMutationException) {
                self::assertSame($before, $t->xml());
            }
        } finally {
            $t->cleanup();
        }
    }

    private function box(string $name): DrawTextBox
    {
        return (new DrawTextBox($name, ['background-color' => '#DCE8F2']))
            ->setFrameLayout(['anchor' => 'as-char', 'width' => '1cm', 'height' => '0.5cm'])
            ->addElement((new Paragraph())->addText($name));
    }
}

final class FlowInspectableTemplate extends PageLayoutOdtTemplate
{
    public function xml(): string
    {
        return $this->documentContext()->contentDom()->saveXML() . $this->documentContext()->stylesDom()->saveXML();
    }
}
