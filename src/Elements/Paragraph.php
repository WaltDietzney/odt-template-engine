<?php

namespace OdtTemplateEngine\Elements;

use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Contracts\HasStyles;
use DOMDocument;
use DOMNode;

/**
 * Represents a paragraph in an ODT (OpenDocument Text) document.
 *
 * Supports inline text styling, line breaks, tabs, hyperlinks, paragraph styles,
 * list formatting (bulleted/numbered), and embedded elements (like images).
 */
class Paragraph extends OdtElement implements HasStyles
{
    /**
     * Text parts and inline content of the paragraph.
     * Each part may be a text, line break, tab, hyperlink, etc.
     *
     * @var array[] List of content parts with structure:
     *              ['type' => 'text'|'line-break'|'tab'|'hyperlink', 'content' => string, 'style' => array, ...]
     */
    protected array $parts = [];

    /**
     * Map of text styles used in this paragraph.
     * Each style is identified by a unique style name.
     *
     * @var array<string, array>
     */
    protected array $textStyleMap = [];

    /**
     * The name of the paragraph style.
     * This is referenced in the output XML (e.g., "Standard", "Heading1").
     *
     * @var string|null
     */
    protected ?string $paragraphStyle = null;

    /**
     * Options (rules) for the paragraph style (e.g., alignment, margins).
     *
     * @var array
     */
    protected array $paragraphStyleOptions = [];

    /**
     * Optional list style for this paragraph (for bulleted or numbered lists).
     *
     * @var string|null
     */
    protected ?string $listStyle = null;

    /**
     * Embedded ODT elements such as images or frames.
     *
     * @var OdtElement[]
     */
    protected array $embeddedElements = [];

    /**
     * Constructor.
     *
     * @param string|null $paragraphStyle Optional paragraph style name.
     * @param array $paragraphStyleOptions Optional paragraph style options.
     */
    public function __construct(?string $paragraphStyle = null, array $paragraphStyleOptions = [])
    {
        if ($paragraphStyle !== null) {
            $this->setParagraphStyle($paragraphStyle);
            $this->setParagraphStyleOptions($paragraphStyleOptions);
        }
    }

    /**
     * Adds styled text to the paragraph.
     *
     * @param string $text The text content.
     * @param array $style Optional inline style (e.g., ['bold' => true]).
     * @return $this
     */
    public function addText(string $text, array $style = []): self
    {
        $styleName = null;

        if (!empty($style)) {
            $styleName = StyleMapper::generateStyleName($style);
            $this->textStyleMap[$styleName] = $style;
        }

        $this->parts[] = [
            'type' => 'text',
            'content' => $text,
            'style' => $style,
            'styleName' => $styleName
        ];

        return $this;
    }

    /**
     * Adds a line break.
     *
     * @return $this
     */
    public function addLineBreak(int $count = 1): self
    {
        for ($i = 0; $i < $count; $i++) {
            $this->parts[] = ['type' => 'line-break'];
        }
        return $this;
    }


    /**
     * Adds a tab character.
     *
     * @return $this
     */
    public function addTab(): self
    {
        $this->parts[] = ['type' => 'tab'];
        return $this;
    }

    /**
     * Adds a custom Tab at given position
     * 
     * @param float $position
     * @param string $alignement
     * @param string $text
     * 
     * @return $this
     */
    public function addTabStop(float $position, string $alignment = 'left', ?string $text = null, array $style = []): self
    {
        $this->parts[] = [
            'type' => 'tab-stop',
            'position' => $position,
            'alignment' => $alignment,
            'text' => $text,
            'style' => $style
        ];

        if (!empty($style)) {
            $styleName = StyleMapper::generateStyleName($style);
            $this->textStyleMap[$styleName] = $style;
        }

        return $this;
    }

    /**
     * Adds multiple lines using tab stops. Each line is an array of strings.
     * Optionally styles the first row (e.g., as header).
     *
     * @param array $lines         Array of rows, each row is an array of column values.
     * @param array $tabDefs       Array of tab stop definitions: [['position' => float, 'alignment' => string], ...]
     * @param array $headerStyle   Optional style array for the first row (bold, etc.)
     */
    public function addTabularLines(array $lines, array $tabDefs, array $headerStyle = []): self
    {   $noFirstTab = false;
        // Tab definitions (applied once)
        foreach ($tabDefs as $tab) {
            $this->addTabStopDefinition($tab['position'], $tab['alignment'] ?? 'left');
            
        }

        // Loop through lines
        foreach ($lines as $rowIndex => $columns) {
            $this->addTab();
            // Add each value + tab
            foreach ($columns as $i => $value) {
                if ($i > 0) {
                    $this->addTab();
                }
                
                    $style = ($rowIndex === 0 && !empty($headerStyle)) ? $headerStyle : [];
                    $this->addText((string) $value, $style);
                
            }

            // New paragraph after each line (except last)
            if ($rowIndex < count($lines) - 1) {
                $this->addLineBreak();
            }
        }

        return $this;
    }



    /**
     * Adds a hyperlink with optional inline styling.
     *
     * @param string $text Link label.
     * @param string $href URL of the hyperlink.
     * @param array $style Optional link text styling.
     * @return $this
     */
    public function aderlink(string $text, string $href, array $style = []): self
    {
        $this->parts[] = [
            'type' => 'hyperlink',
            'content' => $text,
            'href' => $href,
            'style' => $style
        ];

        if (!empty($style)) {
            $styleName = StyleMapper::generateStyleName($style);
            $this->textStyleMap[$styleName] = $style;
        }

        return $this;
    }

    /**
     * Adds a child ODT element (e.g. an image).
     *
     * @param OdtElement $element The embedded element.
     * @return $this
     */
    public function addElement(OdtElement $element): self
    {
        $this->embeddedElements[] = $element;
        return $this;
    }

    /**
     * Gets all embedded elements.
     *
     * @return OdtElement[]
     */
    public function getEmbeddedElements(): array
    {
        return $this->embeddedElements;
    }

    /**
     * Sets the paragraph style name.
     *
     * @param string $styleName
     * @return $this
     */
    public function setParagraphStyle(string $styleName): self
    {
        $this->paragraphStyle = $styleName;
        return $this;
    }

    /**
     * Sets the paragraph style options.
     *
     * @param array $options
     * @return $this
     */
    public function setParagraphStyleOptions(array $options): self
    {
        $this->paragraphStyleOptions = $options;
        return $this;
    }

    /**
     * Sets the paragraph tabs definitions.
     *
     * @param float $postion
     * @param string $alignment 
     * @return $this
     */
    public function addTabStopDefinition(float $position, string $alignment = 'left'): self
    {
        $this->paragraphStyleOptions['tab-stops'][] = [
            'position' => $position,
            'alignment' => $alignment
        ];

        // Auto-Style setzen, wenn noch keiner vergeben wurde
        if ($this->paragraphStyle === null) {
            $this->paragraphStyle = StyleMapper::generateStyleName($this->paragraphStyleOptions);
        }

        return $this;
    }

    /**
     * A usefull method to set keys addKeyValueLine
     * @param string $key
     * @param string $value
     * @param float $tabPosition
     * @return Paragraph
     */
    public function addKeyValueLine(string $key, string $value, float $tabPosition = 10.0, array $style = null): self
    {
        $this->addTabStopDefinition($tabPosition, 'right');
        return $this
            ->addText($key, $style)
            ->addTab()
            ->addText($value, $style);
    }

    /**
     * Summary of addTabsWithTexts
     * @param array $tabs = ['position' =>$position, 'alignment'=>$alignment, 'text'=>$text, 'style' => $textStyle ]
     * @return Paragraph
     */
    public function addTabsWithTexts(array $tabs): self
    {
        foreach ($tabs as $entry) {
            $this->addTabStopDefinition($entry['position'], $entry['alignment'] ?? 'left');
            $this->addTab();
            $this->addText($entry['text'] ?? '', $entry['style'] ?? []);
        }
        return $this;
    }

    public function addHyperlink(string $text, string $href, array $style = []): self
    {
        $this->parts[] = [
            'type' => 'hyperlink',
            'content' => $text,
            'href' => $href,
            'style' => $style
        ];
        return $this;
    }


    /**
     * Converts the paragraph to a bulleted list item.
     *
     * @return $this
     */
    public function setBulleted(): self
    {
        $this->setParagraphStyle('Bullet_20_Symbol');
        $this->listStyle = 'Bullet_20_Symbol';
        return $this;
    }

    /**
     * Converts the paragraph to a numbered list item.
     *
     * @return $this
     */
    public function setNumbered(): self
    {
        $this->setParagraphStyle('Numbering_20_Symbol');
        $this->listStyle = 'Numbering_20_Symbol';
        return $this;
    }

    /**
     * Checks if this paragraph is part of a list.
     *
     * @return bool
     */
    public function isList(): bool
    {
        return !empty($this->listStyle);
    }

    // ------------- Style Registration -------------

    /**
     * Registers all text and paragraph styles used in this paragraph.
     */
    public function registerStyles(): void
    {
        foreach ($this->textStyleMap as $style) {
            StyleMapper::registerTextStyle($style);
        }

        if ($this->paragraphStyle && !empty($this->paragraphStyleOptions)) {
            StyleMapper::registerParagraphStyle($this->paragraphStyle, $this->paragraphStyleOptions);
        }
    }

    /**
     * Returns all inline text styles required by this paragraph.
     *
     * @return array<string, array>
     */
    public function getRequiredStyles(): array
    {
        return $this->textStyleMap;
    }

    /**
     * Returns all paragraph style definitions.
     *
     * @return array<string, array>
     */
    public function getRequiredParagraphStyles(): array
    {
        if ($this->paragraphStyle && !empty($this->paragraphStyleOptions)) {
            return [$this->paragraphStyle => $this->paragraphStyleOptions];
        }
        return [];
    }

    /**
     * Returns all styles (inline and paragraph) used in this paragraph.
     *
     * @return array<string, array>
     */
    public function getStyleDefinitions(): array
    {
        return array_merge(
            $this->getRequiredStyles(),
            $this->getParagraphStyleDefinitions()
        );
    }

    /**
     * Returns mapped paragraph style definitions.
     *
     * @return array<string, array>
     */
    public function getParagraphStyleDefinitions(): array
    {
        if ($this->paragraphStyle && !empty($this->paragraphStyleOptions)) {
            return [
                $this->paragraphStyle => StyleMapper::mapParagraphStyle($this->paragraphStyleOptions)
            ];
        }
        return [];
    }

    // ------------- Rendering -------------

    /**
     * Converts the paragraph into a DOMNode for inclusion in the ODT XML structure.
     *
     * @param DOMDocument $dom
     * @return DOMNode
     */
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $style = $this->paragraphStyle ?? 'Standard';
        $p = $dom->createElement('text:p');
        $p->setAttribute('text:style-name', $style);

        foreach ($this->parts as $part) {
            switch ($part['type']) {
                case 'text':
                    $node = $dom->createTextNode($part['content']);
                    if (!empty($part['style'])) {
                        $styleName = StyleMapper::generateStyleName($part['style']);
                        $span = $dom->createElement('text:span');
                        $span->setAttribute('text:style-name', $styleName);
                        $span->appendChild($node);
                        $p->appendChild($span);
                    } else {
                        $p->appendChild($node);
                    }
                    break;

                case 'hyperlink':
                    $a = $dom->createElement('text:a');
                    $a->setAttribute('xlink:href', $part['href']);
                    $a->setAttribute('xlink:type', 'simple');
                    $a->setAttribute('xlink:show', 'new');

                    $node = $dom->createTextNode($part['content']);
                    if (!empty($part['style'])) {
                        $styleName = StyleMapper::generateStyleName($part['style']);
                        $span = $dom->createElement('text:span');
                        $span->setAttribute('text:style-name', $styleName);
                        $span->appendChild($node);
                        $a->appendChild($span);
                    } else {
                        $a->appendChild($node);
                    }

                    $p->appendChild($a);
                    break;

                case 'line-break':
                    $p->appendChild($dom->createElement('text:line-break'));
                    break;

                case 'tab':
                    $p->appendChild($dom->createElement('text:tab'));
                    break;
                case 'tab-stop':
                    $p->appendChild($dom->createElement('text:tab'));

                    if (!empty($part['text'])) {
                        $node = $dom->createTextNode($part['text']);
                        if (!empty($part['style'])) {
                            $styleName = StyleMapper::generateStyleName($part['style']);
                            $span = $dom->createElement('text:span');
                            $span->setAttribute('text:style-name', $styleName);
                            $span->appendChild($node);
                            $p->appendChild($span);
                        } else {
                            $p->appendChild($node);
                        }
                    }
                    break;
                case 'paragraph-break':
                    $p->appendChild($dom->createElement('text:p'));
                    break;

            }
        }

        foreach ($this->embeddedElements as $element) {
            $p->appendChild($element->toDomNode($dom));
        }

        if ($this->isList()) {
            $list = $dom->createElement('text:list');
            $list->setAttribute('text:style-name', $this->listStyle);
            $item = $dom->createElement('text:list-item');
            $item->appendChild($p);
            $list->appendChild($item);
            return $list;
        }

        return $p;
    }

    
}
