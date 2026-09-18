<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\ConcreteMappingPreflight;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\Mapping\NativeObjectActionMapping;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class NativeObjectActionAutomationE4Test extends TestCase
{
    private const DRAW = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testSectionAndBookmarkActionsReuseTheirTypedMutationSemantics(): void
    {
        $template = new E4InspectableTemplate($this->fixture(
            '<text:section text:name="Profile" text:style-name="Keep">'
            . '<text:p>old profile</text:p></text:section>'
            . '<text:p>before<text:bookmark-start text:name="Signature"/>old'
            . '<text:bookmark-end text:name="Signature"/>after</text:p>'
        ));
        $definition = new MappingDefinition([], [
            new NativeObjectActionMapping(ApplicationPath::parse('profile.content'), 'section', 'Profile', 'replace-content'),
            new NativeObjectActionMapping(ApplicationPath::parse('signature.text'), 'bookmark', 'Signature', 'replace-text'),
        ]);
        $contract = $template->inspectTemplate();
        $preflight = (new ConcreteMappingPreflight())->preflight(
            $definition,
            $contract,
            [
                'profile' => ['content' => (new Paragraph())->addText('new profile')],
                'signature' => ['text' => 'Ada'],
            ],
            $template->inspect()
        );
        self::assertTrue($preflight->ready());

        $template->automateNativeObjectActions($contract, $preflight);

        $section = $this->named($template->workingContent(), 'section', 'Profile');
        self::assertNotNull($section);
        self::assertSame('Keep', $section->getAttribute('text:style-name'));
        self::assertSame('new profile', $section->textContent);
        self::assertSame(1, $this->countNamed($template->workingContent(), 'bookmark-start', 'Signature'));
        self::assertSame(1, $this->countNamed($template->workingContent(), 'bookmark-end', 'Signature'));
        self::assertSame('new profilebeforeAdaafter', $template->workingContent()->textContent);
    }

    public function testNonReadyPreflightIsRejectedWithoutMutation(): void
    {
        $template = new E4InspectableTemplate($this->fixture(
            '<text:section text:name="Profile"><text:p>original</text:p></text:section>'
        ));
        $contract = $template->inspectTemplate();
        $definition = new MappingDefinition([], [
            new NativeObjectActionMapping(ApplicationPath::parse('profile.content'), 'section', 'Profile', 'replace-content'),
        ]);
        $preflight = (new ConcreteMappingPreflight())->preflight(
            $definition,
            $contract,
            ['profile' => ['content' => 'not an OdtElement']],
            $template->inspect()
        );
        self::assertFalse($preflight->ready());
        $before = $template->workingContent()->C14N();

        try {
            $template->automateNativeObjectActions($contract, $preflight);
            self::fail('Expected E4 to reject a non-READY preflight.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('READY concrete preflight', $exception->getMessage());
        }
        self::assertSame($before, $template->workingContent()->C14N());
    }

    public function testSectionAndBookmarkActionsUseBoundedStylesRegionOwners(): void
    {
        $template = new E4InspectableTemplate($this->fixture(
            '<text:p>ordinary body</text:p>',
            '<style:header><text:section text:name="HeaderSection"><text:p>old header</text:p></text:section>'
            . '<text:p>left<text:bookmark-start text:name="HeaderMark"/>old'
            . '<text:bookmark-end text:name="HeaderMark"/>right</text:p></style:header>'
        ));
        $contract = $template->inspectTemplate();
        $preflight = (new ConcreteMappingPreflight())->preflight(
            new MappingDefinition([], [
                new NativeObjectActionMapping(ApplicationPath::parse('section'), 'section', 'HeaderSection', 'replace-content'),
                new NativeObjectActionMapping(ApplicationPath::parse('bookmark'), 'bookmark', 'HeaderMark', 'replace-text'),
            ]),
            $contract,
            [
                'section' => (new Paragraph())->addText('new header'),
                'bookmark' => 'Replaced',
            ],
            $template->inspect()
        );
        self::assertTrue($preflight->ready());

        $template->automateNativeObjectActions($contract, $preflight);

        $section = $this->named($template->workingStyles(), 'section', 'HeaderSection');
        self::assertNotNull($section);
        self::assertSame('new header', $section->textContent);
        self::assertSame(1, $this->countNamed($template->workingStyles(), 'bookmark-start', 'HeaderMark'));
        self::assertSame(1, $this->countNamed($template->workingStyles(), 'bookmark-end', 'HeaderMark'));
        self::assertStringContainsString('leftReplacedright', $template->workingStyles()->textContent);
        self::assertSame('ordinary body', $template->workingContent()->textContent);
    }

    #[DataProvider('imageDimensionProvider')]
    public function testPhaseEFrameReplacementUsesPreserveProportionalAndExplicitDimensions(
        array $options,
        ?string $expectedWidth,
        ?string $expectedHeight
    ): void {
        $template = new E4InspectableTemplate($this->fixture(
            '<text:p>body remains</text:p>',
            '<style:header><draw:frame draw:name="Portrait" svg:width="9cm" svg:height="3cm" '
            . 'draw:style-name="ImageStyle" text:anchor-type="paragraph" draw:z-index="7">'
            . '<draw:image xlink:href="Pictures/original.png"/></draw:frame>'
            . '<draw:frame draw:name="Other" svg:width="2cm" svg:height="1cm">'
            . '<draw:image xlink:href="Pictures/untouched.png"/></draw:frame></style:header>'
        ));
        $image = $this->imagePath();
        $contract = $template->inspectTemplate();
        $definition = new MappingDefinition([], [
            new NativeObjectActionMapping(ApplicationPath::parse('person.photo'), 'frame', 'Portrait', 'replace-image'),
        ]);
        $preflight = (new ConcreteMappingPreflight())->preflight(
            $definition,
            $contract,
            ['person' => ['photo' => ['source' => $image, 'options' => $options]]],
            $template->inspect()
        );
        self::assertTrue($preflight->ready());
        $beforeOther = $this->named($template->workingStyles(), 'frame', 'Other')?->C14N();

        $template->automateNativeObjectActions($contract, $preflight);

        $frame = $this->named($template->workingStyles(), 'frame', 'Portrait');
        self::assertNotNull($frame);
        self::assertSame($expectedWidth ?? '9cm', $frame->getAttribute('svg:width'));
        self::assertSame($expectedHeight ?? '3cm', $frame->getAttribute('svg:height'));
        self::assertSame('ImageStyle', $frame->getAttribute('draw:style-name'));
        self::assertSame('paragraph', $frame->getAttribute('text:anchor-type'));
        self::assertSame('7', $frame->getAttribute('draw:z-index'));
        self::assertSame('Pictures/' . basename($image), $frame->firstChild?->getAttribute('xlink:href'));
        self::assertSame($beforeOther, $this->named($template->workingStyles(), 'frame', 'Other')?->C14N());

        $output = $this->temporaryPath('.odt');
        $template->save($output);
        $reopened = new E4InspectableTemplate($output);
        $savedFrame = $this->named($reopened->workingStyles(), 'frame', 'Portrait');
        self::assertNotNull($savedFrame);
        self::assertSame($expectedWidth ?? '9cm', $savedFrame->getAttribute('svg:width'));
        self::assertSame($expectedHeight ?? '3cm', $savedFrame->getAttribute('svg:height'));

        $zip = new ZipArchive();
        self::assertSame(true, $zip->open($output));
        try {
            self::assertNotFalse($zip->locateName('Pictures/' . basename($image)));
        } finally {
            $zip->close();
        }
    }

    public static function imageDimensionProvider(): iterable
    {
        yield 'preserve both authored dimensions' => [[], null, null];
        yield 'width only derives height in supplied unit' => [['width' => '42mm'], '42mm', '28mm'];
        yield 'height only derives width in supplied unit' => [['height' => '4cm'], '6cm', '4cm'];
        yield 'two explicit dimensions are exact' => [['width' => '7cm', 'height' => '2in'], '7cm', '2in'];
    }

    public function testOneDimensionalSvgWithoutIntrinsicRatioFailsBeforeMutation(): void
    {
        $svg = $this->temporaryPath('.svg');
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $template = new E4InspectableTemplate($this->fixture(
            '<text:p>body remains</text:p>',
            '<style:header><draw:frame draw:name="Portrait" svg:width="9cm" svg:height="6cm">'
            . '<draw:image xlink:href="Pictures/original.png"/></draw:frame></style:header>'
        ));
        $contract = $template->inspectTemplate();
        $definition = new MappingDefinition([], [
            new NativeObjectActionMapping(ApplicationPath::parse('photo'), 'frame', 'Portrait', 'replace-image'),
        ]);
        $preflight = (new ConcreteMappingPreflight())->preflight(
            $definition,
            $contract,
            ['photo' => ['source' => $svg, 'options' => ['width' => '6cm']]],
            $template->inspect()
        );
        self::assertTrue($preflight->ready(), 'E2-C validates the SVG source; E4 owns the conditional ratio gate.');
        $before = $template->workingStyles()->C14N();

        try {
            $template->automateNativeObjectActions($contract, $preflight);
            self::fail('Expected missing intrinsic SVG ratio to fail before mutation.');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('determinable intrinsic image ratio', $exception->getMessage());
        }

        self::assertSame($before, $template->workingStyles()->C14N());

        $output = $this->temporaryPath('.odt');
        $template->save($output);
        $zip = new ZipArchive();
        self::assertSame(true, $zip->open($output));
        try {
            self::assertFalse($zip->locateName('Pictures/' . basename($svg)) !== false);
        } finally {
            $zip->close();
        }
    }

    public function testSvgWithoutRatioWorksWhenDimensionsArePreservedOrBothExplicit(): void
    {
        $svg = $this->temporaryPath('.svg');
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"/>');
        foreach ([[], ['width' => '7cm', 'height' => '3cm']] as $options) {
            $template = new E4InspectableTemplate($this->fixture(
                '<text:p>body remains</text:p>',
                '<style:header><draw:frame draw:name="Portrait" svg:width="9cm" svg:height="6cm">'
                . '<draw:image xlink:href="Pictures/original.png"/></draw:frame></style:header>'
            ));
            $contract = $template->inspectTemplate();
            $preflight = (new ConcreteMappingPreflight())->preflight(
                new MappingDefinition([], [
                    new NativeObjectActionMapping(ApplicationPath::parse('photo'), 'frame', 'Portrait', 'replace-image'),
                ]),
                $contract,
                ['photo' => ['source' => $svg, 'options' => $options]],
                $template->inspect()
            );
            self::assertTrue($preflight->ready());
            $template->automateNativeObjectActions($contract, $preflight);
            $frame = $this->named($template->workingStyles(), 'frame', 'Portrait');
            self::assertNotNull($frame);
            self::assertSame($options['width'] ?? '9cm', $frame->getAttribute('svg:width'));
            self::assertSame($options['height'] ?? '6cm', $frame->getAttribute('svg:height'));
        }
    }

    public function testSectionReplacementCannotDestroyAnotherSelectedNativeAction(): void
    {
        $template = new E4InspectableTemplate($this->fixture(
            '<text:section text:name="Profile"><text:p>old'
            . '<text:bookmark-start text:name="Signature"/>text<text:bookmark-end text:name="Signature"/>'
            . '</text:p><draw:frame draw:name="Portrait"><draw:image xlink:href="Pictures/old.png"/></draw:frame>'
            . '</text:section>'
        ));
        $contract = $template->inspectTemplate();
        $image = $this->imagePath();
        $preflight = (new ConcreteMappingPreflight())->preflight(
            new MappingDefinition([], [
                new NativeObjectActionMapping(ApplicationPath::parse('content'), 'section', 'Profile', 'replace-content'),
                new NativeObjectActionMapping(ApplicationPath::parse('signature'), 'bookmark', 'Signature', 'replace-text'),
                new NativeObjectActionMapping(ApplicationPath::parse('photo'), 'frame', 'Portrait', 'replace-image'),
            ]),
            $contract,
            [
                'content' => (new Paragraph())->addText('replacement'),
                'signature' => 'Ada',
                'photo' => ['source' => $image, 'options' => []],
            ],
            $template->inspect()
        );
        self::assertTrue($preflight->ready());
        $beforeContent = $template->workingContent()->C14N();
        $beforeStyles = $template->workingStyles()->C14N();

        try {
            $template->automateNativeObjectActions($contract, $preflight);
            self::fail('Expected destructive Section/native-action interference to be rejected.');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('would destroy selected', $exception->getMessage());
        }

        self::assertSame($beforeContent, $template->workingContent()->C14N());
        self::assertSame($beforeStyles, $template->workingStyles()->C14N());
    }

    public function testFrameWithIntrinsicSvgViewBoxUsesItsIntrinsicRatio(): void
    {
        $svg = $this->temporaryPath('.svg');
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 200"/>');
        $template = new E4InspectableTemplate($this->fixture(
            '<text:p>body remains</text:p>',
            '<style:header><draw:frame draw:name="Portrait" svg:width="9cm" svg:height="6cm">'
            . '<draw:image xlink:href="Pictures/original.png"/></draw:frame></style:header>'
        ));
        $contract = $template->inspectTemplate();
        $preflight = (new ConcreteMappingPreflight())->preflight(
            new MappingDefinition([], [
                new NativeObjectActionMapping(ApplicationPath::parse('photo'), 'frame', 'Portrait', 'replace-image'),
            ]),
            $contract,
            ['photo' => ['source' => $svg, 'options' => ['width' => '6cm']]],
            $template->inspect()
        );
        self::assertTrue($preflight->ready());
        $template->automateNativeObjectActions($contract, $preflight);
        $frame = $this->named($template->workingStyles(), 'frame', 'Portrait');
        self::assertNotNull($frame);
        self::assertSame('6cm', $frame->getAttribute('svg:width'));
        self::assertSame('4cm', $frame->getAttribute('svg:height'));
    }

    public function testFooterFrameReplacementIsBoundedToItsMasterPageFooter(): void
    {
        $template = new E4InspectableTemplate($this->fixture(
            '<text:p>body image stays separate</text:p>',
            '<style:footer><draw:frame draw:name="FooterPortrait" svg:width="8cm" svg:height="4cm">'
            . '<draw:image xlink:href="Pictures/footer-old.png"/></draw:frame></style:footer>'
        ));
        $contract = $template->inspectTemplate();
        $preflight = (new ConcreteMappingPreflight())->preflight(
            new MappingDefinition([], [
                new NativeObjectActionMapping(ApplicationPath::parse('photo'), 'frame', 'FooterPortrait', 'replace-image'),
            ]),
            $contract,
            ['photo' => ['source' => $this->imagePath(), 'options' => []]],
            $template->inspect()
        );
        self::assertTrue($preflight->ready());
        $template->automateNativeObjectActions($contract, $preflight);
        $frame = $this->named($template->workingStyles(), 'frame', 'FooterPortrait');
        self::assertNotNull($frame);
        self::assertSame('8cm', $frame->getAttribute('svg:width'));
        self::assertSame('4cm', $frame->getAttribute('svg:height'));
        self::assertSame('Pictures/' . basename($this->imagePath()), $frame->firstChild?->getAttribute('xlink:href'));
    }

    public function testSectionReplacementCannotDestroySelectedFrameBeforeAnyMutation(): void
    {
        $template = new E4InspectableTemplate($this->fixture(
            '<text:section text:name="Profile"><text:p>old'
            . '<draw:frame draw:name="Portrait"><draw:image xlink:href="Pictures/old.png"/></draw:frame>'
            . '</text:p></text:section>'
        ));
        $contract = $template->inspectTemplate();
        $preflight = (new ConcreteMappingPreflight())->preflight(
            new MappingDefinition([], [
                new NativeObjectActionMapping(ApplicationPath::parse('content'), 'section', 'Profile', 'replace-content'),
                new NativeObjectActionMapping(ApplicationPath::parse('photo'), 'frame', 'Portrait', 'replace-image'),
            ]),
            $contract,
            [
                'content' => (new Paragraph())->addText('replacement'),
                'photo' => ['source' => $this->imagePath(), 'options' => []],
            ],
            $template->inspect()
        );
        self::assertTrue($preflight->ready());
        $before = $template->workingContent()->C14N();
        try {
            $template->automateNativeObjectActions($contract, $preflight);
            self::fail('Expected Section/Frame destructive interference to be rejected.');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('would destroy selected', $exception->getMessage());
        }
        self::assertSame($before, $template->workingContent()->C14N());
    }

    public function testChangedWorkingDocumentWithDuplicateFrameIdentityFailsInsteadOfUpdatingBoth(): void
    {
        $template = new E4InspectableTemplate($this->fixture(
            '<text:p>body remains</text:p>',
            '<style:header><draw:frame draw:name="Portrait" svg:width="9cm" svg:height="6cm">'
            . '<draw:image xlink:href="Pictures/original.png"/></draw:frame></style:header>'
        ));
        $contract = $template->inspectTemplate();
        $image = $this->imagePath();
        $preflight = (new ConcreteMappingPreflight())->preflight(
            new MappingDefinition([], [
                new NativeObjectActionMapping(ApplicationPath::parse('photo'), 'frame', 'Portrait', 'replace-image'),
            ]),
            $contract,
            ['photo' => ['source' => $image, 'options' => []]],
            $template->inspect()
        );
        self::assertTrue($preflight->ready());
        $template->appendDuplicateHeaderFrame('Portrait');
        $before = $template->workingStyles()->C14N();

        try {
            $template->automateNativeObjectActions($contract, $preflight);
            self::fail('Expected duplicate current frame identity to fail explicitly.');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('cannot be deterministically localized', $exception->getMessage());
        }
        self::assertSame($before, $template->workingStyles()->C14N());
    }

    private function fixture(string $body, string $styles = ''): string
    {
        $path = $this->temporaryPath('.odt');
        $namespaces = ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="' . self::TEXT . '"'
            . ' xmlns:draw="' . self::DRAW . '"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
            . ' xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0"'
            . ' xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"'
            . ' xmlns:xlink="http://www.w3.org/1999/xlink"';
        $zip = new ZipArchive();
        self::assertSame(true, $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
        $zip->addFromString('content.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content' . $namespaces . '><office:automatic-styles/><office:body><office:text>'
            . $body . '</office:text></office:body></office:document-content>');
        $zip->addFromString('styles.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles' . $namespaces . '><office:styles/><office:automatic-styles/>'
            . '<office:master-styles><style:master-page style:name="Standard">' . $styles
            . '</style:master-page></office:master-styles></office:document-styles>');
        $zip->addFromString('meta.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"><office:meta/></office:document-meta>');
        $zip->addFromString('META-INF/manifest.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">'
            . '<manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/>'
            . '<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>'
            . '</manifest:manifest>');
        $zip->close();
        return $path;
    }

    private function temporaryPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/e4-native-' . bin2hex(random_bytes(8)) . $suffix;
        $this->paths[] = $path;
        return $path;
    }

    private function imagePath(): string
    {
        $path = dirname(__DIR__, 2) . '/assets/banner.png';
        self::assertFileExists($path);
        return $path;
    }

    private function named(DOMDocument $document, string $kind, string $name): ?\DOMElement
    {
        $namespace = $kind === 'section' ? self::TEXT : self::DRAW;
        $tag = $kind === 'section' ? 'section' : $kind;
        $attribute = match ($kind) {
            'section' => 'text:name',
            'frame' => 'draw:name',
            default => throw new \InvalidArgumentException('Unsupported fixture lookup kind.'),
        };
        foreach ($document->getElementsByTagNameNS($namespace, $tag) as $element) {
            if ($element instanceof \DOMElement && $element->getAttribute($attribute) === $name) {
                return $element;
            }
        }
        return null;
    }

    private function countNamed(DOMDocument $document, string $tag, string $name): int
    {
        $count = 0;
        foreach ($document->getElementsByTagNameNS(self::TEXT, $tag) as $element) {
            if ($element instanceof \DOMElement && $element->getAttribute('text:name') === $name) {
                ++$count;
            }
        }
        return $count;
    }
}

final class E4InspectableTemplate extends OdtTemplate
{
    public function workingContent(): DOMDocument
    {
        return $this->documentContext()->contentDom();
    }

    public function workingStyles(): DOMDocument
    {
        return $this->documentContext()->stylesDom();
    }

    public function appendDuplicateHeaderFrame(string $name): void
    {
        $drawing = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
        $style = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
        $masters = $this->workingStyles()->getElementsByTagNameNS($style, 'master-page');
        $master = $masters->item(0);
        if (!$master instanceof \DOMElement) {
            throw new \RuntimeException('Fixture master page is missing.');
        }
        $header = $master->getElementsByTagNameNS($style, 'header')->item(0);
        if (!$header instanceof \DOMElement) {
            throw new \RuntimeException('Fixture header is missing.');
        }
        $frame = $this->workingStyles()->createElementNS($drawing, 'draw:frame');
        $frame->setAttribute('draw:name', $name);
        $frame->appendChild($this->workingStyles()->createElementNS($drawing, 'draw:image'));
        $header->appendChild($frame);
    }
}
