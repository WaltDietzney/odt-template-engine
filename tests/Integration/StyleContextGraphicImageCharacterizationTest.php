<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes the pre-01F-D graphic/image style and nested-materialization paths.
 *
 * These tests intentionally preserve observations of the current global
 * compatibility registries; they are not assertions of the desired 01F-D design.
 */
final class StyleContextGraphicImageCharacterizationTest extends TestCase
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

    #[RunInSeparateProcess]
    public function testUnattachedDrawTextBoxDoesNotLeakIntoNormalDocumentFinalization(): void
    {
        $box = new DrawTextBox('D1ForeignBox', ['background-color' => '#d101fd']);
        $style = array_key_first($box->getStyleDefinitions());
        self::assertIsString($style);

        $output = $this->saveTemplate(new OdtTemplate($this->templatePath('template_18_ListStyles.odt')));
        $styles = $this->zipEntry($output, 'styles.xml');

        self::assertStringNotContainsString('style:name="' . $style . '"', $styles);
    }

    #[RunInSeparateProcess]
    public function testUnattachedImageElementDoesNotLeakIntoNormalDocumentFinalization(): void
    {
        $image = new ImageElement($this->imagePath(), ['width' => '2cm', 'wrap' => 'left']);
        $style = $image->getImageOptions()['style-name'];

        $output = $this->saveTemplate(new OdtTemplate($this->templatePath('template_18_ListStyles.odt')));
        $styles = $this->zipEntry($output, 'styles.xml');

        self::assertStringNotContainsString('style:name="' . $style . '"', $styles);
    }

    #[RunInSeparateProcess]
    public function testInterleavedUnattachedImageStylesAreAbsentFromBothDocuments(): void
    {
        $imageA = new ImageElement($this->imagePath(), ['width' => '2cm']);
        $imageB = new ImageElement($this->imagePath(), ['width' => '7cm']);
        $styleA = $imageA->getImageOptions()['style-name'];
        $styleB = $imageB->getImageOptions()['style-name'];
        $templateA = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $templateB = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));

        $outputA = $this->saveTemplate($templateA);
        $outputB = $this->saveTemplate($templateB);

        foreach ([$outputA, $outputB] as $output) {
            $styles = $this->zipEntry($output, 'styles.xml');
            self::assertStringNotContainsString('style:name="' . $styleA . '"', $styles);
            self::assertStringNotContainsString('style:name="' . $styleB . '"', $styles);
        }
    }

    #[RunInSeparateProcess]
    public function testSameImageStyleNameUsesLatestRegisteredDefinition(): void
    {
        $name = 'D1_Conflicting_Image';
        StyleMapper::registerImageStyle($name, ['svg:width' => '1cm']);
        StyleMapper::registerImageStyle($name, ['svg:width' => '9cm']);

        self::assertSame(['svg:width' => '9cm'], StyleMapper::getRegisteredImageStyles()[$name]);
    }

    #[RunInSeparateProcess]
    public function testRepeatedSaveKeepsGraphicDeclarationsUniqueAndXmlParseable(): void
    {
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $image = new ImageElement($this->imagePath(), ['width' => '3cm']);
        $box = new DrawTextBox('D1RepeatedBox', ['background-color' => '#d10d1f']);
        $template->setElement('my_list', $image);
        $first = $this->saveTemplate($template);
        $second = $this->saveTemplate($template);

        foreach ([$first, $second] as $index => $output) {
            $styles = $this->zipEntry($output, 'styles.xml');
            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($styles));
            self::assertSame(1, substr_count($styles, 'style:name="' . $image->getImageOptions()['style-name'] . '"'));
        }

        // Construction itself registers the frame style even when the box is not inserted.
        $frameStyle = array_key_first($box->getStyleDefinitions());
        self::assertIsString($frameStyle);
        self::assertNotSame('', $frameStyle);
    }

    #[RunInSeparateProcess]
    public function testRefreshDoesNotInjectImageStylesButSubsequentSaveDoes(): void
    {
        $image = new ImageElement($this->imagePath(), ['width' => '4cm']);
        $style = $image->getImageOptions()['style-name'];
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));

        $template->refresh();
        $afterRefresh = $this->inspectableStyles($template);
        self::assertStringNotContainsString('style:name="' . $style . '"', $afterRefresh);

        $output = $this->saveTemplate($template);
        self::assertStringNotContainsString('style:name="' . $style . '"', $this->zipEntry($output, 'styles.xml'));
    }

    #[RunInSeparateProcess]
    public function testLoadDoesNotClearGlobalImageRegistration(): void
    {
        $image = new ImageElement($this->imagePath(), ['width' => '5cm']);
        $style = $image->getImageOptions()['style-name'];
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->load();
        $output = $this->saveTemplate($template);

        self::assertStringNotContainsString('style:name="' . $style . '"', $this->zipEntry($output, 'styles.xml'));
    }

    public function testRepeatedImageDomMaterializationStabilizesResolvedOptions(): void
    {
        $image = new ImageElement($this->imagePath(), ['width' => '2cm', 'align' => 'right']);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $before = $image->getImageOptions();
        $image->toDomNode($dom);
        $afterFirst = $image->getImageOptions();
        $image->toDomNode($dom);
        $afterSecond = $image->getImageOptions();

        self::assertArrayNotHasKey('style:wrap', $before);
        self::assertSame('left', $afterFirst['style:wrap']);
        self::assertSame('right', $afterFirst['style:horizontal-pos']);
        self::assertSame($afterFirst, $afterSecond);
    }

    public function testNestedStyledParagraphInDrawTextBoxIsMaterializedTransitively(): void
    {
        $paragraph = (new Paragraph())->addText('nested styled text', [
            'font-family' => 'D1 Nested Font',
            'color' => '#d10001',
        ]);
        $box = (new DrawTextBox('D1NestedTextBox'))->addElement($paragraph);
        $output = $this->saveTemplateWithElement('test1', $box, 'nested-textbox');
        $content = $this->zipEntry($output, 'content.xml');
        $styles = $this->zipEntry($output, 'styles.xml');

        self::assertStringContainsString('nested styled text', $content);
        self::assertStringContainsString('text:style-name="', $content);
        self::assertStringContainsString('D1 Nested Font', $styles);
    }

    public function testNestedImageInDrawTextBoxReceivesTransitiveAssetPreparation(): void
    {
        $image = new ImageElement($this->imagePath(), ['width' => '2cm', 'anchor' => 'as-char']);
        $box = (new DrawTextBox('D1NestedImageBox'))->addElement($image);
        $output = $this->saveTemplateWithElement('test1', $box, 'nested-imagebox');
        $content = $this->zipEntry($output, 'content.xml');
        $manifest = $this->zipEntry($output, 'META-INF/manifest.xml');

        self::assertStringContainsString('draw:image', $content);
        self::assertStringContainsString('Pictures/' . basename($this->imagePath()), $content);
        self::assertTrue($this->archiveContains($output, 'Pictures/' . basename($this->imagePath())));
        self::assertStringContainsString('Pictures/' . basename($this->imagePath()), $manifest);
    }

    public function testDrawTextBoxFrameStyleIsMaterializedOnceByItsStyleNodePath(): void
    {
        $box = new DrawTextBox('D1FramePath', ['background-color' => '#d1aa00']);
        $style = array_key_first($box->getStyleDefinitions());
        self::assertIsString($style);
        $output = $this->saveTemplateWithElement('test1', $box, 'frame-path');
        $styles = $this->zipEntry($output, 'styles.xml');

        self::assertSame(1, substr_count($styles, 'style:name="' . $style . '"'));
        self::assertStringContainsString('draw:style-name="' . $style . '"', $this->zipEntry($output, 'content.xml'));
    }

    private function saveTemplate(OdtTemplate $template): string
    {
        if (!in_array($template, $this->templates, true)) {
            $this->templates[] = $template;
        }
        $output = sys_get_temp_dir() . '/odt-style-graphic-d1-' . bin2hex(random_bytes(6)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);
        return $output;
    }

    private function saveTemplateWithElement(string $placeholder, object $element, string $label): string
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $template->setElement($placeholder, $element);
        return $this->saveTemplate($template);
    }

    private function zipEntry(string $path, string $entry): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            $value = $zip->getFromName($entry);
            self::assertIsString($value);
            return $value;
        } finally {
            $zip->close();
        }
    }

    private function inspectableStyles(OdtTemplate $template): string
    {
        $method = new \ReflectionMethod($template, 'documentContext');
        $method->setAccessible(true);
        $context = $method->invoke($template);
        return $context->stylesDom()->saveXML() ?: '';
    }

    private function archiveContains(string $path, string $entry): bool
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            return $zip->locateName($entry) !== false;
        } finally {
            $zip->close();
        }
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
