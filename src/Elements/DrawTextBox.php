<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMElement;
use DOMNode;
use OdtTemplateEngine\Contracts\HasStyles;
use OdtTemplateEngine\Utils\StyleMapper;

/**
 * Represents a styled text box (draw:frame with draw:text-box) in an ODT document.
 */
class DrawTextBox extends OdtElement implements HasStyles
{
    protected string $name;
    protected array $frameOptions = [];
    protected array $paragraphs = [];
    protected string $frameStyleName = '';

    public function __construct(string $name, array $options = [])
    {
        $this->name = $name;
        $this->frameOptions = $options;
        // register frame style immediately
        $this->registerFrameStyle();
         $this->frameStyleProps = StyleMapper::mapFrameStyleOptions($options);

    }

    /**
     * Add a Paragraph or RichText element into this text box.
     */
    public function addElement(OdtElement $element): self
    {
        $this->paragraphs[] = $element;
        return $this;
    }

    /**
     * Map frame options into a style and register it.
     */
    protected function registerFrameStyle(): void
    {
        $styleDef = StyleMapper::mapFrameStyleOptions($this->frameOptions);
        $this->frameStyleName = StyleMapper::generateStyleName($styleDef);
        StyleMapper::registerParagraphStyle($this->frameStyleName, $styleDef);
        // also register frame-level (graphic) style
        StyleMapper::$frameStyles[$this->frameStyleName] = $styleDef;
    }

    /**
     * HasStyles interface: define style definitions for this DrawTextBox.
     */
    public function getStyleDefinitions(): array
    {
        return [$this->frameStyleName => StyleMapper::mapFrameStyleOptions($this->frameOptions)];
    }

    /**
     * Converts this element into a DOMElement (draw:frame).
     */
    public function toDomNode(DOMDocument $dom): DOMElement
    {
        $this->registerFrameStyle();

        $frame = $dom->createElement('draw:frame');
        $frame->setAttribute('draw:name', $this->name);
        $frame->setAttribute('text:anchor-type', $this->frameOptions['anchor'] ?? 'paragraph');
        $frame->setAttribute('draw:z-index', '0');
        $frame->setAttribute('draw:style-name', $this->frameStyleName);

        // size attributes
        if (!empty($this->frameOptions['width'])) {
            $frame->setAttribute('svg:width', $this->frameOptions['width']);
        }
        if (!empty($this->frameOptions['height'])) {
            $frame->setAttribute('svg:height', $this->frameOptions['height']);
        }

        $textBox = $dom->createElement('draw:text-box');
        foreach ($this->paragraphs as $element) {
            $child = $element->toDomNode($dom);
            if ($child instanceof \DOMDocumentFragment) {
                foreach ($child->childNodes as $node) {
                    $textBox->appendChild($node->cloneNode(true));
                }
            } else {
                $textBox->appendChild($child);
            }
        }
        $frame->appendChild($textBox);
        return $frame;
    }

    /**
     * Inserts the style definition into styles.xml
     */
    public function toStyleDomNode(DOMDocument $dom): ?DOMElement
    {
        $styleNode = $dom->createElement('style:style');
        $styleNode->setAttribute('style:name', $this->frameStyleName);
        $styleNode->setAttribute('style:family', 'graphic');
        $styleNode->setAttribute('style:parent-style-name', 'Frame');

        $props = $dom->createElement('style:graphic-properties');
        foreach (StyleMapper::mapFrameStyleOptions($this->frameOptions) as $key => $val) {
            $props->setAttribute($key, $val);
        }
        $styleNode->appendChild($props);
        return $styleNode;
    }

    // Fluent API for styling:
    public function setBackground(string $color): self
    {
        $this->frameOptions['background-color'] = $color;
        $this->registerFrameStyle();
        return $this;
    }

    public function setFill(string $fill): self
    {
        $this->frameOptions['fill'] = $fill;
        $this->registerFrameStyle();
        return $this;
    }

    public function setFillColor(string $color): self
    {
        $this->frameOptions['fill-color'] = $color;
        $this->registerFrameStyle();
        return $this;
    }

    public function setAllowOverlap(bool $allow = true): self
    {
        $this->frameOptions['allow-overlap'] = $allow ? 'true' : 'false';
        $this->registerFrameStyle();
        return $this;
    }

    public function setVerticalPos(string $pos, string $rel = 'baseline'): self
    {
        $this->frameOptions['vertical-pos'] = $pos;
        $this->frameOptions['vertical-rel'] = $rel;
        $this->registerFrameStyle();
        return $this;
    }

    public function setHorizontalPos(string $pos, string $rel = 'char'): self
    {
        $this->frameOptions['horizontal-pos'] = $pos;
        $this->frameOptions['horizontal-rel'] = $rel;
        $this->registerFrameStyle();
        return $this;
    }

    /**
     * Set horizontal position properties (e.g. style:horizontal-pos and style:horizontal-rel).
     */
    public function setHorizontalPosition(string $pos, string $rel = 'page'): self
    {
        $this->frameOptions['style:horizontal-pos'] = $pos;
        $this->frameOptions['style:horizontal-rel'] = $rel;
        $this->registerFrameStyle();
        return $this;
    }

    /**
     * Set vertical position properties (e.g. style:vertical-pos and style:vertical-rel).
     */
    public function setVerticalPosition(string $pos, string $rel = 'page'): self
    {
        $this->frameOptions['style:vertical-pos'] = $pos;
        $this->frameOptions['style:vertical-rel'] = $rel;
        $this->registerFrameStyle();
        return $this;
    }

    /**
     * Set text flow mode inside the frame (true = flow-with-text).
     */
    public function flowWithText(bool $enable = true): self
    {
        $this->frameOptions['style:flow-with-text'] = $enable ? 'true' : 'false';
        $this->registerFrameStyle();
        return $this;
    }


    public function registerStyles(): void
    {
    }
}
