<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes current graphic/drawing boundaries without approving them as
 * the future semantic graphic model.
 */
final class StyleContextGraphicDrawingBoundaryCharacterizationTest extends TestCase
{
    /** @var list<string> */
    private array $outputs = [];

    /** @var list<OdtTemplate> */
    private array $templates = [];

    protected function tearDown(): void
    {
        foreach ($this->templates as $template) {
            $template->cleanup();
        }
        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testImageOptionsParticipateIndependentlyInCurrentLegacyStyleIdentity(): void
    {
        $path = $this->imagePath();
        $base = $this->styleName(new ImageElement($path, ['width' => '4cm', 'height' => '3cm']));

        $variants = [
            'width' => ['width' => '5cm', 'height' => '3cm'],
            'height' => ['width' => '4cm', 'height' => '6cm'],
            'anchor' => ['width' => '4cm', 'height' => '3cm', 'anchor' => 'as-char'],
            'horizontal position' => ['width' => '4cm', 'height' => '3cm', 'horizontal-pos' => 'right'],
            'horizontal relation' => ['width' => '4cm', 'height' => '3cm', 'horizontal-rel' => 'page'],
            'vertical position' => ['width' => '4cm', 'height' => '3cm', 'vertical-pos' => 'top'],
            'vertical relation' => ['width' => '4cm', 'height' => '3cm', 'vertical-rel' => 'page'],
            'wrap' => ['width' => '4cm', 'height' => '3cm', 'wrap' => 'left'],
        ];

        foreach ($variants as $label => $options) {
            self::assertNotSame($base, $this->styleName(new ImageElement($path, $options)), $label . ' changes current legacy style identity');
        }

        self::assertSame(
            $base,
            $this->styleName(new ImageElement($path, ['width' => '4cm', 'height' => '3cm', 'align' => 'right'])),
            'align does not change current legacy style identity'
        );
    }

    public function testImageMaterializationAddsResolvedPlacementToStateAndIsStable(): void
    {
        $image = new ImageElement($this->imagePath(), ['width' => '2cm', 'align' => 'right']);
        $before = $image->getImageOptions();
        self::assertArrayNotHasKey('style:wrap', $before);

        $firstDom = new DOMDocument('1.0', 'UTF-8');
        $firstFrame = $image->toDomNode($firstDom);
        $afterFirst = $image->getImageOptions();

        self::assertInstanceOf(DOMElement::class, $firstFrame);
        self::assertSame('right', $firstFrame->getAttribute('style:horizontal-pos'));
        self::assertSame('left', $firstFrame->getAttribute('style:wrap'));
        self::assertSame('paragraph', $firstFrame->getAttribute('style:horizontal-rel'));
        self::assertSame('right', $afterFirst['style:horizontal-pos']);
        self::assertSame('left', $afterFirst['style:wrap']);

        $secondDom = new DOMDocument('1.0', 'UTF-8');
        $secondFrame = $image->toDomNode($secondDom);
        self::assertSame($afterFirst, $image->getImageOptions());
        self::assertSame($firstFrame->attributes?->getNamedItem('style:wrap')?->nodeValue, $secondFrame->attributes?->getNamedItem('style:wrap')?->nodeValue);
        self::assertSame($firstFrame->attributes?->getNamedItem('style:horizontal-pos')?->nodeValue, $secondFrame->attributes?->getNamedItem('style:horizontal-pos')?->nodeValue);
    }

    public function testDrawTextBoxSeparatesDrawingStructureFromReferencedGraphicStyle(): void
    {
        $box = new DrawTextBox('BoundaryTextBox', [
            'width' => '6cm',
            'height' => '2cm',
            'anchor' => 'as-char',
            'horizontal-pos' => 'right',
            'horizontal-rel' => 'paragraph',
            'vertical-pos' => 'top',
            'vertical-rel' => 'paragraph',
            'background-color' => '#123456',
            'border-bottom' => '0.05cm solid #abcdef',
            'padding' => '0.1cm',
        ]);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $box->toDomNode($dom);
        self::assertInstanceOf(DOMElement::class, $frame);

        self::assertSame('BoundaryTextBox', $frame->getAttribute('draw:name'));
        self::assertSame('as-char', $frame->getAttribute('text:anchor-type'));
        self::assertSame('6cm', $frame->getAttribute('svg:width'));
        self::assertSame('2cm', $frame->getAttribute('svg:height'));
        self::assertSame('0', $frame->getAttribute('draw:z-index'));
        self::assertSame('right', $frame->getAttribute('style:horizontal-pos'));
        self::assertSame('paragraph', $frame->getAttribute('style:horizontal-rel'));

        $semantic = iterator_to_array($box->getOwnStyleRequirements(), false)[0];
        self::assertSame($semantic->name(), $frame->getAttribute('draw:style-name'));

        $styleDom = new DOMDocument('1.0', 'UTF-8');
        $legacyStyle = $box->toStyleDomNode($styleDom);
        self::assertInstanceOf(DOMElement::class, $legacyStyle);
        self::assertNotSame($frame->getAttribute('draw:style-name'), $legacyStyle->getAttribute('style:name'));
        self::assertSame('graphic', $legacyStyle->getAttribute('style:family'));
        $properties = $legacyStyle->getElementsByTagName('style:graphic-properties')->item(0);
        self::assertInstanceOf(DOMElement::class, $properties);
        self::assertSame('#123456', $properties->getAttribute('fo:background-color'));
        self::assertSame('solid', $properties->getAttribute('draw:fill'));
        self::assertSame('0.05cm solid #abcdef', $properties->getAttribute('fo:border-bottom'));
        self::assertSame('0.1cm', $properties->getAttribute('fo:padding'));
        self::assertSame('', $properties->getAttribute('draw:name'));
    }

    public function testCircularImageEmitsCustomShapeGeometryStyleFillDeclarationAndResource(): void
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        $image = new CircularImageElement($this->imagePath(), ['width' => '3cm', 'height' => '3cm']);
        $template->setElement('test1', $image);
        $output = sys_get_temp_dir() . '/odt-graphic-boundary-' . bin2hex(random_bytes(6)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        $contentDom = $this->dom($content);
        $xpath = new DOMXPath($contentDom);
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $shape = $xpath->query('//draw:custom-shape')->item(0);
        self::assertInstanceOf(DOMElement::class, $shape);
        $geometry = $xpath->query('descendant::draw:enhanced-geometry', $shape)->item(0);
        self::assertInstanceOf(DOMElement::class, $geometry);
        self::assertSame('ellipse', $geometry->getAttribute('draw:type'));
        self::assertSame('0', $shape->getAttribute('draw:z-index'));

        $fillName = 'cv_photo_' . pathinfo($this->imagePath(), PATHINFO_FILENAME);
        self::assertStringContainsString('draw:fill-image', $styles);
        self::assertStringContainsString('draw:name="' . $fillName . '"', $styles);
        self::assertStringContainsString('draw:fill-image-name="' . $fillName . '"', $styles);
        self::assertTrue($this->contains($output, 'Pictures/' . basename($this->imagePath())));
        self::assertStringContainsString('Pictures/' . basename($this->imagePath()), $this->entry($output, 'META-INF/manifest.xml'));
    }

    public function testStyleMapperPreservesTheCurrentMixedGraphicMappingBoundary(): void
    {
        self::assertSame([
            'svg:width' => '4cm',
            'svg:height' => '2cm',
            'style:wrap' => 'left',
            'align' => 'right',
            'text:anchor-type' => 'as-char',
            'style:horizontal-pos' => 'right',
            'style:horizontal-rel' => 'paragraph',
            'style:vertical-pos' => 'top',
            'style:vertical-rel' => 'page',
        ], StyleMapper::mapImageStyleOptions([
            'width' => '4cm', 'height' => '2cm', 'wrap' => 'left', 'anchor' => 'as-char',
            'align' => 'right', 'horizontal-pos' => 'right', 'horizontal-rel' => 'paragraph',
            'vertical-pos' => 'top', 'vertical-rel' => 'page',
        ]));

        self::assertSame([
            'fo:background-color' => '#123456',
            'draw:fill' => 'solid',
            'draw:fill-color' => '#123456',
            'fo:border-bottom' => '0.05cm solid #abcdef',
            'svg:rx' => '0.2cm',
            'svg:ry' => '0.3cm',
            'style:horizontal-pos' => 'right',
            'style:horizontal-rel' => 'paragraph',
        ], StyleMapper::mapFrameStyleOptions([
            'background-color' => '#123456', 'border-bottom' => '0.05cm solid #abcdef',
            'rx' => '0.2cm', 'ry' => '0.3cm', 'horizontal-pos' => 'right',
            'horizontal-rel' => 'paragraph',
        ]));
    }

    private function styleName(ImageElement $image): string
    {
        return (string) ($image->getImageOptions()['style-name'] ?? '');
    }

    private function entry(string $path, string $name): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            $value = $zip->getFromName($name);
            self::assertIsString($value);
            return $value;
        } finally {
            $zip->close();
        }
    }

    private function contains(string $path, string $name): bool
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            return $zip->locateName($name) !== false;
        } finally {
            $zip->close();
        }
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}
