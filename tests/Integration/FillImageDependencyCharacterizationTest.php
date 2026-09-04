<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LogicException;
use OdtTemplateEngine\Document\StructuredResourceCollector;
use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Style\StyleContext;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes the current fill-image dependency lifecycle before SR-06E
 * migrates it to an explicit document-local semantic dependency path.
 */
final class FillImageDependencyCharacterizationTest extends TestCase
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

    public function testSemanticReferenceExistsBeforeLegacyFillImageRequirementAppears(): void
    {
        $image = $this->circularImage();
        $semantic = iterator_to_array($image->getOwnStyleRequirements(), false);

        self::assertCount(1, $semantic);
        self::assertSame(
            $this->fillImageName(),
            $semantic[0]->propertyGroups()['style:graphic-properties']['draw:fill-image-name'] ?? null
        );
        self::assertSame([], $image->getOwnFillImageRequirements());

        $dom = new DOMDocument('1.0', 'UTF-8');
        $image->toDomNode($dom);

        $legacy = $image->getOwnFillImageRequirements();
        self::assertArrayHasKey($this->fillImageName(), $legacy);
        self::assertSame($this->fillImageName(), $legacy[$this->fillImageName()]['name'] ?? null);
        self::assertSame($this->imagePath(), $legacy[$this->fillImageName()]['path'] ?? null);
        self::assertSame(basename($this->imagePath()), $legacy[$this->fillImageName()]['filename'] ?? null);
    }

    public function testPhysicalResourceDiscoveryIsIndependentOfLegacyFillImageMutation(): void
    {
        $image = $this->circularImage();
        self::assertSame([], $image->getOwnFillImageRequirements());

        $resources = iterator_to_array((new StructuredResourceCollector())->collect($image), false);

        self::assertCount(1, $resources);
        self::assertSame(basename($this->imagePath()), $resources[0]['id'] ?? null);
        self::assertSame($this->imagePath(), $resources[0]['path'] ?? null);
        self::assertSame([], $image->getOwnFillImageRequirements());
    }

    public function testLegacyDocumentLocalFillImageRegistrationIsIdempotentAndConflicting(): void
    {
        $context = new StyleContext();
        $definition = [
            'name' => 'CharacterizedFill',
            'path' => '/tmp/first.png',
            'filename' => 'first.png',
        ];

        $context->registerFillImage('CharacterizedFill', $definition);
        $context->registerFillImage('CharacterizedFill', $definition);

        self::assertSame(['CharacterizedFill' => $definition], $context->fillImages());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Fill-image declaration "CharacterizedFill" is already registered with a different definition.');

        $context->registerFillImage('CharacterizedFill', [
            'name' => 'CharacterizedFill',
            'path' => '/tmp/second.png',
            'filename' => 'second.png',
        ]);
    }

    public function testExistingTargetFillImageDeclarationRemainsAuthoritativeDuringCurrentFinalization(): void
    {
        $template = new class($this->templatePath('sample_textfeld.odt')) extends OdtTemplate {
            public function addFillImageDeclarationForTest(string $name, string $href): void
            {
                $dom = $this->documentContext()->stylesDom();
                $xpath = new DOMXPath($dom);
                $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
                $officeStyles = $xpath->query('//office:styles')->item(0);
                if (!$officeStyles instanceof DOMElement) {
                    self::fail('Expected office:styles in test template.');
                }

                $drawNamespace = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
                $xlinkNamespace = 'http://www.w3.org/1999/xlink';
                $fillImage = $dom->createElementNS($drawNamespace, 'draw:fill-image');
                $fillImage->setAttributeNS($drawNamespace, 'draw:name', $name);
                $fillImage->setAttributeNS($xlinkNamespace, 'xlink:href', $href);
                $fillImage->setAttributeNS($xlinkNamespace, 'xlink:type', 'simple');
                $fillImage->setAttributeNS($xlinkNamespace, 'xlink:show', 'embed');
                $fillImage->setAttributeNS($xlinkNamespace, 'xlink:actuate', 'onLoad');
                $officeStyles->insertBefore($fillImage, $officeStyles->firstChild);
            }
        };
        $this->templates[] = $template;
        $template->addFillImageDeclarationForTest($this->fillImageName(), 'Pictures/authored.png');

        $template->setElement('test1', $this->circularImage());
        $output = $this->outputPath('existing-authority');
        $template->save($output);

        $styles = $this->entry($output, 'styles.xml');
        self::assertSame(1, substr_count($styles, 'draw:name="' . $this->fillImageName() . '"'));
        self::assertStringContainsString('xlink:href="Pictures/authored.png"', $styles);
        self::assertStringNotContainsString(
            'xlink:href="Pictures/' . basename($this->imagePath()) . '"',
            $this->fillImageDeclarationXml($styles, $this->fillImageName())
        );
    }

    public function testSavedCircularImageKeepsDeclarationAndPhysicalResourceAsSeparateArtifacts(): void
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        $template->setElement('test1', $this->circularImage());
        $output = $this->outputPath('saved-artifacts');
        $template->save($output);

        $styles = $this->entry($output, 'styles.xml');
        $manifest = $this->entry($output, 'META-INF/manifest.xml');
        $picture = 'Pictures/' . basename($this->imagePath());

        self::assertStringContainsString('draw:name="' . $this->fillImageName() . '"', $styles);
        self::assertStringContainsString('xlink:href="' . $picture . '"', $styles);
        self::assertTrue($this->contains($output, $picture));
        self::assertStringContainsString($picture, $manifest);
    }

    private function circularImage(): CircularImageElement
    {
        return new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);
    }

    private function fillImageDeclarationXml(string $stylesXml, string $name): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($stylesXml));
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $nodes = $xpath->query('//draw:fill-image[@draw:name="' . $name . '"]');
        self::assertNotFalse($nodes);
        $node = $nodes->item(0);
        self::assertInstanceOf(DOMElement::class, $node);

        return $dom->saveXML($node) ?: '';
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

    private function outputPath(string $label): string
    {
        $path = sys_get_temp_dir() . '/sr06e1-' . $label . '-' . bin2hex(random_bytes(6)) . '.odt';
        $this->outputs[] = $path;
        return $path;
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
