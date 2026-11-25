<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementCollector;
use OdtTemplateEngine\Elements\DrawingGroup;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\DrawingLayoutProjector;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class DrawingCompositionTest extends TestCase
{
    public function testLegacyStandaloneShapeAndTextRequirementsRemainUnprojected(): void
    {
        $shape = $this->shape()->addParagraph(
            (new Paragraph(null, ['text-align' => 'left']))->addText('Text', ['font-size' => '9pt'])
        );
        $requirements = iterator_to_array((new StyleRequirementCollector())->collectSemantic($shape), false);
        self::assertSame('graphic', $requirements[0]->family());
        self::assertSame(StyleRequirement::PART_CONTENT, $requirements[0]->documentPart());
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirements[1]->scope());
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirements[2]->scope());
        $node = $shape->toDomNode(new DOMDocument());
        self::assertSame('paragraph', $node->getAttribute('text:anchor-type'));
        self::assertSame('2cm', $node->getAttribute('svg:width'));
    }

    public function testLegacyStandaloneImageRetainsCommonWriterFrameStyle(): void
    {
        $image = (new ImageElement($this->root() . '/assets/banner.png'))->setFrameLayout([
            'anchor' => 'paragraph', 'width' => '1cm', 'height' => '1cm', 'wrap' => 'run-through',
        ]);
        $requirements = iterator_to_array((new StyleRequirementCollector())->collectSemantic($image), false);
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirements[0]->scope());
        self::assertSame('Frame', $requirements[0]->parentStyleName());
    }

    public function testInlineAutoGrowProjectsOwnedTextAndPreservesExplicitMarginsAndLayers(): void
    {
        $layout = DrawingLayout::fromArray([
            'anchor' => 'as-char', 'width' => '1cm', 'height' => '0.2cm',
            'margin' => ['left' => '0.08cm', 'right' => '0cm'], 'z-index' => 7,
        ]);
        self::assertSame(7, $layout->withWrap('none')->zIndex());
        self::assertSame(['left' => '0.08cm', 'right' => '0cm'], $layout->withWrap('none')->margins());
        $shape = $this->shape($layout)->setTextAutoGrow()->addParagraph(
            (new Paragraph())->addText('Native', ['font-size' => '9pt', 'font-family' => 'Liberation Sans'])
        );
        $requirements = iterator_to_array((new StyleRequirementCollector())->collectSemantic($shape), false);
        foreach ($requirements as $requirement) {
            self::assertSame(StyleRequirement::SCOPE_AUTOMATIC, $requirement->scope());
            self::assertTrue($requirement->isDrawingProjection());
            self::assertStringStartsWith('drawing_c_', $requirement->name());
        }
        $node = (new Paragraph())->addText('A')->addInlineElement($shape)->addText('B')->toDomNode(new DOMDocument());
        self::assertSame('draw:custom-shape', $node->childNodes->item(1)->nodeName);
        self::assertSame('7', $node->childNodes->item(1)->getAttribute('draw:z-index'));
    }

    public function testGroupHasOneAnchorLocalCoordinatesAndStablePaintOrder(): void
    {
        $back = $this->shape(DrawingLayout::fromArray([
            'anchor' => 'paragraph', 'width' => '1cm', 'height' => '1cm',
            'horizontal' => ['offset' => '-0.5cm', 'relative-to' => 'paragraph-content'],
        ]));
        $image = (new ImageElement($this->root() . '/assets/banner.png'))->setFrameLayout([
            'anchor' => 'paragraph', 'width' => '0.5cm', 'height' => '0.5cm',
            'wrap' => 'run-through', 'run-through' => 'foreground',
        ]);
        $group = new DrawingGroup('icon', ['back' => $back, 'icon' => $image]);
        $node = $group->toDomNode(new DOMDocument());
        self::assertSame('as-char', $node->getAttribute('text:anchor-type'));
        self::assertFalse($node->hasAttribute('svg:x'));
        self::assertFalse($node->hasAttribute('svg:width'));
        self::assertSame('-0.5cm', $node->firstChild->getAttribute('svg:x'));
        self::assertFalse($node->firstChild->hasAttribute('text:anchor-type'));
        self::assertSame('0', $node->firstChild->getAttribute('draw:z-index'));
        self::assertSame('1', $node->lastChild->getAttribute('draw:z-index'));
        $requirements = iterator_to_array((new StyleRequirementCollector())->collectSemantic($group), false);
        self::assertSame('Graphics', $requirements[count($requirements) - 1]->parentStyleName());
        $nonThrough = new DrawingGroup('non-through', [$back], DrawingLayout::fromArray([
            'anchor' => 'paragraph', 'wrap' => 'none',
        ]));
        foreach ($nonThrough->getOwnStyleRequirements() as $requirement) {
            self::assertArrayNotHasKey('style:run-through', $requirement->propertyGroups()['style:graphic-properties']);
        }
    }

    public function testGroupRejectsFrameGeometryAndWriterFramesBeforeUse(): void
    {
        foreach ([['width' => '2cm'], ['horizontal' => ['offset' => '1cm', 'relative-to' => 'paragraph']]] as $options) {
            try {
                new DrawingGroup('bad', [$this->shape()], DrawingLayout::fromArray(['anchor' => 'paragraph', ...$options]));
                self::fail('Expected group coordinate rejection.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
        $this->expectException(InvalidArgumentException::class);
        new DrawingGroup('field-frame', [new DrawTextBox('native')]);
    }

    public function testNativeFieldsAreRejectedInNewDrawingTextButRemainValidOutsideIt(): void
    {
        $paragraph = (new Paragraph())->addUserField('contact.email');
        $dom = new DOMDocument();
        $node = $paragraph->toDomNode($dom);
        self::assertSame('text:user-field-get', $node->firstChild->nodeName);
        $shape = $this->shape()->addParagraph($paragraph);
        $this->expectException(InvalidArgumentException::class);
        $shape->toDomNode(new DOMDocument());
    }

    public function testRunThroughLayersAreValidatedAndSurviveLayoutUpdates(): void
    {
        $layout = DrawingLayout::fromArray([
            'anchor' => 'paragraph', 'width' => '2cm', 'height' => '1cm',
            'wrap' => 'run-through', 'run-through' => 'background', 'z-index' => 0,
        ]);
        $properties = (new DrawingLayoutProjector())->graphicLayoutProperties($layout->withHorizontalOffset('1cm'));
        self::assertSame('background', $properties['style:run-through']);
        $requirements = iterator_to_array((function () use ($layout): iterable {
            yield from $this->shape($layout)->getOwnStyleRequirements();
        })());
        self::assertSame('background', $requirements[0]->propertyGroups()['style:graphic-properties']['style:run-through']);
        foreach ([
            ['wrap' => 'none', 'run-through' => 'foreground'],
            ['wrap' => 'run-through', 'run-through' => 'invalid'],
            ['z-index' => -1], ['z-index' => '2'], ['margin' => ['left' => '-1cm']],
        ] as $options) {
            try {
                DrawingLayout::fromArray($options);
                self::fail('Expected invalid layout.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testScopedHeaderStylesAndImageResourcesAreMaterializedWithoutAdapters(): void
    {
        $out = sys_get_temp_dir() . '/drawing-scope-' . bin2hex(random_bytes(6)) . '.odt';
        $repeat = $out . '.repeat.odt';
        $template = new PageLayoutOdtTemplate($this->fixture());
        try {
            $shape = $this->shape(DrawingLayout::fromArray([
                'anchor' => 'as-char', 'width' => '1cm', 'height' => '0.2cm',
            ]))->setTextAutoGrow()->addParagraph(
                (new Paragraph(null, ['fo:margin' => '0cm']))->addText('Header tag', [
                    'font-family' => 'Liberation Sans', 'font-size' => '9pt',
                ])
            );
            $image = (new ImageElement($this->root() . '/assets/banner.png'))->setFrameLayout([
                'anchor' => 'paragraph', 'width' => '1cm', 'height' => '1cm',
                'wrap' => 'run-through', 'run-through' => 'foreground',
            ]);
            $group = new DrawingGroup('header-image', [$image]);
            $template->definePageLayout('DrawingLayout', ['page-width' => '21cm', 'page-height' => '29.7cm']);
            $template->definePageStyle('DrawingPage', 'DrawingLayout', [
                'header' => (new Paragraph())->addInlineElement($shape)->addInlineElement($group),
            ]);
            $template->appendBody((new Paragraph())->addInlineElement($shape));
            $template->save($out);
            $template->save($repeat);
            $a = new ZipArchive();
            $b = new ZipArchive();
            $a->open($out);
            $b->open($repeat);
            foreach (['content.xml', 'styles.xml'] as $part) {
                self::assertSame($a->getFromName($part), $b->getFromName($part));
                $document = new DOMDocument();
                $document->loadXML($a->getFromName($part));
                $node = $document->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'custom-shape')->item(0);
                self::assertNotNull($node);
                $prefix = $part === 'content.xml' ? 'drawing_c_' : 'drawing_s_';
                self::assertStringStartsWith($prefix, $node->getAttribute('draw:style-name'));
                $span = $node->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'span')->item(0);
                self::assertStringStartsWith($prefix, $span->getAttribute('text:style-name'));
            }
            $styles = new DOMDocument();
            $styles->loadXML($a->getFromName('styles.xml'));
            $imageNode = $styles->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'image')->item(0);
            self::assertNotNull($imageNode);
            self::assertNotFalse($a->getFromName($imageNode->getAttribute('xlink:href')));
            $a->close();
            $b->close();
        } finally {
            $template->cleanup();
            foreach ([$out, $repeat] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function testAuthoredProjectedStyleCollisionFailsWithoutOverwritingSource(): void
    {
        $path = sys_get_temp_dir() . '/drawing-conflict-' . bin2hex(random_bytes(6)) . '.odt';
        copy($this->fixture(), $path);
        $shape = $this->shape(DrawingLayout::fromArray([
            'anchor' => 'as-char', 'width' => '1cm', 'height' => '1cm',
        ]))->setTextAutoGrow();
        $requirements = iterator_to_array((new StyleRequirementCollector())->collectSemantic($shape), false);
        $zip = new ZipArchive();
        $zip->open($path);
        $document = new DOMDocument();
        $document->loadXML($zip->getFromName('content.xml'));
        $style = $document->createElement('style:style');
        $style->setAttribute('style:name', $requirements[0]->name());
        $style->setAttribute('style:family', 'graphic');
        $properties = $document->createElement('style:graphic-properties');
        $properties->setAttribute('draw:fill-color', '#FF0000');
        $style->appendChild($properties);
        $document->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0', 'automatic-styles')->item(0)->appendChild($style);
        $zip->addFromString('content.xml', $document->saveXML());
        $zip->close();
        $template = new DrawingInspectableTemplate($path);
        try {
            $before = $template->xml();
            try {
                $template->appendBody((new Paragraph())->addInlineElement($shape));
                self::fail('Expected style collision.');
            } catch (InvalidArgumentException) {
                self::assertSame($before, $template->xml());
            }
        } finally {
            $template->cleanup();
            unlink($path);
        }
    }

    private function shape(?DrawingLayout $layout = null): ShapeElement
    {
        return new ShapeElement(
            ShapeGeometry::preset('round-rectangle'),
            ShapeAppearance::solid('#DCE8F2'),
            $layout ?? DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '2cm', 'height' => '1cm'])
        );
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function fixture(): string
    {
        return $this->root() . '/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt';
    }
}

final class DrawingInspectableTemplate extends OdtTemplate
{
    public function xml(): string
    {
        return $this->documentContext()->contentDom()->saveXML() . $this->documentContext()->stylesDom()->saveXML();
    }
}
