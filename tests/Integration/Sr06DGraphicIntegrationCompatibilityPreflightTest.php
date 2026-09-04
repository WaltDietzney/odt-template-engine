<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class Sr06DGraphicIntegrationCompatibilityPreflightTest extends TestCase
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

    public function testSavedDrawTextBoxUsesSemanticGraphicAuthorityWhenCompatibilityCarrierIsNotNeeded(): void
    {
        $template = $this->template();
        $box = new DrawTextBox('SemanticAuthorityBox', [
            'width' => '6cm',
            'height' => '2cm',
            'anchor' => 'as-char',
            'horizontal-pos' => 'right',
            'horizontal-rel' => 'paragraph',
            'background-color' => '#123456',
            'border' => '0.03cm solid #abcdef',
            'padding' => '0.1cm',
        ]);
        $semantic = iterator_to_array($box->getOwnStyleRequirements(), false)[0];
        $legacyName = (string) array_key_first($box->getOwnFrameStyleRequirements());
        self::assertNotSame($semantic->name(), $legacyName);

        $template->setElement('test1', $box);
        $output = $this->outputPath('semantic-box');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');

        self::assertStringContainsString('draw:style-name="' . $semantic->name() . '"', $content);
        self::assertStringNotContainsString('draw:style-name="' . $legacyName . '"', $content);
        self::assertSame(1, substr_count($styles, 'style:name="' . $semantic->name() . '"'));
        self::assertStringContainsString('style:name="' . $legacyName . '"', $styles);
        self::assertStringContainsString('draw:fill-color="#123456"', $styles);
        self::assertArrayHasKey($this->semanticIdentity($semantic), $template->semanticDefinitionsForTest());
        self::assertArrayHasKey($legacyName, $template->frameStylesForTest());
    }

    public function testSavedDrawTextBoxRetainsLegacyCarrierWhenUnmigratedLayoutPropertyIsRequired(): void
    {
        $template = $this->template();
        $box = new DrawTextBox('CompatibilityCarrierBox', [
            'width' => '6cm',
            'height' => '2cm',
            'background-color' => '#123456',
            'allow-overlap' => 'true',
        ]);
        $semantic = iterator_to_array($box->getOwnStyleRequirements(), false)[0];
        $legacyName = (string) array_key_first($box->getOwnFrameStyleRequirements());
        self::assertNotSame($semantic->name(), $legacyName);

        $template->setElement('test1', $box);
        $output = $this->outputPath('legacy-carrier-box');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');

        self::assertStringContainsString('draw:style-name="' . $legacyName . '"', $content);
        self::assertStringNotContainsString('draw:style-name="' . $semantic->name() . '"', $content);
        self::assertSame(1, substr_count($styles, 'style:name="' . $semantic->name() . '"'));
        self::assertSame(1, substr_count($styles, 'style:name="' . $legacyName . '"'));
        self::assertStringContainsString('loext:allow-overlap="true"', $styles);
        self::assertArrayHasKey($this->semanticIdentity($semantic), $template->semanticDefinitionsForTest());
        self::assertArrayHasKey($legacyName, $template->frameStylesForTest());
    }

    public function testSavedCircularImagePreservesSemanticGraphicStyleFillDependencyAndResourceBoundary(): void
    {
        $template = $this->template();
        $image = new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);
        $semantic = iterator_to_array($image->getOwnStyleRequirements(), false)[0];

        $template->setElement('test1', $image);
        $output = $this->outputPath('circular-image');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        $fillName = $this->fillImageName();

        self::assertStringContainsString('draw:style-name="' . $semantic->name() . '"', $content);
        self::assertSame(1, substr_count($styles, 'style:name="' . $semantic->name() . '"'));
        self::assertStringContainsString('draw:fill-image-name="' . $fillName . '"', $styles);
        self::assertStringContainsString('draw:name="' . $fillName . '"', $styles);
        self::assertArrayHasKey($fillName, $template->fillImagesForTest());
        self::assertTrue($this->contains($output, 'Pictures/' . basename($this->imagePath())));
        self::assertStringContainsString(
            'Pictures/' . basename($this->imagePath()),
            $this->entry($output, 'META-INF/manifest.xml')
        );
    }

    public function testNormalImageElementRemainsOnLegacyCompatibilityPathWithoutSemanticGraphicDefinition(): void
    {
        $template = $this->template();
        $image = new ImageElement($this->imagePath(), [
            'width' => '4cm',
            'height' => '2cm',
            'align' => 'right',
        ]);
        self::assertSame([], iterator_to_array($image->getOwnStyleRequirements(), false));
        $legacyName = (string) array_key_first($image->getImageStyleRequirements());

        $template->setElement('test1', $image);
        $output = $this->outputPath('normal-image');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');

        self::assertSame([], $template->semanticDefinitionsForTest());
        self::assertStringContainsString('draw:style-name="' . $legacyName . '"', $content);
        self::assertStringContainsString('style:name="' . $legacyName . '"', $styles);
        self::assertArrayHasKey($legacyName, $template->imageStylesForTest());
        self::assertTrue($this->contains($output, 'Pictures/' . basename($this->imagePath())));
        self::assertStringContainsString(
            'Pictures/' . basename($this->imagePath()),
            $this->entry($output, 'META-INF/manifest.xml')
        );
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
            public function frameStylesForTest(): array
            {
                return $this->documentContext()->styleContext()->frameStyles();
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

    private function semanticIdentity(StyleRequirement $requirement): string
    {
        return implode("\0", [
            $requirement->family(),
            $requirement->name(),
            $requirement->scope() ?? '',
            $requirement->documentPart() ?? '',
        ]);
    }

    private function outputPath(string $label): string
    {
        $output = sys_get_temp_dir() . '/sr06d-' . $label . '-' . bin2hex(random_bytes(6)) . '.odt';
        $this->outputs[] = $output;

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

    private function fillImageName(): string
    {
        return 'cv_photo_' . pathinfo($this->imagePath(), PATHINFO_FILENAME);
    }
}
