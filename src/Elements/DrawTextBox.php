<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMElement;
use DOMNode;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StructuredInsertionMode;
use OdtTemplateEngine\Utils\StyleMapper;

/**
 * Represents a styled text box (draw:frame with draw:text-box) in an ODT document.
 */
class DrawTextBox extends OdtElement
{
    protected string $name;
    protected array $frameOptions = [];
    protected array $paragraphs = [];
    protected string $frameStyleName = '';
    private ?DrawingLayout $drawingLayout = null;
    private DrawingLayoutProjector $drawingLayoutProjector;
    /** @var array<string, string|bool> Explicit native bounds and drawing Auto-Grow. */
    private array $textBoxSizing = [];
    /** @var array<string, string> Explicit native padding and margins. */
    private array $frameSpacing = [];


    public function __construct(string $name, array $options = [])
    {
        $this->name = $name;
        $this->frameOptions = $options;
        $this->drawingLayoutProjector = new DrawingLayoutProjector();
        $this->registerFrameStyle();
    }

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

    /** @return iterable<int, StyleRequirement> */
    public function getOwnStyleRequirements(): iterable
    {
        $properties = $this->semanticGraphicProperties();
        if ($properties === []) {
            return [];
        }

        return [new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'graphic',
            StyleRequirement::PART_STYLES,
            StyleMapper::generateStyleName($properties),
            'Frame',
            ['style:graphic-properties' => $properties]
        )];
    }

    protected function registerFrameStyle(): void
    {
        $styleDef = $this->projectedFrameStyleProperties();
        $this->frameStyleName = StyleMapper::generateStyleName($styleDef);
    }

    /** @return array<string, array<string, mixed>> */
    public function getFrameStyleRequirements(): array
    {
        $this->registerFrameStyle();
        return [$this->frameStyleName => $this->projectedFrameStyleProperties()];
    }

    /** @return array<string, array<string, mixed>> */
    public function getOwnFrameStyleRequirements(): array
    {
        return $this->getFrameStyleRequirements();
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $this->registerFrameStyle();

        $anchor = $this->effectiveAnchor();
        $styleName = $this->resolvedRenderedStyleName();

        $frame = $dom->createElement('draw:frame');
        $frame->setAttribute('draw:name', $this->name);
        $frame->setAttribute('text:anchor-type', $anchor);
        $frame->setAttribute('draw:z-index', '0');
        $frame->setAttribute('draw:style-name', $styleName);

        if ($this->drawingLayout !== null) {
            foreach ($this->effectiveObjectAttributes() as $attribute => $value) {
                $frame->setAttribute($attribute, $value);
            }
        } else {
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
        }

        $textBox = $dom->createElement('draw:text-box');
        foreach ($this->textBoxSizing as $key => $value) {
            if (!str_starts_with($key, 'auto-grow-')) {
                $textBox->setAttributeNS(
                    'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                    'fo:' . $key,
                    $value
                );
            }
        }
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

        if ($anchor === 'as-char') {
            return $frame;
        }

        $p = $dom->createElement('text:p');
        $p->appendChild($frame);
        return $p;
    }

    public function structuredInsertionMode(): StructuredInsertionMode
    {
        return $this->effectiveAnchor() === 'as-char'
            ? StructuredInsertionMode::INLINE_TEXT_FLOW
            : StructuredInsertionMode::BLOCK;
    }

    public function toStyleDomNode(DOMDocument $dom): ?DOMElement
    {
        $styleNode = $dom->createElement('style:style');
        $styleNode->setAttribute('style:name', $this->frameStyleName);
        $styleNode->setAttribute('style:family', 'graphic');
        $styleNode->setAttribute('style:parent-style-name', 'Frame');

        $props = $dom->createElement('style:graphic-properties');
        foreach ($this->projectedFrameStyleProperties() as $key => $val) {
            $props->setAttribute($key, $val);
        }
        $styleNode->appendChild($props);
        return $styleNode;
    }

    /**
     * Replaces the friendly semantic frame-layout state.
     *
     * @param array<string, mixed> $layout
     */
    public function setFrameLayout(array $layout): self
    {
        $this->drawingLayout = $layout === [] ? null : DrawingLayout::fromArray($layout);

        return $this;
    }

    /**
     * Replace explicit native minima and optional drawing Auto-Grow flags.
     *
     * Object width/height remain owned by setFrameLayout(). Empty input clears this overlay.
     * @param array<string, mixed> $sizing
     * @return $this
     */
    public function setTextBoxSizing(array $sizing): self
    {
        $normalized = [];
        foreach ($sizing as $key => $value) {
            if (in_array($key, ['auto-grow-width', 'auto-grow-height'], true)) {
                if (!is_bool($value)) {
                    throw new \InvalidArgumentException('Auto-Grow flags must be booleans.');
                }
                $normalized[$key] = $value;
            } elseif (in_array($key, ['min-width', 'min-height'], true)) {
                $this->absoluteLengthInPoints($value, false);
                $normalized[$key] = $value;
            } else {
                throw new \InvalidArgumentException('Unsupported text-box sizing key.');
            }
        }
        foreach (['width', 'height'] as $axis) {
            if (isset($normalized['min-' . $axis])
                && array_key_exists('auto-grow-' . $axis, $normalized)
                && $normalized['auto-grow-' . $axis] === false) {
                throw new \InvalidArgumentException('A native minimum selects variable size and cannot disable growth on that axis.');
            }
        }
        $this->textBoxSizing = $normalized;
        $this->registerFrameStyle();
        return $this;
    }

    /**
     * Replace explicit nonnegative padding/margins without changing legacy constructor options.
     *
     * @param array<string, mixed> $spacing e.g. padding-left or margin-bottom.
     * @return $this
     */
    public function setFrameSpacing(array $spacing): self
    {
        $normalized = [];
        foreach ($spacing as $key => $value) {
            if (!is_string($key) || !preg_match('/^(padding|margin)(-(top|right|bottom|left))?$/D', $key)) {
                throw new \InvalidArgumentException('Unsupported frame spacing key.');
            }
            $this->absoluteLengthInPoints($value, true);
            $normalized['fo:' . $key] = $value;
        }
        foreach (['padding', 'margin'] as $kind) {
            $shorthand = 'fo:' . $kind;
            if (!array_key_exists($shorthand, $normalized)) {
                continue;
            }
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                $key = $shorthand . '-' . $side;
                if (!array_key_exists($key, $normalized)) {
                    $normalized[$key] = $normalized[$shorthand];
                }
            }
            unset($normalized[$shorthand]);
        }
        $this->frameSpacing = $normalized;
        $this->registerFrameStyle();
        return $this;
    }

    public function setFrameAnchor(string $anchor): self
    {
        $this->drawingLayout = ($this->drawingLayout ?? DrawingLayout::empty())
            ->withAnchor($anchor);

        return $this;
    }

    public function setFrameHorizontalAlignment(string $alignment, ?string $relativeTo = null): self
    {
        $this->drawingLayout = ($this->drawingLayout ?? DrawingLayout::empty())
            ->withHorizontalAlignment($alignment, $relativeTo);

        return $this;
    }

    public function setFrameVerticalAlignment(string $alignment, ?string $relativeTo = null): self
    {
        $this->drawingLayout = ($this->drawingLayout ?? DrawingLayout::empty())
            ->withVerticalAlignment($alignment, $relativeTo);

        return $this;
    }

    public function setFrameHorizontalOffset(string $offset, ?string $relativeTo = null): self
    {
        $this->drawingLayout = ($this->drawingLayout ?? DrawingLayout::empty())
            ->withHorizontalOffset($offset, $relativeTo);

        return $this;
    }

    public function setFrameVerticalOffset(string $offset, ?string $relativeTo = null): self
    {
        $this->drawingLayout = ($this->drawingLayout ?? DrawingLayout::empty())
            ->withVerticalOffset($offset, $relativeTo);

        return $this;
    }

    public function setFrameWrap(string $wrap): self
    {
        $this->drawingLayout = ($this->drawingLayout ?? DrawingLayout::empty())
            ->withWrap($wrap);

        return $this;
    }

    public function setBackground(string|array $color): self
    {
        if (is_array($color)) {
            \OdtTemplateEngine\Document\FillProjection::validate($color);
        }
        foreach (['background', 'background-color', 'fo:background-color', 'fill', 'draw:fill',
            'fill-color', 'draw:fill-color', 'draw:fill-gradient-name', 'draw:fill-image-name',
            'draw:fill-image-width', 'draw:fill-image-height', 'style:repeat'] as $key) {
            unset($this->frameOptions[$key]);
        }
        if (is_string($color)) {
            unset($this->frameOptions['background']);
            $this->frameOptions['background-color'] = $color;
        } else {
            unset($this->frameOptions['background-color'], $this->frameOptions['fo:background-color']);
            $this->frameOptions['background'] = $color;
        }
        $this->registerFrameStyle();
        return $this;
    }

    /** @return array<string, mixed>|null */
    public function semanticBackgroundOptions(): ?array
    {
        return isset($this->frameOptions['background']) && is_array($this->frameOptions['background'])
            ? $this->frameOptions['background'] : null;
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

    public function setHorizontalPosition(string $pos, string $rel = 'page'): self
    {
        return $this->setHorizontalPos($pos, $rel);
    }

    public function setVerticalPosition(string $pos, string $rel = 'page'): self
    {
        return $this->setVerticalPos($pos, $rel);
    }

    public function flowWithText(bool $enable = true): self
    {
        $this->frameOptions['style:flow-with-text'] = $enable ? 'true' : 'false';
        $this->registerFrameStyle();
        return $this;
    }

    /** @return array<string, mixed> */
    private function projectedFrameStyleProperties(): array
    {
        $properties = StyleMapper::mapFrameStyleOptions($this->frameOptions);
        foreach (['auto-grow-width', 'auto-grow-height'] as $key) {
            if (array_key_exists($key, $this->textBoxSizing)) {
                $properties['draw:' . $key] = $this->textBoxSizing[$key] ? 'true' : 'false';
            }
        }
        return array_merge($properties, $this->frameSpacing);
    }

    private function absoluteLengthInPoints(mixed $value, bool $allowZero): float
    {
        if (!is_string($value)
            || !preg_match('/^(\d+(?:\.\d+)?|\.\d+)(cm|mm|in|pt|pc)$/D', $value, $match)) {
            throw new \InvalidArgumentException('Frame bounds and spacing require absolute ODF lengths.');
        }
        $scale = ['cm' => 72 / 2.54, 'mm' => 72 / 25.4, 'in' => 72, 'pt' => 1, 'pc' => 12];
        $points = (float) $match[1] * $scale[$match[2]];
        if (!is_finite($points) || $points < 0 || (!$allowZero && $points <= 0)) {
            throw new \InvalidArgumentException('Frame bounds must be positive; spacing may also be zero.');
        }
        return $points;
    }

    /** @return array<string, mixed> */
    private function semanticGraphicProperties(): array
    {
        $semantic = [];
        foreach ($this->projectedFrameStyleProperties() as $key => $value) {
            if ($this->isSemanticGraphicProperty((string) $key)) {
                $semantic[(string) $key] = $value;
            }
        }

        // Only explicit new sizing/spacing state opts into these semantic properties.
        foreach (['auto-grow-width', 'auto-grow-height'] as $key) {
            if (array_key_exists($key, $this->textBoxSizing)) {
                $semantic['draw:' . $key] = $this->textBoxSizing[$key] ? 'true' : 'false';
            }
        }
        $semantic = array_merge($semantic, $this->frameSpacing);

        if ($this->drawingLayout !== null) {
            $semantic = array_merge(
                $semantic,
                $this->drawingLayoutProjector->graphicLayoutProperties($this->drawingLayout)
            );
        }

        ksort($semantic);

        return $semantic;
    }

    private function effectiveAnchor(): string
    {
        return $this->drawingLayout?->anchor()
            ?? ($this->frameOptions['anchor'] ?? 'paragraph');
    }

    /** @return array<string, string> */
    private function effectiveObjectAttributes(): array
    {
        if ($this->drawingLayout === null) {
            return [];
        }

        $attributes = [];

        if (!empty($this->frameOptions['width'])) {
            $attributes['svg:width'] = (string) $this->frameOptions['width'];
        }
        if (!empty($this->frameOptions['height'])) {
            $attributes['svg:height'] = (string) $this->frameOptions['height'];
        }

        return array_merge(
            $attributes,
            $this->drawingLayoutProjector->objectAttributes($this->drawingLayout)
        );
    }

    private function resolvedRenderedStyleName(): string
    {
        $semantic = $this->semanticGraphicProperties();
        if ($semantic === [] || $this->requiresLegacyGraphicCarrier()) {
            return $this->frameStyleName;
        }

        return StyleMapper::generateStyleName($semantic);
    }

    private function requiresLegacyGraphicCarrier(): bool
    {
        $semantic = $this->semanticGraphicProperties();
        foreach ($this->projectedFrameStyleProperties() as $key => $_value) {
            $key = (string) $key;
            if (array_key_exists($key, $semantic)) {
                continue;
            }

            if (in_array($key, [
                'width',
                'height',
                'anchor',
                'style:horizontal-pos',
                'style:horizontal-rel',
                'style:vertical-pos',
                'style:vertical-rel',
            ], true)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function isSemanticGraphicProperty(string $key): bool
    {
        if (in_array($key, [
            'fo:background-color',
            'draw:fill',
            'draw:fill-color',
            'draw:stroke',
            'draw:fill-image-name',
            'draw:fill-gradient-name',
            'draw:fill-image-width',
            'draw:fill-image-height',
            'style:repeat',
            'style:wrap',
            'style:flow-with-text',
            'draw:wrap-influence-on-position',
            'loext:allow-overlap',
        ], true)) {
            return true;
        }

        return str_starts_with($key, 'fo:border')
            || str_starts_with($key, 'fo:padding')
            || str_starts_with($key, 'draw:stroke-')
            || str_starts_with($key, 'svg:stroke-');
    }
}
