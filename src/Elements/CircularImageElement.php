<?php

namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMElement;
use DOMNode;

class CircularImageElement extends ImageElement
{
    private string $fillImageName = '';

    /** @var array<string, mixed> */
    private array $circularStyleOptions = [];

    private string $circularStyleName = '';

    public function __construct(string $imagePath, array $options = [])
    {
        parent::__construct($imagePath, $options);
        $this->width = $options['width'] ?? '3.4cm';
        $this->height = $options['height'] ?? '3.4cm';
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        // Use a draw:custom-shape with draw:type="ellipse" and fill the shape
        // with the image as a bitmap. This is how LibreOffice creates circular
        // images natively.
        $fillImageName = 'cv_photo_' . pathinfo($this->filename, PATHINFO_FILENAME);

        // Register the fill-image (creates <draw:fill-image> in styles.xml)
        $this->fillImageName = $fillImageName;

        // Register a graphic style with bitmap fill referencing the fill-image by name
        $styleOptions = [
            'draw:fill' => 'bitmap',
            'draw:fill-image-name' => $fillImageName,
            'draw:fill-image-width' => '100%',
            'draw:fill-image-height' => '100%',
            'style:repeat' => 'stretch',
            'draw:stroke' => 'none',
        ];
        $styleName = \OdtTemplateEngine\Utils\StyleMapper::generateStyleName($styleOptions);
        $this->circularStyleName = $styleName;
        $this->circularStyleOptions = $styleOptions;

        // Create the draw:custom-shape element
        $shape = $dom->createElement('draw:custom-shape');
        $shape->setAttribute('text:anchor-type', $this->anchor);
        $shape->setAttribute('svg:width', $this->width);
        $shape->setAttribute('svg:height', $this->height);
        $shape->setAttribute('text:animation', 'none');
        $shape->setAttribute('draw:style-name', $styleName);

        // Enhanced geometry defines the ellipse/circle shape.
        // Using the "U" command format that LibreOffice generates natively:
        //   U cx cy rx ry startangle endangle
        // viewBox 0 0 21600 21600 with center at 10800,10800 and rx=ry=10800
        // creates a full circle (360 degrees)
        $shape->setAttribute('draw:z-index', '0');

        $geo = $dom->createElement('draw:enhanced-geometry');
        $geo->setAttribute('svg:viewBox', '0 0 21600 21600');
        $geo->setAttribute('draw:type', 'ellipse');
        $geo->setAttribute('draw:enhanced-path', 'U 10800 10800 10800 10800 0 360 Z N');
        $geo->setAttribute('draw:glue-points', '10800 0 3163 3163 0 10800 3163 18437 10800 21600 18437 18437 21600 10800 18437 3163');
        $geo->setAttribute('draw:text-areas', '3163 3163 18437 18437');
        $geo->setAttribute('draw:text-path-allowed', 'false');
        $geo->setAttribute('draw:concentric-gradient-fill-allowed', 'false');
        $geo->setAttribute('dr3d:projection', 'parallel');
        $shape->appendChild($geo);

        return $shape;
    }

    /** @return array<string, array<string, mixed>> */
    public function getImageStyleRequirements(): array
    {
        if ($this->circularStyleName === '') {
            return [];
        }

        return [$this->circularStyleName => $this->circularStyleOptions];
    }

    /** @return array<string, array<string, mixed>> */
    public function getFillImageRequirements(): array
    {
        if ($this->fillImageName === '') {
            return [];
        }

        return [$this->fillImageName => [
            'name' => $this->fillImageName,
            'path' => $this->imagePath,
            'filename' => $this->filename,
        ]];
    }

    /**
     * No custom style node needed here – fill-image and graphic style are
     * handled by injectImageStyles() via StyleMapper registrations.
     *
     * @param DOMDocument $dom Unused.
     * @return null
     */
    public function toStyleDomNode(DOMDocument $dom): ?DOMElement
    {
        return null;
    }
}
