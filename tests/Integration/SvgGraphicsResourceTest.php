<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\ConcreteMappingPreflight;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\Mapping\NativeObjectActionMapping;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\ImageInfo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class SvgGraphicsResourceTest extends TestCase
{
    private const NS = [
        'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
        'draw' => 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
        'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
        'text' => 'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
        'svg' => 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
        'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
        'xlink' => 'http://www.w3.org/1999/xlink',
    ];
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                rmdir($path);
            }
        }
    }

    public function testSvgMetadataAndOneDimensionalUnitsAreRuntimeIndependent(): void
    {
        $path = $this->svg('viewBox="0 0 40 20"');
        $info = ImageInfo::fromPath($path);
        self::assertSame('image/svg+xml', $info->mime);
        self::assertSame(2.0, $info->width / $info->height);
        $image = new ImageElement($path, ['width' => '40mm']);
        $node = $image->toDomNode(new DOMDocument());
        self::assertSame('40mm', $node->getAttribute('svg:width'));
        self::assertSame('20mm', $node->getAttribute('svg:height'));
        $viewport = ImageInfo::fromPath($this->svg('width="1in" height="12.7mm" viewBox="0 0 24 24"'));
        self::assertSame(2.0, $viewport->width / $viewport->height);
        self::assertSame(['4cm', '7cm'], $info->completeDimensions('4cm', '7cm'));
        $default = (new ImageElement($path))->toDomNode(new DOMDocument());
        self::assertSame('5cm', $default->getAttribute('svg:width'));
        self::assertSame('3cm', $default->getAttribute('svg:height'));
    }

    public function testSvgWithoutRatioKeepsExplicitDimensionsAndRejectsInference(): void
    {
        $path = $this->svg('');
        $info = ImageInfo::fromPath($path);
        self::assertNull($info->width);
        $image = new ImageElement($path, ['width' => '4cm', 'height' => '2cm']);
        self::assertSame('4cm', $image->toDomNode(new DOMDocument())->getAttribute('svg:width'));
        $this->expectException(InvalidArgumentException::class);
        new ImageElement($path, ['width' => '4cm']);
    }

    #[DataProvider('unsafeSvgProvider')]
    public function testUnsupportedSvgFailsBeforePackaging(string $xml): void
    {
        $path = $this->file('unsafe.svg', $xml);
        $this->expectException(InvalidArgumentException::class);
        new ImageElement($path, ['width' => '1cm', 'height' => '1cm']);
    }

    public static function unsafeSvgProvider(): iterable
    {
        $root = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"';
        yield 'script' => [$root . '><script>alert(1)</script></svg>'];
        yield 'event' => [$root . ' onload="alert(1)"/>'];
        yield 'external' => [$root . '><image href="https://example.invalid/a.png"/></svg>'];
        yield 'local use' => [$root . '><use href="#item"/></svg>'];
        yield 'paint reference' => [$root . ' fill="url(https://example.invalid/a)"/>'];
        yield 'encoded paint reference' => [$root . ' fill="u&#114;l(#a)"/>'];
        yield 'CSS' => [$root . ' style="fill:red"/>'];
        yield 'foreign content' => [$root . '><foreignObject/></svg>'];
        yield 'DTD' => ['<!DOCTYPE svg [<!ENTITY x "data">]>' . $root . '/>'];
        yield 'external DTD' => ['<!DOCTYPE svg SYSTEM "file:///etc/hostname">' . $root . '/>'];
        yield 'processing instruction' => ['<?xml-stylesheet href="https://example.invalid/a"?>' . $root . '/>'];
        yield 'prefixed namespace' => ['<s:svg xmlns:s="http://www.w3.org/2000/svg"/>'];
        yield 'wrong namespace' => ['<svg xmlns="urn:wrong"/>'];
        yield 'invalid viewport' => ['<svg xmlns="http://www.w3.org/2000/svg" width="0" height="2"/>'];
        yield 'invalid viewBox' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 -1 3"/>'];
        yield 'deep nesting' => [$root . '>' . str_repeat('<g>', 34) . str_repeat('</g>', 34) . '</svg>'];
        yield 'large input' => [$root . '><!--' . str_repeat('x', 1048576) . '--></svg>'];
        yield 'many nodes' => [$root . '>' . str_repeat('<path d="M0 0"/>', 4100) . '</svg>'];
    }

    public function testSvgPassesActualMappingPreflightAndRatioAwareExecution(): void
    {
        $template = new SvgResourceInspectableTemplate($this->fixture(
            '<text:p><draw:frame draw:name="Logo" svg:width="9cm" svg:height="3cm">'
            . '<draw:image xlink:href="Pictures/original.png"/></draw:frame></text:p>'
        ));
        try {
            $contract = $template->inspectTemplate();
            $preflight = (new ConcreteMappingPreflight())->preflight(
                new MappingDefinition([], [new NativeObjectActionMapping(
                    ApplicationPath::parse('image'), 'frame', 'Logo', 'replace-image'
                )]),
                $contract,
                ['image' => ['source' => $this->svg('viewBox="0 0 40 20"'), 'options' => ['width' => '40mm']]],
                $template->inspect()
            );
            self::assertTrue($preflight->ready());
            $template->automateNativeObjectActions($contract, $preflight);
            $frame = $this->named($template->content(), 'frame', 'Logo');
            self::assertSame('40mm', $frame->getAttribute('svg:width'));
            self::assertSame('20mm', $frame->getAttribute('svg:height'));
        } finally {
            $template->cleanup();
        }
    }

    public function testNamedReplacementNeverOverwritesAResourceSharedByOtherConsumers(): void
    {
        $old = file_get_contents(dirname(__DIR__, 2) . '/assets/banner.png');
        $new = file_get_contents(dirname(__DIR__, 2) . '/assets/WaltDietzney.png');
        $path = $this->fixture('<text:p>' . $this->frame('A') . $this->frame('B') . '</text:p>');
        $this->putPackageResource($path, 'Pictures/shared.png', $old);
        $template = new SvgResourceInspectableTemplate($path);
        try {
            $replacement = $this->file('shared.png', $new);
            $template->replaceImageByName('A', $replacement, ['height' => '4cm']);
            $a = $this->named($template->content(), 'frame', 'A');
            $b = $this->named($template->content(), 'frame', 'B');
            self::assertSame('5cm', $a->getAttribute('svg:width'));
            self::assertSame('4cm', $a->getAttribute('svg:height'));
            $href = $a->firstChild->getAttribute('xlink:href');
            self::assertNotSame('Pictures/shared.png', $href);
            self::assertSame('Pictures/shared.png', $b->firstChild->getAttribute('xlink:href'));
            $output = $this->file('result.odt', '');
            $template->save($output);
            $zip = new ZipArchive();
            $zip->open($output);
            self::assertSame($old, $zip->getFromName('Pictures/shared.png'));
            self::assertSame($new, $zip->getFromName($href));
            $zip->close();
        } finally {
            $template->cleanup();
        }
    }

    public function testStructuredIdenticalResourceIsReusedAndConflictsFailAtomically(): void
    {
        $template = new SvgResourceInspectableTemplate($this->fixture('<text:p>Before</text:p>'));
        try {
            $path = $this->svg('viewBox="0 0 40 20"');
            $image = new ImageElement($path, ['width' => '2cm']);
            $template->appendBody((new Paragraph())->addElement($image)->addElement($image));
            $output = $this->file('reuse.odt', '');
            $template->save($output);
            $zip = new ZipArchive();
            $zip->open($output);
            $resources = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (str_ends_with($zip->getNameIndex($i), '.svg')) {
                    $resources[] = $zip->getNameIndex($i);
                }
            }
            self::assertCount(1, $resources);
            self::assertStringContainsString('image/svg+xml', $zip->getFromName('META-INF/manifest.xml'));
            $zip->close();
            $before = $template->xml();
            $conflicting = $this->file(basename($path), '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 40"/>');
            try {
                $template->appendBody((new Paragraph())->addElement(new ImageElement($conflicting)));
                self::fail('Expected resource conflict.');
            } catch (\RuntimeException) {
                self::assertSame($before, $template->xml());
            }
        } finally {
            $template->cleanup();
        }
    }

    public function testBitmapReplacementClonesStyleAndFillButPreservesOtherConsumersAndGeometry(): void
    {
        $path = $this->bitmapFixture();
        $template = new SvgResourceInspectableTemplate($path);
        try {
            $other = $this->named($template->content(), 'rect', 'B')->C14N();
            $declaration = $this->named($template->stylesDom(), 'fill-image', 'OriginalFill')->C14N();
            $sharedStyle = $this->style($template->stylesDom(), 'Shared')->C14N();
            $replacement = $this->svg('viewBox="0 0 40 20"');
            $template->replaceBitmapFillByName('A', $replacement);
            $a = $this->named($template->content(), 'rect', 'A');
            self::assertSame('3cm', $a->getAttribute('svg:width'));
            self::assertSame('2cm', $a->getAttribute('svg:height'));
            self::assertSame($other, $this->named($template->content(), 'rect', 'B')->C14N());
            self::assertSame($declaration, $this->named($template->stylesDom(), 'fill-image', 'OriginalFill')->C14N());
            $newStyle = $a->getAttribute('draw:style-name');
            self::assertStringStartsWith('bitmap_object_', $newStyle);
            $style = $this->style($template->content(), $newStyle);
            $props = $style->getElementsByTagNameNS(self::NS['style'], 'graphic-properties')->item(0);
            self::assertSame('0.04cm', $props->getAttribute('svg:stroke-width'));
            self::assertSame('Parent', $style->getAttribute('style:parent-style-name'));
            $before = $template->xml();
            $template->replaceBitmapFillByName('A', $replacement);
            self::assertSame($before, $template->xml());
            $template->replaceBitmapFillByName('H', $replacement);
            self::assertSame($sharedStyle, $this->style($template->stylesDom(), 'Shared')->C14N());
            self::assertSame($other, $this->named($template->content(), 'rect', 'B')->C14N());
            $header = $this->named($template->stylesDom(), 'rect', 'H');
            self::assertNotNull($this->style($template->stylesDom(), $header->getAttribute('draw:style-name')));
            self::assertSame('Pictures/shared.png', $this->named($template->content(), 'frame', 'Direct')->firstChild->getAttribute('xlink:href'));
            $output = $this->file('bitmap.odt', '');
            $template->save($output);
            $reopened = new SvgResourceInspectableTemplate($output);
            try {
                self::assertSame($newStyle, $this->named($reopened->content(), 'rect', 'A')->getAttribute('draw:style-name'));
            } finally {
                $reopened->cleanup();
            }
        } finally {
            $template->cleanup();
        }
    }

    public function testBitmapAmbiguityAndLateFailureRollBackWithoutNewPackageFiles(): void
    {
        $template = new SvgResourceInspectableTemplate($this->bitmapFixture());
        $replacement = $this->svg('viewBox="0 0 40 20"');
        try {
            foreach (['Missing', 'Direct'] as $name) {
                $before = $template->xml();
                try {
                    $template->replaceBitmapFillByName($name, $replacement);
                    self::fail('Expected unsupported bitmap target.');
                } catch (InvalidArgumentException) {
                    self::assertSame($before, $template->xml());
                }
            }
            $automatic = $template->content()->getElementsByTagNameNS(self::NS['office'], 'automatic-styles')->item(0);
            $automatic->parentNode->removeChild($automatic);
            $before = $template->xml();
            try {
                $template->replaceBitmapFillByName('A', $replacement);
                self::fail('Expected late missing-container failure.');
            } catch (\LogicException) {
                self::assertSame($before, $template->xml());
            }
            $output = $this->file('rollback.odt', '');
            $template->save($output);
            $zip = new ZipArchive();
            $zip->open($output);
            self::assertFalse($zip->getFromName('Pictures/' . basename($replacement)));
            $zip->close();
        } finally {
            $template->cleanup();
        }
    }

    public function testBitmapTargetNamesMustBeUniqueAcrossParts(): void
    {
        $template = new SvgResourceInspectableTemplate($this->bitmapFixture());
        try {
            $this->named($template->stylesDom(), 'rect', 'H')->setAttribute('draw:name', 'A');
            $before = $template->xml();
            try {
                $template->replaceBitmapFillByName('A', $this->svg('viewBox="0 0 40 20"'));
                self::fail('Expected ambiguous cross-part target.');
            } catch (InvalidArgumentException) {
                self::assertSame($before, $template->xml());
            }
        } finally {
            $template->cleanup();
        }
    }

    public function testSvgIsRevalidatedWhenItsFileChangesAfterElementConstruction(): void
    {
        $template = new SvgResourceInspectableTemplate($this->fixture('<text:p>Before</text:p>'));
        try {
            $path = $this->svg('viewBox="0 0 40 20"');
            $image = new ImageElement($path);
            file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" onload="bad()"/>');
            $before = $template->xml();
            try {
                $template->appendBody((new Paragraph())->addElement($image));
                self::fail('Expected changed SVG to fail resource preparation.');
            } catch (InvalidArgumentException) {
                self::assertSame($before, $template->xml());
            }
        } finally {
            $template->cleanup();
        }
    }

    public function testBitmapInheritedFillAndBackgroundImageAreIsolated(): void
    {
        $template = new SvgResourceInspectableTemplate($this->bitmapFixture());
        try {
            $namespace = self::NS['style'];
            $shared = $this->style($template->stylesDom(), 'Shared');
            $props = $shared->getElementsByTagNameNS($namespace, 'graphic-properties')->item(0);
            $background = $template->stylesDom()->createElementNS($namespace, 'style:background-image');
            $background->setAttributeNS(self::NS['xlink'], 'xlink:href', 'Pictures/shared.png');
            $props->appendChild($background);
            $sharedBefore = $shared->C14N();
            $child = $template->stylesDom()->createElementNS($namespace, 'style:style');
            $child->setAttributeNS($namespace, 'style:name', 'Child');
            $child->setAttributeNS($namespace, 'style:family', 'graphic');
            $child->setAttributeNS($namespace, 'style:parent-style-name', 'Shared');
            $shared->parentNode->appendChild($child);
            $object = $this->named($template->content(), 'rect', 'A');
            $object->setAttributeNS(self::NS['draw'], 'draw:style-name', 'Child');
            $template->replaceBitmapFillByName('A', $this->svg('viewBox="0 0 40 20"'));
            self::assertSame($sharedBefore, $this->style($template->stylesDom(), 'Shared')->C14N());
            $clone = $this->style($template->content(), $object->getAttribute('draw:style-name'));
            self::assertSame('Shared', $clone->getAttribute('style:parent-style-name'));
            $newBackground = $clone->getElementsByTagNameNS($namespace, 'background-image')->item(0);
            self::assertNotSame('Pictures/shared.png', $newBackground->getAttribute('xlink:href'));
        } finally {
            $template->cleanup();
        }
    }

    public function testFreshlyInsertedSvgCanBeReplacedByNameWithoutSaveReload(): void
    {
        $template = new SvgResourceInspectableTemplate($this->fixture('<text:p>{{Logo}}</text:p>'));
        try {
            $old = $this->svg('viewBox="0 0 40 20"');
            $new = $this->file('other.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 40"/>');
            $template->setImage('Logo', $old, ['width' => '4cm', 'height' => '2cm']);
            self::assertNotNull($this->named($template->content(), 'frame', 'Logo'));
            $template->replaceImageByName('Logo', $new, ['width' => '2cm', 'height' => '4cm']);
            $node = $this->named($template->content(), 'frame', 'Logo');
            self::assertSame('Pictures/other.svg', $node->firstChild->getAttribute('xlink:href'));
            self::assertSame('2cm', $node->getAttribute('svg:width'));
            self::assertSame('4cm', $node->getAttribute('svg:height'));
            $output = $this->file('fresh.odt', '');
            $template->save($output);
            $reopened = new SvgResourceInspectableTemplate($output);
            try {
                self::assertSame('Pictures/other.svg', $this->named($reopened->content(), 'frame', 'Logo')->firstChild->getAttribute('xlink:href'));
            } finally {
                $reopened->cleanup();
            }
        } finally {
            $template->cleanup();
        }
    }

    public function testWriterImageAlternativesReceiveConsistentMimeHints(): void
    {
        $template = new SvgResourceInspectableTemplate($this->fixture(
            '<text:p><draw:frame draw:name="Logo" svg:width="2cm" svg:height="1cm">'
            . '<draw:image xlink:href="Pictures/old.svg" draw:mime-type="image/svg+xml"/>'
            . '<draw:image xlink:href="Pictures/fallback.png" draw:mime-type="image/png"/>'
            . '</draw:frame></text:p>'
        ));
        try {
            $template->replaceImageByName('Logo', $this->svg('viewBox="0 0 40 20"'));
            $node = $this->named($template->content(), 'frame', 'Logo');
            foreach ($node->childNodes as $image) {
                self::assertSame('image/svg+xml', $image->getAttribute('draw:mime-type'));
                self::assertSame('Pictures/icon.svg', $image->getAttribute('xlink:href'));
            }
        } finally {
            $template->cleanup();
        }
    }

    public function testManifestEscapesResourceNamesWithoutDuplicatingRepeatedEntries(): void
    {
        $template = new SvgResourceInspectableTemplate($this->fixture('<text:p>{{Logo}}</text:p>'));
        try {
            $path = $this->file('mark&"quote.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 20"/>');
            $template->setImage('Logo', $path);
            $output = $this->file('manifest.odt', '');
            $previous = null;
            for ($i = 0; $i < 2; $i++) {
                $template->save($output);
                $zip = new ZipArchive();
                $zip->open($output);
                $xml = $zip->getFromName('META-INF/manifest.xml');
                $zip->close();
                $dom = new DOMDocument();
                self::assertTrue($dom->loadXML($xml));
                $matches = 0;
                foreach ($dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:manifest:1.0', 'file-entry') as $entry) {
                    if ($entry->getAttribute('manifest:full-path') === 'Pictures/' . basename($path)) {
                        $matches++;
                        self::assertSame('image/svg+xml', $entry->getAttribute('manifest:media-type'));
                    }
                }
                self::assertSame(1, $matches);
                if ($previous !== null) {
                    self::assertSame($previous, $xml);
                }
                $previous = $xml;
            }
        } finally {
            $template->cleanup();
        }
    }

    private function bitmapFixture(): string
    {
        $styles = '<style:style style:name="Parent" style:family="graphic"><style:graphic-properties draw:stroke="solid"/></style:style>'
            . '<style:style style:name="Shared" style:family="graphic" style:parent-style-name="Parent">'
            . '<style:graphic-properties draw:fill="bitmap" draw:fill-image-name="OriginalFill" svg:stroke-width="0.04cm" style:repeat="stretch"/>'
            . '</style:style><draw:fill-image draw:name="OriginalFill" xlink:href="Pictures/shared.png" xlink:type="simple"/>';
        $shape = static fn (string $name): string => '<draw:rect draw:name="' . $name
            . '" draw:style-name="Shared" svg:width="3cm" svg:height="2cm" text:anchor-type="as-char"><text:p/></draw:rect>';
        $path = $this->fixture('<text:p>' . $shape('A') . $shape('B') . $this->frame('Direct') . '</text:p>', $styles, '<text:p>' . $shape('H') . '</text:p>');
        $this->putPackageResource($path, 'Pictures/shared.png', file_get_contents(dirname(__DIR__, 2) . '/assets/banner.png'));
        return $path;
    }

    private function frame(string $name): string
    {
        return '<draw:frame draw:name="' . $name . '" svg:width="2cm" svg:height="1cm">'
            . '<draw:image xlink:href="Pictures/shared.png"/></draw:frame>';
    }

    private function svg(string $attributes): string
    {
        return $this->file('icon.svg', '<svg xmlns="http://www.w3.org/2000/svg" ' . $attributes . '><path d="M0 0L10 10"/></svg>');
    }

    private function file(string $name, string $bytes): string
    {
        $dir = sys_get_temp_dir() . '/odt-i04-' . bin2hex(random_bytes(6));
        mkdir($dir);
        $this->paths[] = $dir;
        $path = $dir . '/' . $name;
        file_put_contents($path, $bytes);
        $this->paths[] = $path;
        return $path;
    }

    private function fixture(string $body, string $styles = '', string $header = ''): string
    {
        $path = $this->file('fixture.odt', '');
        copy(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt', $path);
        $zip = new ZipArchive();
        $zip->open($path);
        foreach (['content.xml', 'styles.xml'] as $part) {
            $dom = new DOMDocument();
            $dom->loadXML($zip->getFromName($part));
            if ($part === 'content.xml') {
                $target = $dom->getElementsByTagNameNS(self::NS['office'], 'text')->item(0);
                while ($target->firstChild !== null) {
                    $target->removeChild($target->firstChild);
                }
                $this->appendXml($dom, $target, $body);
            } else {
                $this->appendXml($dom, $dom->getElementsByTagNameNS(self::NS['office'], 'styles')->item(0), $styles);
                if ($header !== '') {
                    $master = $dom->getElementsByTagNameNS(self::NS['style'], 'master-page')->item(0);
                    $node = $dom->createElementNS(self::NS['style'], 'style:header');
                    $this->appendXml($dom, $node, $header);
                    $master->appendChild($node);
                }
            }
            $zip->addFromString($part, $dom->saveXML());
        }
        $zip->close();
        return $path;
    }

    private function appendXml(DOMDocument $dom, DOMElement $parent, string $xml): void
    {
        $ns = '';
        foreach (self::NS as $prefix => $uri) {
            $ns .= ' xmlns:' . $prefix . '="' . $uri . '"';
        }
        $fragment = new DOMDocument();
        $fragment->loadXML('<root' . $ns . '>' . $xml . '</root>');
        foreach ($fragment->documentElement->childNodes as $child) {
            $parent->appendChild($dom->importNode($child, true));
        }
    }

    private function putPackageResource(string $path, string $href, string $bytes): void
    {
        $zip = new ZipArchive();
        $zip->open($path);
        $zip->addFromString($href, $bytes);
        $zip->close();
    }

    private function named(DOMDocument $dom, string $type, string $name): ?DOMElement
    {
        foreach ($dom->getElementsByTagNameNS(self::NS['draw'], $type) as $node) {
            if ($node->getAttributeNS(self::NS['draw'], 'name') === $name) {
                return $node;
            }
        }
        return null;
    }

    private function style(DOMDocument $dom, string $name): ?DOMElement
    {
        foreach ($dom->getElementsByTagNameNS(self::NS['style'], 'style') as $node) {
            if ($node->getAttributeNS(self::NS['style'], 'name') === $name) {
                return $node;
            }
        }
        return null;
    }
}

final class SvgResourceInspectableTemplate extends OdtTemplate
{
    public function content(): DOMDocument
    {
        return $this->documentContext()->contentDom();
    }

    public function stylesDom(): DOMDocument
    {
        return $this->documentContext()->stylesDom();
    }

    public function xml(): string
    {
        return $this->content()->saveXML() . $this->stylesDom()->saveXML();
    }
}
