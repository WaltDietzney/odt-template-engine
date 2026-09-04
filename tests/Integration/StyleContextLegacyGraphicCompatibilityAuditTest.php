<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes the remaining graphic compatibility channels for SR-06F.
 *
 * These tests record current ownership and lifecycle behavior. They do not
 * approve the coexistence of semantic and legacy channels as the final model.
 */
final class StyleContextLegacyGraphicCompatibilityAuditTest extends TestCase
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
    public function testNormalSetElementRetainsSemanticAndLegacyGraphicChannels(): void
    {
        $frameTemplate = $this->template();
        $frame = new DrawTextBox('AuditFrame', ['background-color' => '#d4a100']);
        $frameTemplate->setElement('test1', $frame);

        self::assertNotSame([], $frameTemplate->semanticDefinitionsForAudit());
        self::assertNotSame([], $frameTemplate->frameStylesForAudit());

        $imageTemplate = $this->template();
        $image = new ImageElement($this->imagePath());
        $imageTemplate->setElement('test1', $image);

        self::assertSame([], $imageTemplate->semanticDefinitionsForAudit());
        self::assertNotSame([], $imageTemplate->imageStylesForAudit());

        $circularTemplate = $this->template();
        $circular = new CircularImageElement($this->imagePath());
        $circularTemplate->setElement('test1', $circular);
        $fillName = 'cv_photo_' . pathinfo($this->imagePath(), PATHINFO_FILENAME);

        self::assertNotSame([], $circularTemplate->semanticDefinitionsForAudit());
        self::assertSame([$fillName], $circularTemplate->semanticFillImageNamesForAudit());
        self::assertArrayHasKey($fillName, $circularTemplate->fillImagesForAudit());

        // The same normal occurrence is visible in both semantic and legacy
        // state today. Physical de-duplication is a later ownership concern.
        self::assertArrayNotHasKey($fillName, StyleMapper::getRegisteredFillImages());
        self::assertArrayHasKey(
            (string) array_key_first($circular->getImageStyleRequirements()),
            $circularTemplate->imageStylesForAudit()
        );
    }

    #[RunInSeparateProcess]
    public function testNormalCircularSetElementPhysicallyWritesOneFillDeclarationDespiteTwoRegistrations(): void
    {
        $template = $this->template();
        $image = new CircularImageElement($this->imagePath());
        $template->setElement('test1', $image);

        $output = sys_get_temp_dir() . '/sr06f-normal-circular-' . bin2hex(random_bytes(6)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $styles = $this->entry($output, 'styles.xml');
        $fillName = 'cv_photo_' . pathinfo($this->imagePath(), PATHINFO_FILENAME);

        self::assertArrayHasKey($fillName, $template->fillImagesForAudit());
        self::assertArrayNotHasKey($fillName, $template->legacyFillImagesForAudit());
        self::assertSame(1, substr_count($styles, 'draw:name="' . $fillName . '"'));
    }

    #[RunInSeparateProcess]
    public function testAssignRenderUsesLegacyRegistrationAndFinalizationWithoutSemanticCollection(): void
    {
        $template = $this->template();
        $image = new CircularImageElement($this->imagePath());
        $template->assign(['test1' => $image]);
        $template->render();

        self::assertTrue($template->legacyStructuredValuesMaterializedForAudit());
        self::assertSame([], $template->semanticDefinitionsForAudit());
        self::assertSame([], $template->imageStylesForAudit());
        self::assertSame([], $template->fillImagesForAudit());
        self::assertArrayHasKey(
            (string) array_key_first($image->getImageStyleRequirements()),
            StyleMapper::getRegisteredImageStyles()
        );
        self::assertArrayHasKey(
            'cv_photo_' . pathinfo($this->imagePath(), PATHINFO_FILENAME),
            StyleMapper::getRegisteredFillImages()
        );

        $output = sys_get_temp_dir() . '/sr06f-legacy-circular-' . bin2hex(random_bytes(6)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);
        $styles = $this->entry($output, 'styles.xml');

        self::assertStringContainsString('draw:fill-image', $styles);
    }

    private function template(): OdtTemplate
    {
        $template = new class($this->templatePath('sample_textfeld.odt')) extends OdtTemplate {
            public function semanticDefinitionsForAudit(): array
            {
                return $this->documentContext()->styleContext()->semanticDefinitions();
            }

            public function frameStylesForAudit(): array
            {
                return $this->documentContext()->styleContext()->frameStyles();
            }

            public function imageStylesForAudit(): array
            {
                return $this->documentContext()->styleContext()->imageStyles();
            }

            public function fillImagesForAudit(): array
            {
                return $this->documentContext()->styleContext()->fillImages();
            }

            public function legacyFillImagesForAudit(): array
            {
                return \OdtTemplateEngine\Utils\StyleMapper::getRegisteredFillImages();
            }

            public function semanticFillImageNamesForAudit(): array
            {
                return array_map(
                    static fn ($requirement): string => $requirement->name(),
                    $this->documentContext()->fillImageRequirements()->requirements()
                );
            }

            public function legacyStructuredValuesMaterializedForAudit(): bool
            {
                $property = new \ReflectionProperty(OdtTemplate::class, 'legacyStructuredValuesMaterialized');

                return $property->getValue($this);
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

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}
