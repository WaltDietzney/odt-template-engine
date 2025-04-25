<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMElement;
use DOMNode;
use OdtTemplateEngine\Contracts\HasStyles;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Utils\StyleMapper;

/**
 * Represents an image element that can be inserted into an ODT document.
 * Handles image positioning, sizing, wrapping, and style definitions.
 */
class ImageElement extends OdtElement implements HasStyles
{
    /**
     * Summary of imagePath
     * @var string
     */
    protected string $imagePath;

    /**
     * Summary of filename
     * @var string
     */
    protected string $filename;

    /**
     * Summary of width
     * @var 
     */
    protected ?string $width;

    /**
     * Summary of height
     * @var 
     */
    protected ?string $height;

    /**
     * Summary of anchor
     * @var string
     */
    protected string $anchor;

    /**
     * Summary of wrap
     * @var string
     */
    protected string $wrap;

    /**
     * Summary of enabled
     * @var bool
     */
    protected bool $enabled = true;

    /**
     * Summary of imageOptions
     * @var array
     */
    protected array $imageOptions = [];

    /**
     * Summary of rawOptions
     * @var array
     */
    protected array $rawOptions = [];

    /**
     * Summary of styleMap
     * @var array
     */
    protected array $styleMap = [];

    /**
     * Constructor.
     *
     * @param string $imagePath Path to the image file.
     * @param array $options Optional image settings such as width, height, alignment, wrapping, etc.
     * @throws \Exception If the image file does not exist or is not readable (when enabled).
     */
    public function __construct(string $imagePath, array $options = [])
    {
        $this->imagePath = $imagePath;
        $this->imageOptions = $options;

        $this->enabled = $options['enabled'] ?? true;

        if ($this->enabled && (!file_exists($imagePath) || !is_readable($imagePath))) {
            throw new \Exception("❌ Image not found: $imagePath");
        }

        $this->filename = basename($imagePath);

        if ($this->enabled) {
            [$imgWidth, $imgHeight] = getimagesize($imagePath);
            $this->width = $options['width'] ?? null;
            $this->height = $options['height'] ?? null;

            // Auto-scale height or width if only one is provided
            if ($this->width && !$this->height) {
                $cm = (float) rtrim($this->width, 'cm');
                $ratio = $imgHeight / $imgWidth;
                $this->height = round($cm * $ratio, 3) . 'cm';
            } elseif (!$this->width && $this->height) {
                $cm = (float) rtrim($this->height, 'cm');
                $ratio = $imgWidth / $imgHeight;
                $this->width = round($cm * $ratio, 3) . 'cm';
            } elseif (!$this->width && !$this->height) {
                $this->width = '5cm';
                $this->height = '3cm';
            }
        }

        if ($options) {
            $this->setStyle($options);
        }

        $this->anchor = $options['anchor'] ?? 'paragraph'; // or 'as-char'
        $this->wrap = $options['wrap'] ?? 'none'; // or 'left', 'right'
    }

    /**
     * Converts the image to an ODT-compatible DOM node.
     *
     * @param DOMDocument $dom The document where the node will be created.
     * @return DOMNode The generated image frame node.
     */
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        if (!$this->enabled) {
            return $dom->createElement('text:p'); // Bild ist deaktiviert, leeren Absatz zurückgeben
        }

        error_log("[ImageElement] Generating image node for " . $this->imagePath);

        $frame = $dom->createElement('draw:frame');
        $styleName = $this->imageOptions['style-name'] ?? StyleMapper::generateStyleName($this->imageOptions);
        $frame->setAttribute('draw:style-name', $styleName);

        // Basis-Attribute (mit Fallbacks)
        $frame->setAttribute('text:anchor-type', $this->imageOptions['text:anchor-type'] ?? 'paragraph');
        $frame->setAttribute('svg:width', $this->imageOptions['svg:width'] ?? '5cm');
        $frame->setAttribute('svg:height', $this->imageOptions['svg:height'] ?? '3cm');

        // Alignment-Handling (zentriert, links, rechts, absolut etc.)
        $align = $this->rawOptions['align'] ?? null;
        error_log("📦 Aktuelles align: " . $align);

        switch ($align) {
            case 'left':
                $frame->setAttribute('style:wrap', 'right');
                $frame->setAttribute('style:horizontal-pos', 'left');
                $frame->setAttribute('style:horizontal-rel', 'paragraph');
                break;
            case 'right':
                $frame->setAttribute('style:wrap', 'left');
                $frame->setAttribute('style:horizontal-pos', 'right');
                $frame->setAttribute('style:horizontal-rel', 'paragraph');
                break;
            case 'center':
                $frame->setAttribute('style:wrap', 'none');
                $frame->setAttribute('style:horizontal-pos', 'center');
                $frame->setAttribute('style:horizontal-rel', 'paragraph');
                break;
            case 'absolute':
                $frame->setAttribute('style:wrap', 'none');
                $frame->setAttribute('style:horizontal-pos', 'from-left');
                $frame->setAttribute('style:horizontal-rel', 'page-content');
                break;
            default:
                // Falls kein align gesetzt, dann eventuell wrap direkt übernehmen
                if (!empty($this->imageOptions['style:wrap'])) {
                    $frame->setAttribute('style:wrap', $this->imageOptions['style:wrap']);
                }
                if (!empty($this->imageOptions['style:horizontal-pos'])) {
                    $frame->setAttribute('style:horizontal-pos', $this->imageOptions['style:horizontal-pos']);
                }
                if (!empty($this->imageOptions['style:horizontal-rel'])) {
                    $frame->setAttribute('style:horizontal-rel', $this->imageOptions['style:horizontal-rel']);
                }
        }

        // Vertikale Ausrichtung
        if (!empty($this->imageOptions['style:vertical-pos'])) {
            $frame->setAttribute('style:vertical-pos', $this->imageOptions['style:vertical-pos']);
            $frame->setAttribute('style:vertical-rel', $this->imageOptions['style:vertical-rel'] ?? 'paragraph');
        }

        // Absolute Positionierungen (optional)
        if (!empty($this->imageOptions['svg:x'])) {
            $frame->setAttribute('svg:x', $this->imageOptions['svg:x']);
        }
        if (!empty($this->imageOptions['svg:y'])) {
            $frame->setAttribute('svg:y', $this->imageOptions['svg:y']);
        }

        // 🔁 Synchrone Styles zur Verwendung in styles.xml
        $this->imageOptions['style:wrap'] = $frame->getAttribute('style:wrap');
        $this->imageOptions['style:horizontal-pos'] = $frame->getAttribute('style:horizontal-pos');
        $this->imageOptions['style:horizontal-rel'] = $frame->getAttribute('style:horizontal-rel');

        if ($frame->hasAttribute('style:vertical-pos')) {
            $this->imageOptions['style:vertical-pos'] = $frame->getAttribute('style:vertical-pos');
            $this->imageOptions['style:vertical-rel'] = $frame->getAttribute('style:vertical-rel');
        }

        // ➕ Bild einfügen
        $image = $dom->createElement('draw:image');
        $image->setAttribute('xlink:href', 'Pictures/' . $this->filename);
        $image->setAttribute('xlink:type', 'simple');
        $image->setAttribute('xlink:show', 'embed');
        $image->setAttribute('xlink:actuate', 'onLoad');

        $frame->appendChild($image);

        // ✅ Registriere den Style für späteren Export in styles.xml
        StyleMapper::registerImageStyle($styleName, $this->imageOptions);


        return $frame;
    }


    /**
     * Returns a list of image assets required for the ODT (to be added to Pictures folder).
     *
     * @return array List of image references with ID and path.
     */
    public function getImageAssets(): array
    {
        return [
            [
                'id' => basename($this->imagePath),
                'path' => $this->imagePath
            ]
        ];
    }

    /**
     * Returns style definitions (if any) associated with this image.
     *
     * @return array Empty or customized style array.
     */
    public function getStyleDefinitions(): array
    {
        return [];
    }

    /**
     * Gets the full path to the image file.
     *
     * @return string
     */
    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    /**
     * Returns all image-related options passed to this element.
     *
     * @return array
     */
    public function getImageOptions(): array
    {
        return $this->imageOptions;
    }

    /**
     * Summary of registerStyles
     * @return void
     */
    public function registerStyles(): void
    {
        if (!empty($this->rawOptions)) {
            $this->setStyle($this->rawOptions);
        }
    }


    /**
     * Summary of setStyle
     * @param array $options
     * @return ImageElement
     */
    public function setStyle(array $options): self
    {
        $this->rawOptions = $options;
        $this->imageOptions = StyleMapper::mapImageStyleOptions($options);

        $styleName = StyleMapper::generateStyleName($this->imageOptions);
        $this->imageOptions['style-name'] = $styleName;

        StyleMapper::registerImageStyle($styleName, $this->imageOptions);

        return $this;
    }





}
