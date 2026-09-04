<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use OdtTemplateEngine\Document\FillImageRequirement;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class FillImageSemanticIndependencePreflightTest extends TestCase
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

    public function testSemanticOnlyProducerMaterializesWithoutLegacyFillImageState(): void
    {
        $template = $this->template();
        $element = $this->semanticOnlyElement();

        self::assertSame([], $element->getOwnFillImageRequirements());
        self::assertSame([], $template->legacyFillImageNamesForTest());

        $template->setElement('test1', $element);

        self::assertSame([], $element->getOwnFillImageRequirements());
        self::assertSame([], $template->legacyFillImageNamesForTest());
        self::assertSame([$this->fillImageName()], $template->semanticFillImageNamesForTest());
        self::assertTrue($template->hasFillImageDeclarationForTest($this->fillImageName()));
        self::assertTrue($template->hasGraphicStyleForTest(SemanticOnlyFillImageElement::STYLE_NAME));
    }

    public function testSemanticOnlyProducerSavesDeclarationStyleResourceAndManifest(): void
    {
        $template = $this->template();
        $element = $this->semanticOnlyElement();

        $template->setElement('test1', $element);
        $output = $this->outputPath('semantic-only');
        $template->save($output);

        $styles = $this->entry($output, 'styles.xml');
        $manifest = $this->entry($output, 'META-INF/manifest.xml');
        $picture = 'Pictures/' . basename($this->imagePath());

        self::assertStringContainsString('draw:name="' . $this->fillImageName() . '"', $styles);
        self::assertStringContainsString('xlink:href="' . $picture . '"', $styles);
        self::assertStringContainsString('style:name="' . SemanticOnlyFillImageElement::STYLE_NAME . '"', $styles);
        self::assertStringContainsString('draw:fill-image-name="' . $this->fillImageName() . '"', $styles);
        self::assertTrue($this->contains($output, $picture));
        self::assertStringContainsString($picture, $manifest);
        self::assertSame([], $element->getOwnFillImageRequirements());
        self::assertSame([], $template->legacyFillImageNamesForTest());
    }

    public function testCircularImageCompatibilityStateCanCoexistWithoutDuplicateDeclaration(): void
    {
        $template = $this->template();
        $element = new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);

        $template->setElement('test1', $element);

        self::assertSame([$this->fillImageName()], $template->semanticFillImageNamesForTest());
        self::assertSame([$this->fillImageName()], $template->legacyFillImageNamesForTest());
        self::assertSame(1, $template->fillImageDeclarationCountForTest($this->fillImageName()));
    }

    private function template(): OdtTemplate
    {
        $template = new class($this->templatePath('sample_textfeld.odt')) extends OdtTemplate {
            /** @return list<string> */
            public function semanticFillImageNamesForTest(): array
            {
                return array_map(
                    static fn (FillImageRequirement $requirement): string => $requirement->name(),
                    $this->documentContext()->fillImageRequirements()->requirements()
                );
            }

            /** @return list<string> */
            public function legacyFillImageNamesForTest(): array
            {
                return array_keys($this->documentContext()->styleContext()->fillImages());
            }

            public function hasFillImageDeclarationForTest(string $name): bool
            {
                return $this->fillImageDeclarationCountForTest($name) === 1;
            }

            public function fillImageDeclarationCountForTest(string $name): int
            {
                $xpath = $this->stylesXPathForTest();

                return $xpath->query('//draw:fill-image[@draw:name="' . $name . '"]')->length;
            }

            public function hasGraphicStyleForTest(string $name): bool
            {
                $xpath = $this->stylesXPathForTest();

                return $xpath->query(
                    '//style:style[@style:name="' . $name . '" and @style:family="graphic"]'
                )->length === 1;
            }

            private function stylesXPathForTest(): DOMXPath
            {
                $xpath = new DOMXPath($this->documentContext()->stylesDom());
                $xpath->registerNamespace(
                    'draw',
                    'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0'
                );
                $xpath->registerNamespace(
                    'style',
                    'urn:oasis:names:tc:opendocument:xmlns:style:1.0'
                );

                return $xpath;
            }
        };
        $this->templates[] = $template;

        return $template;
    }

    private function semanticOnlyElement(): SemanticOnlyFillImageElement
    {
        return new SemanticOnlyFillImageElement(
            $this->imagePath(),
            $this->fillImageName()
        );
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
        return 'sr06e5_semantic_' . pathinfo($this->imagePath(), PATHINFO_FILENAME);
    }

    private function outputPath(string $label): string
    {
        $path = sys_get_temp_dir() . '/sr06e5-' . $label . '-' . bin2hex(random_bytes(6)) . '.odt';
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
}

final class SemanticOnlyFillImageElement extends OdtElement
{
    public const STYLE_NAME = 'Sr06E5SemanticOnlyGraphic';

    public function __construct(
        private readonly string $imagePath,
        private readonly string $fillImageName
    ) {
    }

    /** @return iterable<int, StyleRequirement> */
    public function getOwnStyleRequirements(): iterable
    {
        return [new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'graphic',
            StyleRequirement::PART_STYLES,
            self::STYLE_NAME,
            'Frame',
            [
                'style:graphic-properties' => [
                    'draw:fill' => 'bitmap',
                    'draw:fill-image-name' => $this->fillImageName,
                    'draw:fill-image-width' => '100%',
                    'draw:fill-image-height' => '100%',
                    'style:repeat' => 'stretch',
                    'draw:stroke' => 'none',
                ],
            ]
        )];
    }

    /** @return iterable<int, FillImageRequirement> */
    public function getOwnFillImageDependencies(): iterable
    {
        return [new FillImageRequirement(
            FillImageRequirement::PART_STYLES,
            $this->fillImageName,
            'Pictures/' . basename($this->imagePath)
        )];
    }

    /** @return array<int, array<string, mixed>> */
    public function getOwnImageAssets(): array
    {
        return [[
            'id' => basename($this->imagePath),
            'path' => $this->imagePath,
        ]];
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $shape = $dom->createElement('draw:custom-shape');
        $shape->setAttribute('draw:style-name', self::STYLE_NAME);
        $shape->setAttribute('text:anchor-type', 'as-char');
        $shape->setAttribute('svg:width', '3cm');
        $shape->setAttribute('svg:height', '3cm');

        return $shape;
    }
}
