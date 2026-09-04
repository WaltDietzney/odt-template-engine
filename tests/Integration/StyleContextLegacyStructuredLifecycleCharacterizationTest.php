<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes the legacy assign/render lifecycle before further narrowing.
 *
 * The tests intentionally preserve surprising static-state and refresh
 * behavior as evidence; they do not introduce reset or parity behavior.
 */
final class StyleContextLegacyStructuredLifecycleCharacterizationTest extends TestCase
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
    public function testLegacyStaticGraphicRegistrationsSurviveLoadAndLeakIntoLaterLegacySave(): void
    {
        $first = $this->template();
        $firstImage = new ImageElement($this->imagePath(), ['width' => '4cm']);
        $first->assign(['test1' => $firstImage]);
        $first->render();
        $firstName = (string) $firstImage->getImageOptions()['style-name'];

        self::assertArrayHasKey($firstName, StyleMapper::getRegisteredImageStyles());
        $first->load();
        self::assertArrayHasKey(
            $firstName,
            StyleMapper::getRegisteredImageStyles(),
            'load() resets document state but does not reset static legacy registries'
        );

        $second = $this->template();
        $secondImage = new ImageElement($this->imagePath(), ['width' => '7cm']);
        $second->assign(['test1' => $secondImage]);
        $second->render();
        $secondName = (string) $secondImage->getImageOptions()['style-name'];
        $output = $this->outputPath('static-image-leak');
        $second->save($output);

        $styles = $this->entry($output, 'styles.xml');
        self::assertStringContainsString('style:name="' . $firstName . '"', $styles);
        self::assertStringContainsString('style:name="' . $secondName . '"', $styles);
    }

    #[RunInSeparateProcess]
    public function testRepeatedLegacyRenderIsRegistryIdempotentButRepeatsElementMaterialization(): void
    {
        $box = new DrawTextBox('RepeatedLegacyBox', ['background-color' => '#d4a100']);
        $image = new ImageElement($this->imagePath(), ['width' => '4cm']);
        $circular = new CircularImageElement($this->imagePath());
        $template = $this->template();
        $template->assign([
            'box' => $box,
            'image' => $image,
            'circular' => $circular,
        ]);

        $template->render();
        $firstCounts = [
            'frame' => count(StyleMapper::getFrameStyles()),
            'image' => count(StyleMapper::getRegisteredImageStyles()),
            'fill' => count(StyleMapper::getRegisteredFillImages()),
        ];
        $firstFillState = $circular->getFillImageRequirements();

        $template->render();
        self::assertSame($firstCounts['frame'], count(StyleMapper::getFrameStyles()));
        self::assertSame($firstCounts['image'], count(StyleMapper::getRegisteredImageStyles()));
        self::assertSame($firstCounts['fill'], count(StyleMapper::getRegisteredFillImages()));
        self::assertSame($firstFillState, $circular->getFillImageRequirements());
    }

    #[RunInSeparateProcess]
    public function testLegacySaveMaterializesGraphicStateButRefreshSkipsLegacyImageFinalization(): void
    {
        $saveTemplate = $this->template();
        $saveImage = new CircularImageElement($this->imagePath());
        $saveTemplate->assign(['test1' => $saveImage]);
        $saveTemplate->render();
        $saveStyleName = (string) array_key_first($saveImage->getImageStyleRequirements());
        $saveFillName = (string) array_key_first($saveImage->getFillImageRequirements());
        $saveOutput = $this->outputPath('legacy-save');
        $saveTemplate->save($saveOutput);
        $savedStyles = $this->entry($saveOutput, 'styles.xml');

        self::assertStringContainsString('style:name="' . $saveStyleName . '"', $savedStyles);
        self::assertStringContainsString('draw:name="' . $saveFillName . '"', $savedStyles);

        $refreshTemplate = $this->template();
        $refreshImage = new CircularImageElement($this->imagePath());
        $refreshTemplate->assign(['test1' => $refreshImage]);
        $refreshTemplate->render();
        $refreshStyleName = (string) array_key_first($refreshImage->getImageStyleRequirements());
        $refreshFillName = (string) array_key_first($refreshImage->getFillImageRequirements());
        $refreshTemplate->refresh();
        $refreshedStyles = $refreshTemplate->stylesXmlForAudit();

        self::assertStringNotContainsString('style:name="' . $refreshStyleName . '"', $refreshedStyles);
        self::assertStringNotContainsString('draw:name="' . $refreshFillName . '"', $refreshedStyles);
    }

    #[RunInSeparateProcess]
    public function testNormalSetElementUsesDocumentLocalGraphicStateWhileLegacyRenderUsesStaticState(): void
    {
        $normal = $this->template();
        $normalImage = new ImageElement($this->imagePath(), ['width' => '4cm']);
        $normal->setElement('test1', $normalImage);
        $normalName = (string) $normalImage->getImageOptions()['style-name'];

        self::assertArrayHasKey($normalName, $normal->imageStylesForAudit());
        self::assertArrayNotHasKey($normalName, StyleMapper::getRegisteredImageStyles());

        $legacy = $this->template();
        $legacyImage = new ImageElement($this->imagePath(), ['width' => '7cm']);
        $legacy->assign(['test1' => $legacyImage]);
        $legacy->render();
        $legacyName = (string) $legacyImage->getImageOptions()['style-name'];

        self::assertArrayHasKey($legacyName, StyleMapper::getRegisteredImageStyles());
        self::assertArrayNotHasKey($normalName, $legacy->imageStylesForAudit());
    }

    private function template(): OdtTemplate
    {
        $template = new class($this->templatePath('sample_textfeld.odt')) extends OdtTemplate {
            public function imageStylesForAudit(): array
            {
                return $this->documentContext()->styleContext()->imageStyles();
            }

            public function stylesXmlForAudit(): string
            {
                return $this->documentContext()->stylesDom()->saveXML() ?: '';
            }
        };
        $this->templates[] = $template;

        return $template;
    }

    private function outputPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/sr06f3-' . $suffix . '-' . bin2hex(random_bytes(6)) . '.odt';
        $this->outputs[] = $path;

        return $path;
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

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}
