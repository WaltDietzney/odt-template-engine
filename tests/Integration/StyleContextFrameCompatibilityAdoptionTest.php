<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Contracts\HasStyles;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextFrameCompatibilityAdoptionTest extends TestCase
{
    /** @var list<OdtTemplate> */
    private array $templates = [];

    /** @var list<string> */
    private array $outputs = [];

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
    public function testLegacyFrameStylesAreAdoptedOnlyForCurrentDocumentReferences(): void
    {
        $first = $this->template();
        $firstBox = new DrawTextBox('FrameA', ['width' => '4cm']);
        $first->assign(['test1' => $firstBox]);
        $first->render();
        $firstName = array_key_first($firstBox->getFrameStyleRequirements());

        $second = $this->template();
        $secondBox = new DrawTextBox('FrameB', ['width' => '7cm']);
        $second->assign(['test1' => $secondBox]);
        $second->render();
        $secondName = array_key_first($secondBox->getFrameStyleRequirements());
        $output = $this->outputPath('isolated-frame');
        $second->save($output);

        $styles = $this->entry($output, 'styles.xml');
        self::assertIsString($firstName);
        self::assertIsString($secondName);
        self::assertStringNotContainsString('style:name="' . $firstName . '"', $styles);
        self::assertStringContainsString('style:name="' . $secondName . '"', $styles);
        self::assertArrayHasKey($firstName, StyleMapper::getFrameStyles());
    }

    public function testSemanticDrawTextBoxStillUsesDocumentLocalGraphicDefinition(): void
    {
        $template = $this->template();
        $box = new DrawTextBox('SemanticFrame', ['background-color' => '#d4a100']);
        $template->setElement('test1', $box);
        $semanticRequirements = iterator_to_array($box->getOwnStyleRequirements());
        $semanticName = $semanticRequirements[0]->name();
        $output = $this->outputPath('semantic-frame');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        self::assertIsString($semanticName);
        self::assertStringContainsString('draw:style-name="' . $semanticName . '"', $content);
        self::assertSame(1, substr_count($styles, 'style:name="' . $semanticName . '"'));
    }

    #[RunInSeparateProcess]
    public function testDirectLegacyFrameRegistrationIsAdoptedWhenCurrentDomReferencesIt(): void
    {
        $name = 'DirectLegacyFrame';
        StyleMapper::$frameStyles[$name] = ['draw:fill' => 'solid'];
        $template = $this->template();
        $template->assign(['test1' => new LegacyFrameReferenceElement($name)]);
        $template->render();
        $output = $this->outputPath('direct-frame');
        $template->save($output);

        $styles = $this->entry($output, 'styles.xml');
        self::assertSame(1, substr_count($styles, 'style:name="' . $name . '"'));
        self::assertStringContainsString('draw:fill="solid"', $styles);
    }

    #[RunInSeparateProcess]
    public function testRepeatedLegacySaveKeepsFrameDefinitionUnique(): void
    {
        $template = $this->template();
        $box = new DrawTextBox('RepeatedFrame', ['width' => '5cm']);
        $template->assign(['test1' => $box]);
        $template->render();
        $first = $this->outputPath('repeated-frame-one');
        $second = $this->outputPath('repeated-frame-two');
        $template->save($first);
        $template->save($second);

        $name = (string) array_key_first($box->getFrameStyleRequirements());
        foreach ([$first, $second] as $output) {
            self::assertSame(1, substr_count($this->entry($output, 'styles.xml'), 'style:name="' . $name . '"'));
        }
    }

    private function template(): OdtTemplate
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        return $template;
    }

    private function outputPath(string $label): string
    {
        $path = sys_get_temp_dir() . '/sr06f5c-' . $label . '-' . bin2hex(random_bytes(5)) . '.odt';
        $this->outputs[] = $path;
        return $path;
    }

    private function entry(string $path, string $entry): string
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

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }
}

final class LegacyFrameReferenceElement extends OdtElement implements HasStyles
{
    public function __construct(private readonly string $styleName)
    {
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $frame = $dom->createElement('draw:frame');
        $frame->setAttribute('draw:style-name', $this->styleName);
        return $frame;
    }

    public function registerStyles(): void
    {
    }

    public function getStyleDefinitions(): array
    {
        return [];
    }
}
