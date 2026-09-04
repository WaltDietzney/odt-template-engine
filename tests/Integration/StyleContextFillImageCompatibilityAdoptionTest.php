<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Contracts\HasStyles;
use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextFillImageCompatibilityAdoptionTest extends TestCase
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
    public function testLegacyFillImagesAreAdoptedOnlyForTheCurrentDocument(): void
    {
        $first = $this->template();
        $firstImage = new CircularImageElement($this->imagePath('Logo.png'));
        $first->assign(['test1' => $firstImage]);
        $first->render();
        $firstName = 'cv_photo_Logo';

        $second = $this->template();
        $secondImage = new CircularImageElement($this->imagePath('banner.png'));
        $second->assign(['test1' => $secondImage]);
        $second->render();
        $secondName = 'cv_photo_banner';
        $output = $this->outputPath('isolated-fill');
        $second->save($output);

        $styles = $this->entry($output, 'styles.xml');
        self::assertStringNotContainsString('draw:name="' . $firstName . '"', $styles);
        self::assertStringContainsString('draw:name="' . $secondName . '"', $styles);
        self::assertStringContainsString('Pictures/banner.png', $styles);
        self::assertTrue($this->contains($output, 'Pictures/banner.png'));
        self::assertArrayHasKey($firstName, StyleMapper::getRegisteredFillImages());
    }

    #[RunInSeparateProcess]
    public function testNormalSemanticCircularImageStillUsesOneDocumentLocalDeclaration(): void
    {
        $template = $this->template();
        $template->setElement('test1', new CircularImageElement($this->imagePath('Logo.png')));
        $output = $this->outputPath('semantic-fill');
        $template->save($output);

        $styles = $this->entry($output, 'styles.xml');
        self::assertSame(1, substr_count($styles, 'draw:name="cv_photo_Logo"'));
        self::assertStringContainsString('Pictures/Logo.png', $styles);
        self::assertTrue($this->contains($output, 'Pictures/Logo.png'));
        self::assertArrayNotHasKey('cv_photo_Logo', StyleMapper::getRegisteredFillImages());
    }

    #[RunInSeparateProcess]
    public function testDirectLegacyFillRegistrationIsAdoptedWhenCurrentDomReferencesIt(): void
    {
        $name = 'DirectLegacyFill';
        StyleMapper::registerFillImage($name, $this->imagePath('Logo.png'));
        $template = $this->template();
        $template->assign(['test1' => new LegacyFillReferenceElement($name)]);
        $template->render();
        $output = $this->outputPath('direct-fill');
        $template->save($output);

        $styles = $this->entry($output, 'styles.xml');
        self::assertSame(1, substr_count($styles, 'draw:name="' . $name . '"'));
        self::assertStringContainsString('Pictures/Logo.png', $styles);
    }

    #[RunInSeparateProcess]
    public function testRepeatedLegacySaveDoesNotDuplicateFillImageDeclaration(): void
    {
        $template = $this->template();
        $template->assign(['test1' => new CircularImageElement($this->imagePath('Logo.png'))]);
        $template->render();
        $first = $this->outputPath('repeated-fill-one');
        $second = $this->outputPath('repeated-fill-two');
        $template->save($first);
        $template->save($second);

        foreach ([$first, $second] as $output) {
            self::assertSame(1, substr_count($this->entry($output, 'styles.xml'), 'draw:name="cv_photo_Logo"'));
        }
    }

    #[RunInSeparateProcess]
    public function testLoadRemovesCurrentLegacyFillReferenceBeforeLaterSave(): void
    {
        $template = $this->template();
        $template->assign(['test1' => new CircularImageElement($this->imagePath('Logo.png'))]);
        $template->render();
        $template->load();
        $output = $this->outputPath('after-load-fill');
        $template->save($output);

        self::assertStringNotContainsString('draw:name="cv_photo_Logo"', $this->entry($output, 'styles.xml'));
    }

    private function template(): OdtTemplate
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        return $template;
    }

    private function outputPath(string $label): string
    {
        $path = sys_get_temp_dir() . '/sr06f5b-' . $label . '-' . bin2hex(random_bytes(5)) . '.odt';
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

    private function imagePath(string $filename): string
    {
        return dirname(__DIR__, 2) . '/assets/' . $filename;
    }
}

final class LegacyFillReferenceElement extends OdtElement implements HasStyles
{
    public function __construct(private readonly string $fillName)
    {
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $shape = $dom->createElement('draw:custom-shape');
        $shape->setAttribute('draw:fill-image-name', $this->fillName);
        return $shape;
    }

    public function registerStyles(): void
    {
    }

    public function getStyleDefinitions(): array
    {
        return [];
    }
}
