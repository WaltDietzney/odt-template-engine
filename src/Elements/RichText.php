<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Utils\StyleWriter;
use OdtTemplateEngine\Contracts\HasStyles;

/**
 * RichText is a flexible container for formatted text, paragraphs, lists, and more.
 */
class RichText extends OdtElement implements HasStyles
{
    /**
     * @var Paragraph[] Contains all paragraphs in the RichText.
     */
    protected array $paragraphs = [];

    /**
     * Adds a paragraph to the RichText with optional content and paragraph style.
     *
     * @param string|Paragraph $text The text content or Paragraph object to add.
     * @param string|null $styleName Optional paragraph style name to apply.
     * @param array $styleOptions Optional style attributes to define for the paragraph style.
     * @return $this The current instance for method chaining.
     */
    public function addParagraph(string|Paragraph $text = '', ?string $styleName = null, array $styleOptions = []): self
    {
        if ($text instanceof Paragraph) {
            $this->paragraphs[] = $text;
        } else {
            $p = new Paragraph();
            if ($styleName) {
                $p->setParagraphStyle($styleName);
                $styleOptions ?? $p->setParagraphStyleOptions($styleOptions);
            }
            $p->addText($text, $styleOptions);
            $this->paragraphs[] = $p;
        }

        return $this;
    }

    public function addImage(ImageElement $image): mixed {
       
        $p = new Paragraph();
        $p->addElement($image);
        $this->addParagraph($p);
        return $this;
    }

    /**
     * Adds one or more empty paragraphs (line breaks between blocks).
     */
    public function addParagraphBreak(int $count = 1): self
    {
        for ($i = 0; $i < $count; $i++) {
            $this->addParagraph(new Paragraph());
        }
        return $this;
    }

    /**
     * Adds multiple lines as paragraphs.
     * Optionally applies styling and bold to the first line.
     *
     * @param string[] $lines
     * @param array|null $style
     * @param bool $firstBold
     */
    public function addMultiParagraph(array $lines, ?array $style = null, bool $firstBold = false): self
    {
        foreach ($lines as $index => $line) {
            $lineStyle = $style ?? [];

            if ($firstBold && $index === 0) {
                $lineStyle = array_merge($lineStyle, ['bold' => true]);
            }

            $paragraph = (new Paragraph())->addText($line, $lineStyle);
            $this->addParagraph($paragraph);
        }

        return $this;
    }

    /**
     * Adds formatted text to the last paragraph (or creates a new one if none exists).
     *
     * @param string $text The text content to add.
     * @param array $style Optional inline text styles (e.g., bold, italic, color).
     * @return $this The current instance for method chaining.
     */
    public function addText(string $text, array $style = []): self
    {
        if (empty($this->paragraphs)) {
            $this->addParagraph(); // creates empty paragraph
        }
        end($this->paragraphs)->addText($text, $style);
        return $this;
    }

    /**
     * Adds a line break to the last paragraph.
     *
     * @return $this The current instance for method chaining.
     */
    public function addLineBreak(): self
    {
        if (empty($this->paragraphs)) {
            $this->addParagraph();
        }

        end($this->paragraphs)->addLineBreak();
        return $this;
    }

    /**
     * Adds a tab character to the last paragraph.
     *
     * @return $this The current instance for method chaining.
     */
    public function addTab(): self
    {
        if (empty($this->paragraphs)) {
            $this->addParagraph();
        }

        end($this->paragraphs)->addTab();
        return $this;
    }

    /**
     * Adds a bulleted list (each item is a paragraph with a bullet).
     *
     * @param array $items List of items for the bulleted list.
     * @param array $style Optional styles to apply to the list.
     * @return $this The current instance for method chaining.
     */
    public function addBulletList(array $items, array $style = []): self
    {
        foreach ($items as $text) {
            $p = new Paragraph();
            $p->addText($text, $style);
            $p->setBulleted(); // important!
            $this->paragraphs[] = $p;
        }
        return $this;
    }

    /**
     * Returns all required styles of the contained paragraphs.
     *
     * @return array Array of style definitions required for this RichText.
     */
    public function getRequiredStyles(): array
    {
        $styles = [];

        foreach ($this->paragraphs as $paragraph) {
            $styles = array_merge($styles, $paragraph->getRequiredStyles());
        }

        return $styles;
    }

    /**
     * Converts the RichText object to a DOM node (fragment).
     *
     * @param DOMDocument $dom The target DOM document.
     * @return DOMNode The generated DOM node fragment.
     */
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $fragment = $dom->createDocumentFragment();

        foreach ($this->paragraphs as $p) {
            $fragment->appendChild($p->toDomNode($dom));
        }

        foreach ($this->getEmbeddedElements() as $embedded) {
            $fragment->appendChild($embedded->toDomNode($dom));
        }

        return $fragment;
    }

    /**
     * Adds a numbered list (e.g., 1., 2., 3.).
     *
     * @param array $items List of items for the numbered list.
     * @param array $style Optional styles to apply to the list.
     * @return $this The current instance for method chaining.
     */
    public function addNumberedList(array $items, array $style = []): self
    {
        foreach ($items as $text) {
            $p = new Paragraph();
            $p->addText($text, $style);
            $p->setNumbered(); // important!
            $this->paragraphs[] = $p;
        }
        return $this;
    }

    /**
     * Gathers all paragraph style names and their options required by this RichText instance.
     *
     * @return array Associative array where keys are style names and values are style options.
     */
    public function getRequiredParagraphStyles(): array
    {
        $all = [];
        foreach ($this->paragraphs as $p) {
            foreach ($p->getRequiredParagraphStyles() as $name => $opts) {
                $all[$name] = $opts;
            }
        }
        return $all;
    }

    /**
     * Registers the styles for the contained paragraphs.
     */
    public function registerStyles(): void
    {
        foreach ($this->paragraphs as $paragraph) {
            if ($paragraph instanceof HasStyles) {
                $paragraph->registerStyles();
            }
        }
    }





    /**
     * Returns the style definitions for the contained paragraphs.
     *
     * @return array An array of style definitions.
     */
    public function getStyleDefinitions(): array
    {
        $styles = [];

        foreach ($this->paragraphs as $paragraph) {
            if ($paragraph instanceof HasStyles) {
                $styles = array_merge_recursive($styles, $paragraph->getStyleDefinitions());
            }
        }

        return $styles;
    }

    /**
     * Returns the image assets embedded within the paragraphs.
     *
     * @return array An array of image assets.
     */
    public function getImageAssets(): array
    {
        $assets = [];

        foreach ($this->paragraphs as $paragraph) {
            if (method_exists($paragraph, 'getEmbeddedElements')) {
                foreach ($paragraph->getEmbeddedElements() as $element) {
                    if ($element instanceof ImageElement) {
                        $assets[] = [
                            'id' => basename($element->getImagePath()),
                            'path' => $element->getImagePath(),
                            'options' => $element->getImageOptions()
                        ];
                    }
                }
            }
        }

        return $assets;
    }
}
