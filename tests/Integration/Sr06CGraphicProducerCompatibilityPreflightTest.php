<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementCollector;
use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class Sr06CGraphicProducerCompatibilityPreflightTest extends TestCase
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

    public function testCollectorPreservesTheThreeApprovedProducerOutcomes(): void
    {
        $collector = new StyleRequirementCollector();

        $box = new DrawTextBox('PreflightBox', [
            'width' => '6cm',
            'height' => '2cm',
            'background-color' => '#123456',
        ]);
        $image = new ImageElement($this->imagePath(), [
            'width' => '4cm',
            'height' => '2cm',
            'align' => 'right',
        ]);
        $circular = new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);

        $boxSemantic = iterator_to_array($collector->collectSemantic($box), false);
        $imageSemantic = iterator_to_array($collector->collectSemantic($image), false);
        $circularSemantic = iterator_to_array($collector->collectSemantic($circular), false);

        self::assertCount(1, $boxSemantic);
        self::assertSame('graphic', $boxSemantic[0]->family());
        self::assertSame([], $imageSemantic);
        self::assertCount(1, $circularSemantic);
        self::assertSame('graphic', $circularSemantic[0]->family());

        $boxLegacy = iterator_to_array($collector->collect($box), false);
        $imageLegacy = iterator_to_array($collector->collect($image), false);
        $circularLegacyBeforeDom = iterator_to_array($collector->collect($circular), false);

        self::assertTrue($this->containsFamily($boxLegacy, 'frame'));
        self::assertTrue($this->containsFamily($imageLegacy, 'image'));
        self::assertFalse($this->containsFamily($circularLegacyBeforeDom, 'image'));
        self::assertFalse($this->containsFamily($circularLegacyBeforeDom, 'fill-image'));
    }

    public function testSetElementKeepsSemanticAndLegacyGraphicChannelsCompatible(): void
    {
        $template = $this->template();
        $image = new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);
        $semantic = iterator_to_array($image->getOwnStyleRequirements(), false)[0];

        $template->setElement('test1', $image);

        $definitions = $template->semanticDefinitionsForTest();
        self::assertArrayHasKey($this->semanticIdentity($semantic), $definitions);
        self::assertArrayHasKey($semantic->name(), $template->imageStylesForTest());
        self::assertArrayHasKey($this->fillImageName(), $template->fillImagesForTest());
    }

    public function testCircularImageSaveRetainsLegacyRenderedStyleFillDeclarationAndResource(): void
    {
        $template = $this->template();
        $image = new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);
        $semantic = iterator_to_array($image->getOwnStyleRequirements(), false)[0];

        $template->setElement('test1', $image);
        $output = sys_get_temp_dir() . '/sr06c-preflight-' . bin2hex(random_bytes(6)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $styles = $this->entry($output, 'styles.xml');
        $content = $this->entry($output, 'content.xml');

        self::assertStringContainsString('style:name="' . $semantic->name() . '"', $styles);
        self::assertStringContainsString('draw:fill-image-name="' . $this->fillImageName() . '"', $styles);
        self::assertStringContainsString('draw:name="' . $this->fillImageName() . '"', $styles);
        self::assertStringContainsString('draw:style-name="' . $semantic->name() . '"', $content);
        self::assertTrue($this->contains($output, 'Pictures/' . basename($this->imagePath())));
        self::assertStringContainsString(
            'Pictures/' . basename($this->imagePath()),
            $this->entry($output, 'META-INF/manifest.xml')
        );
    }

    public function testLoadResetsSemanticAndLegacyGraphicDocumentState(): void
    {
        $template = $this->template();
        $image = new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);

        $template->setElement('test1', $image);
        self::assertNotSame([], $template->semanticDefinitionsForTest());
        self::assertNotSame([], $template->imageStylesForTest());
        self::assertNotSame([], $template->fillImagesForTest());

        $template->load();

        self::assertSame([], $template->semanticDefinitionsForTest());
        self::assertSame([], $template->imageStylesForTest());
        self::assertSame([], $template->fillImagesForTest());
    }

    /**
     * @param list<array{family: string, name: string, definition: array<string, mixed>}> $requirements
     */
    private function containsFamily(array $requirements, string $family): bool
    {
        foreach ($requirements as $requirement) {
            if ($requirement['family'] === $family) {
                return true;
            }
        }

        return false;
    }

    private function semanticIdentity(StyleRequirement $requirement): string
    {
        return implode("\0", [
            $requirement->family(),
            $requirement->name(),
            $requirement->scope() ?? '',
            $requirement->documentPart() ?? '',
        ]);
    }

    private function template(): OdtTemplate
    {
        $template = new class($this->templatePath('sample_textfeld.odt')) extends OdtTemplate {
            /** @return array<string, StyleRequirement> */
            public function semanticDefinitionsForTest(): array
            {
                return $this->documentContext()->styleContext()->semanticDefinitions();
            }

            /** @return array<string, array<string, mixed>> */
            public function imageStylesForTest(): array
            {
                return $this->documentContext()->styleContext()->imageStyles();
            }

            /** @return array<string, array<string, mixed>> */
            public function fillImagesForTest(): array
            {
                return $this->documentContext()->styleContext()->fillImages();
            }
        };
        $this->templates[] = $template;

        return $template;
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

    private function fillImageName(): string
    {
        return 'cv_photo_' . pathinfo($this->imagePath(), PATHINFO_FILENAME);
    }
}
