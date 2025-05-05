<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Contracts\HasStyles;

/**
 * Class RichText
 *
 * RichText is a flexible container for formatted text blocks, including paragraphs, images, tables, lists, and more.
 *
 * @package OdtTemplateEngine\Elements
 */
class RichText extends OdtElement implements HasStyles
{
    /**
     * @var array<int, OdtElement> List of contained elements (Paragraph, ImageElement, RichTable, etc.)
     */
    protected array $elements = [];

    /**
     * Add a paragraph or simple text.
     *
     * @param string|Paragraph $text Text content or a Paragraph object.
     * @param string|null $styleName Optional paragraph style name.
     * @param array $styleOptions Optional inline style options.
     * @return $this
     */
    public function addParagraph(string|Paragraph $text = '', ?string $styleName = null, array $styleOptions = []): self
    {
        if ($text instanceof Paragraph) {
            $this->elements[] = $text;
        } else {
            $p = new Paragraph();
            if ($styleName) {
                $p->setParagraphStyle($styleName);
            }
            if (!empty($styleOptions)) {
                $p->setParagraphStyleOptions($styleOptions);
            }
            $p->addText($text, $styleOptions);
            $this->elements[] = $p;
        }
        return $this;
    }

    /**
     * Add a RichTable to the content.
     *
     * @param RichTable $table
     * @return $this
     */
    public function addTable(RichTable $table): self
    {
        $this->elements[] = $table;
        return $this;
    }

    /**
     * Add an image into a new paragraph.
     *
     * @param ImageElement $image
     * @return $this
     */
    public function addImage(ImageElement $image): self
    {
        $p = new Paragraph();
        $p->addElement($image);
        $this->addParagraph($p);
        return $this;
    }

    /**
     * Insert one or more empty paragraph breaks.
     *
     * @param int $count Number of breaks to add.
     * @return $this
     */
    public function addParagraphBreak(int $count = 1): self
    {
        for ($i = 0; $i < $count; $i++) {
            $this->addParagraph(new Paragraph());
        }
        return $this;
    }

    /**
     * Add multiple paragraphs from an array of text lines.
     *
     * @param array<int, string> $lines
     * @param array|null $style
     * @param bool $firstBold Whether the first line should be bold.
     * @return $this
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
     * Add text to the last paragraph or create one.
     *
     * @param string $text
     * @param array $style
     * @return $this
     */
    public function addText(string $text, array $style = []): self
    {
        $this->getLastParagraphOrCreate()->addText($text, $style);
        return $this;
    }

    /**
     * Add a line break to the last paragraph.
     *
     * @return $this
     */
    public function addLineBreak(): self
    {
        $this->getLastParagraphOrCreate()->addLineBreak();
        return $this;
    }

    /**
     * Add a tab character to the last paragraph.
     *
     * @return $this
     */
    public function addTab(): self
    {
        $this->getLastParagraphOrCreate()->addTab();
        return $this;
    }

    /**
     * Add a bulleted list.
     *
     * @param array<int, string> $items
     * @param array $style
     * @return $this
     */
    public function addBulletList(array $items, array $style = []): self
    {
        foreach ($items as $item) {
            $p = (new Paragraph())->addText($item, $style);
            $p->setBulleted();
            $this->elements[] = $p;
        }
        return $this;
    }

    /**
     * Add a numbered list.
     *
     * @param array<int, string> $items
     * @param array $style
     * @return $this
     */
    public function addNumberedList(array $items, array $style = []): self
    {
        foreach ($items as $item) {
            $p = (new Paragraph())->addText($item, $style);
            $p->setNumbered();
            $this->elements[] = $p;
        }
        return $this;
    }

    /**
     * Convert the RichText into a DOM node (fragment).
     *
     * @param DOMDocument $dom
     * @return DOMNode
     */
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $fragment = $dom->createDocumentFragment();
        foreach ($this->elements as $element) {
            $fragment->appendChild($element->toDomNode($dom));
        }
        return $fragment;
    }

    /**
     * Get all required text styles (e.g., font styles, text properties).
     *
     * @return array
     */
    public function getRequiredStyles(): array
    {
        $styles = [];
        foreach ($this->elements as $element) {
            if ($element instanceof HasStyles) {
                $styles = array_merge($styles, $element->getRequiredStyles());
            }
        }
        return $styles;
    }

    /**
     * Get all required paragraph styles.
     *
     * @return array<string, array> [styleName => styleOptions]
     */
    public function getRequiredParagraphStyles(): array
    {
        $all = [];
        foreach ($this->elements as $element) {
            if ($element instanceof Paragraph) {
                $all += $element->getRequiredParagraphStyles();
            }
        }
        return $all;
    }

    /**
     * Register all styles for all contained elements.
     *
     * @return void
     */
    public function registerStyles(): void
    {
        foreach ($this->elements as $element) {
            if ($element instanceof HasStyles) {
                $element->registerStyles();
            }
        }
    }

    /**
     * Collect style definitions from all elements.
     *
     * @return array
     */
    public function getStyleDefinitions(): array
    {
        $styles = [];
        foreach ($this->elements as $element) {
            if ($element instanceof HasStyles) {
                $styles = array_merge_recursive($styles, $element->getStyleDefinitions());
            }
        }
        return $styles;
    }

    /**
     * Collect all embedded images.
     *
     * @return array<int, array<string, mixed>> List of image assets.
     */
    public function getImageAssets(): array
    {
        $assets = [];
        foreach ($this->elements as $element) {
            if ($element instanceof Paragraph && method_exists($element, 'getEmbeddedElements')) {
                foreach ($element->getEmbeddedElements() as $embedded) {
                    if ($embedded instanceof ImageElement) {
                        $assets[] = [
                            'id' => basename($embedded->getImagePath()),
                            'path' => $embedded->getImagePath(),
                            'options' => $embedded->getImageOptions(),
                        ];
                    }
                }
            }
        }
        return $assets;
    }

    /**
     * Get the last paragraph or create a new one.
     *
     * @return Paragraph
     */
    protected function getLastParagraphOrCreate(): Paragraph
    {
        if (empty($this->elements) || !$this->elements[array_key_last($this->elements)] instanceof Paragraph) {
            $p = new Paragraph();
            $this->elements[] = $p;
        }
        return $this->elements[array_key_last($this->elements)];
    }
}
