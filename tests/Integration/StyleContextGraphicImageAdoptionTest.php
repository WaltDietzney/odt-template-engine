<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextGraphicImageAdoptionTest extends TestCase
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
    public function testUnattachedFrameDoesNotLeakAndTopLevelFrameIsDocumentOwned(): void
    {
        $unattached = new DrawTextBox('UnattachedD4', ['background-color' => '#d40001']);
        $foreign = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $foreignOutput = $this->save($foreign);
        $foreignStyles = $this->entry($foreignOutput, 'styles.xml');
        $foreignName = array_key_first($unattached->getStyleDefinitions());

        self::assertIsString($foreignName);
        self::assertStringNotContainsString('style:name="' . $foreignName . '"', $foreignStyles);

        $template = new GraphicInspectableTemplate($this->templatePath('sample_textfeld.odt'));
        $box = new DrawTextBox('D4OwnedBox', ['background-color' => '#d40002']);
        $template->setElement('test1', $box);
        $name = array_key_first($box->getFrameStyleRequirements());

        self::assertIsString($name);
        self::assertArrayHasKey($name, $template->frameStyles());
        $output = $this->save($template);
        $styles = $this->entry($output, 'styles.xml');
        self::assertSame(1, substr_count($styles, 'style:name="' . $name . '"'));
    }

    #[RunInSeparateProcess]
    public function testTopLevelImageAdoptsResolvedStyleAndAsset(): void
    {
        $template = new GraphicInspectableTemplate($this->templatePath('template_18_ListStyles.odt'));
        $image = new ImageElement($this->imagePath(), ['width' => '2cm', 'align' => 'right']);
        $template->setElement('my_list', $image);

        $name = array_key_first($image->getImageStyleRequirements());
        self::assertIsString($name);
        self::assertArrayHasKey($name, $template->imageStyles());
        self::assertSame('left', $template->imageStyles()[$name]['style:wrap']);

        $output = $this->save($template);
        $styles = $this->entry($output, 'styles.xml');
        $manifest = $this->entry($output, 'META-INF/manifest.xml');
        self::assertStringContainsString('style:name="' . $name . '"', $styles);
        self::assertTrue($this->contains($output, 'Pictures/WaltDietzney.png'));
        self::assertStringContainsString('Pictures/WaltDietzney.png', $manifest);
    }

    #[RunInSeparateProcess]
    public function testTopLevelCircularImageAdoptsFillAndImageStylesAndAsset(): void
    {
        $template = new GraphicInspectableTemplate($this->templatePath('template_18_ListStyles.odt'));
        $image = new CircularImageElement($this->imagePath(), ['width' => '3cm', 'height' => '3cm']);
        $template->setElement('my_list', $image);

        $imageName = array_key_first($image->getImageStyleRequirements());
        $fillName = array_key_first($image->getFillImageRequirements());
        self::assertIsString($imageName);
        self::assertIsString($fillName);
        self::assertArrayHasKey($imageName, $template->imageStyles());
        self::assertArrayHasKey($fillName, $template->fillImages());

        $output = $this->save($template);
        $styles = $this->entry($output, 'styles.xml');
        $manifest = $this->entry($output, 'META-INF/manifest.xml');
        self::assertStringContainsString('style:name="' . $imageName . '"', $styles);
        self::assertStringContainsString('draw:name="' . $fillName . '"', $styles);
        self::assertTrue($this->contains($output, 'Pictures/WaltDietzney.png'));
        self::assertStringContainsString('Pictures/WaltDietzney.png', $manifest);
    }

    #[RunInSeparateProcess]
    public function testTopLevelGraphicRequirementsRemainIsolatedAcrossDocuments(): void
    {
        $templateA = new GraphicInspectableTemplate($this->templatePath('template_18_ListStyles.odt'));
        $templateB = new GraphicInspectableTemplate($this->templatePath('template_18_ListStyles.odt'));
        $imageA = new ImageElement($this->imagePath(), ['width' => '2cm']);
        $imageB = new ImageElement($this->imagePath(), ['width' => '8cm']);

        $templateA->setElement('my_list', $imageA);
        $templateB->setElement('my_list', $imageB);
        $nameA = array_key_first($imageA->getImageStyleRequirements());
        $nameB = array_key_first($imageB->getImageStyleRequirements());

        self::assertArrayHasKey($nameA, $templateA->imageStyles());
        self::assertArrayNotHasKey($nameA, $templateB->imageStyles());
        self::assertArrayHasKey($nameB, $templateB->imageStyles());
        self::assertArrayNotHasKey($nameB, $templateA->imageStyles());
    }

    private function save(OdtTemplate $template): string
    {
        if (!in_array($template, $this->templates, true)) {
            $this->templates[] = $template;
        }
        $output = sys_get_temp_dir() . '/odt-style-adoption-d4-' . bin2hex(random_bytes(6)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);
        return $output;
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

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}

final class GraphicInspectableTemplate extends OdtTemplate
{
    /** @return array<string, array<string, mixed>> */
    public function frameStyles(): array
    {
        return $this->documentContext()->styleContext()->frameStyles();
    }

    /** @return array<string, array<string, mixed>> */
    public function imageStyles(): array
    {
        return $this->documentContext()->styleContext()->imageStyles();
    }

    /** @return array<string, array<string, mixed>> */
    public function fillImages(): array
    {
        return $this->documentContext()->styleContext()->fillImages();
    }
}
