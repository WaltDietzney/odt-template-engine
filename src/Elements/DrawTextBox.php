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
        // Register the graphic/frame style immediately.
        $this->registerFrameStyle();
    }

    /**
     * Add a Paragraph or RichText element into this text box.
     */
    public function addElement(OdtElement $element): self
    {
        $this->paragraphs[] = $element;
        return $this;
    }

    /** @return iterable<int, OdtElement> */
    public function ownedElements(): iterable
    {
        return $this->paragraphs;
    }

    /**
     * Map frame options into a style and register it.
     */
    protected function registerFrameStyle(): void
    {
        $styleDef = StyleMapper::mapFrameStyleOptions($this->frameOptions);
        $this->frameStyleName = StyleMapper::generateStyleName($styleDef);
    }

    /** @return array<string, array<string, mixed>> */
    public function getFrameStyleRequirements(): array
    {
        $this->registerFrameStyle();

        return [$this->frameStyleName => StyleMapper::mapFrameStyleOptions($this->frameOptions)];
    }

    /** @return array<string, array<string, mixed>> */
    public function getOwnFrameStyleRequirements(): array
    {
        return $this->getFrameStyleRequirements();
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
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $this->registerFrameStyle();

        $anchor = $this->frameOptions['anchor'] ?? 'paragraph';

        $frame = $dom->createElement('draw:frame');
        $frame->setAttribute('draw:name', $this->name);
        $frame->setAttribute('text:anchor-type', $anchor);
        $frame->setAttribute('draw:z-index', '0');
        $frame->setAttribute('draw:style-name', $this->frameStyleName);

        // Größe setzen
        if (!empty($this->frameOptions['width'])) {
            $frame->setAttribute('svg:width', $this->frameOptions['width']);
        }
        if (!empty($this->frameOptions['height'])) {
            $frame->setAttribute('svg:height', $this->frameOptions['height']);
        }

        if (!empty($this->frameOptions['horizontal-pos'])) {
            $frame->setAttribute('style:horizontal-pos', $this->frameOptions['horizontal-pos']);
        }
        if (!empty($this->frameOptions['horizontal-rel'])) {
            $frame->setAttribute('style:horizontal-rel', $this->frameOptions['horizontal-rel']);
        }
        if (!empty($this->frameOptions['vertical-pos'])) {
            $frame->setAttribute('style:vertical-pos', $this->frameOptions['vertical-pos']);
        }
        if (!empty($this->frameOptions['vertical-rel'])) {
            $frame->setAttribute('style:vertical-rel', $this->frameOptions['vertical-rel']);
        }


        // Textbox-Inhalt
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

        // 💡 Einbettung: inline = direkt, alle anderen brauchen eigenen Absatz
        if ($anchor === 'as-char') {
            return $frame;
        }

        // Sonst in <text:p> einbetten
        $p = $dom->createElement('text:p');
        $p->appendChild($frame);
        return $p;
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
        return $this->setHorizontalPos($pos, $rel);
    }


    /**
     * Set vertical position properties (e.g. style:vertical-pos and style:vertical-rel).
     */
    public function setVerticalPosition(string $pos, string $rel = 'page'): self
    {
        return $this->setVerticalPos($pos, $rel);
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
        $this->registerFrameStyle();
        StyleMapper::$frameStyles[$this->frameStyleName] = StyleMapper::mapFrameStyleOptions($this->frameOptions);
    }
}
