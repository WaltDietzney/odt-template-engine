<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextCompositeMaterializationCharacterizationTest extends TestCase
{
    /** @var list<string> */
    private array $outputs = [];

    protected function tearDown(): void
    {
        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    /** @param callable(): array{0: OdtElement, 1: array<string, bool>, 2: bool} $factory */
    #[DataProvider('compositionProvider')]
    public function testCurrentNestedCompositionBehavior(
        string $case,
        callable $factory,
        bool $expectsAsset,
        bool $expectsManifest
    ): void {
        [$element, $expectedStyles, $expectsContentImage] = $factory();
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $element);
        $output = $this->save($template, $case);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        $manifest = $this->entry($output, 'META-INF/manifest.xml');
        self::assertTrue($this->isWellFormedXml($content), $case . ': content.xml');
        self::assertTrue($this->isWellFormedXml($styles), $case . ': styles.xml');

        if ($expectsContentImage) {
            self::assertStringContainsString('Pictures/WaltDietzney.png', $content, $case);
        } else {
            self::assertStringNotContainsString('Pictures/WaltDietzney.png', $content, $case);
        }
        foreach ($expectedStyles as $styleName => $expectsGraphicStyle) {
            $needle = 'style:name="' . $styleName . '"';
            if ($expectsGraphicStyle) {
                self::assertSame(1, substr_count($styles, $needle), $case . ': ' . $styleName);
            } else {
                self::assertStringNotContainsString($needle, $styles, $case . ': ' . $styleName);
            }
        }

        if ($expectsAsset) {
            self::assertTrue($this->contains($output, 'Pictures/WaltDietzney.png'), $case);
        } else {
            self::assertFalse($this->contains($output, 'Pictures/WaltDietzney.png'), $case);
        }

        if ($expectsManifest) {
            self::assertStringContainsString('Pictures/WaltDietzney.png', $manifest, $case);
        } else {
            self::assertStringNotContainsString('Pictures/WaltDietzney.png', $manifest, $case);
        }
    }

    public function testGraphicRequirementCollectionSilentlyKeepsLastDuplicateDefinition(): void
    {
        $first = new CharacterizationGraphicElement(['background' => 'first']);
        $second = new CharacterizationGraphicElement(['background' => 'second']);
        $composite = (new CharacterizationCompositeElement())
            ->addElement($first)
            ->addElement($second);

        self::assertSame(
            ['background' => 'second'],
            $composite->getFrameStyleRequirements()['same-name']
        );
    }

    public function testDrawTextBoxNestedParagraphTextStyleIsReferencedButNotCollected(): void
    {
        $paragraph = (new Paragraph())->addText('nested', ['bold' => true]);
        $box = (new DrawTextBox('TextBoxStyledParagraph', ['background-color' => '#d5f5e3']))
            ->addElement($paragraph);
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $box);
        $output = $this->save($template, 'textbox-styled-paragraph');

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        $textStyleName = (string) array_key_first($paragraph->getRequiredStyles());

        self::assertStringContainsString('text:style-name="' . $textStyleName . '"', $content);
        self::assertStringNotContainsString('style:name="' . $textStyleName . '"', $styles);
    }

    public function testParagraphCircularImageCurrentStyleAndResourcePropagation(): void
    {
        $image = new CircularImageElement(self::imagePath(), ['width' => '3cm', 'height' => '3cm']);
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', (new Paragraph())->addElement($image));
        $output = $this->save($template, 'paragraph-circular-image');

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        $manifest = $this->entry($output, 'META-INF/manifest.xml');
        $imageStyleName = (string) array_key_first($image->getImageStyleRequirements());
        $fillImageName = (string) array_key_first($image->getFillImageRequirements());

        self::assertStringContainsString('draw:custom-shape', $content);
        self::assertSame(1, substr_count($styles, 'style:name="' . $imageStyleName . '"'));
        self::assertSame(1, substr_count($styles, 'draw:name="' . $fillImageName . '"'));
        self::assertTrue($this->contains($output, 'Pictures/WaltDietzney.png'));
        self::assertStringContainsString('Pictures/WaltDietzney.png', $manifest);
    }

    public function testDrawTextBoxCircularImageCurrentStyleAndResourcePropagation(): void
    {
        $image = new CircularImageElement(self::imagePath(), ['width' => '3cm', 'height' => '3cm']);
        $box = (new DrawTextBox('TextBoxCircularImage', ['background-color' => '#d5f5e3']))
            ->addElement($image);
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $box);
        $output = $this->save($template, 'textbox-circular-image');

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        $manifest = $this->entry($output, 'META-INF/manifest.xml');
        $imageStyleName = (string) array_key_first($image->getImageStyleRequirements());
        $fillImageName = (string) array_key_first($image->getFillImageRequirements());

        self::assertStringContainsString('draw:custom-shape', $content);
        self::assertStringNotContainsString('style:name="' . $imageStyleName . '"', $styles);
        self::assertStringNotContainsString('draw:name="' . $fillImageName . '"', $styles);
        self::assertFalse($this->contains($output, 'Pictures/WaltDietzney.png'));
        self::assertStringNotContainsString('Pictures/WaltDietzney.png', $manifest);
    }

    /**
     * @return array<string, array{string, callable(): array{0: OdtElement, 1: array<string, bool>, 2: bool}, bool, bool}>
     */
    public static function compositionProvider(): array
    {
        $image = static fn (): ImageElement => new ImageElement(self::imagePath());

        return [
            'paragraph-image' => [
                'Paragraph -> ImageElement',
                static function () use ($image): array {
                    $imageElement = $image();
                    return [
                        (new Paragraph())->addElement($imageElement),
                        [self::materializedImageStyleName($imageElement) => true],
                        true,
                    ];
                },
                true,
                true,
            ],
            'paragraph-textbox' => [
                'Paragraph -> DrawTextBox',
                static function (): array {
                    $box = new DrawTextBox('ParagraphBox', ['background-color' => '#d5f5e3']);
                    return [(new Paragraph())->addElement($box), [self::frameStyleName($box) => true], false];
                },
                false,
                false,
            ],
            'richtext-paragraph-image' => [
                'RichText -> Paragraph -> ImageElement',
                static function () use ($image): array {
                    $imageElement = $image();
                    return [
                        (new RichText())->addParagraph((new Paragraph())->addElement($imageElement)),
                        [self::materializedImageStyleName($imageElement) => false],
                        true,
                    ];
                },
                true,
                true,
            ],
            'richtext-direct-image' => [
                'RichText -> direct ImageElement',
                static function () use ($image): array {
                    $imageElement = $image();
                    return [(new RichText())->addElement($imageElement), [self::materializedImageStyleName($imageElement) => false], true];
                },
                false,
                false,
            ],
            'richtext-paragraph-textbox' => [
                'RichText -> Paragraph -> DrawTextBox',
                static function (): array {
                    $box = new DrawTextBox('RichTextBox', ['background-color' => '#d5f5e3']);
                    return [(new RichText())->addParagraph((new Paragraph())->addElement($box)), [self::frameStyleName($box) => false], false];
                },
                false,
                false,
            ],
            'textbox-paragraph' => [
                'DrawTextBox -> styled Paragraph',
                static function (): array {
                    $box = (new DrawTextBox('TextBoxParagraph', ['background-color' => '#d5f5e3']))
                        ->addElement((new Paragraph())->addText('nested', ['bold' => true]));
                    return [$box, [self::frameStyleName($box) => true], false];
                },
                false,
                false,
            ],
            'textbox-image' => [
                'DrawTextBox -> ImageElement',
                static function () use ($image): array {
                    $imageElement = $image();
                    $box = (new DrawTextBox('TextBoxImage', ['background-color' => '#d5f5e3']))
                        ->addElement($imageElement);
                    return [$box, [self::frameStyleName($box) => true, self::materializedImageStyleName($imageElement) => false], true];
                },
                false,
                false,
            ],
            'textbox-richtext-image' => [
                'DrawTextBox -> RichText -> ImageElement',
                static function () use ($image): array {
                    $imageElement = $image();
                    $box = (new DrawTextBox('TextBoxRichImage', ['background-color' => '#d5f5e3']))
                        ->addElement((new RichText())->addImage($imageElement));
                    return [$box, [self::frameStyleName($box) => true, self::materializedImageStyleName($imageElement) => false], true];
                },
                false,
                false,
            ],
            'list-paragraph-image' => [
                'ListElement -> Paragraph -> ImageElement',
                static function () use ($image): array {
                    $imageElement = $image();
                    return [
                        (new ListElement())->addItem((new Paragraph())->addElement($imageElement)),
                        [self::materializedImageStyleName($imageElement) => false],
                        true,
                    ];
                },
                true,
                true,
            ],
            'nested-list-paragraph-image' => [
                'ListElement -> nested ListElement -> Paragraph -> ImageElement',
                static function () use ($image): array {
                    $imageElement = $image();
                    $inner = (new ListElement())->addItem((new Paragraph())->addElement($imageElement));
                    return [(new ListElement())->addSubList($inner), [self::materializedImageStyleName($imageElement) => false], true];
                },
                true,
                true,
            ],
            'table-cell-paragraph-image' => [
                'RichTable -> RichTableCell -> Paragraph -> ImageElement',
                static function () use ($image): array {
                    $imageElement = $image();
                    return [
                        (new RichTable())->addRow([
                            new RichTableCell((new Paragraph())->addElement($imageElement)),
                        ]),
                        [self::materializedImageStyleName($imageElement) => false],
                        true,
                    ];
                },
                false,
                false,
            ],
            'table-cell-richtext-image' => [
                'RichTable -> RichTableCell -> RichText -> ImageElement',
                static function () use ($image): array {
                    $imageElement = $image();
                    return [
                        (new RichTable())->addRow([
                            new RichTableCell((new RichText())->addImage($imageElement)),
                        ]),
                        [self::materializedImageStyleName($imageElement) => false],
                        true,
                    ];
                },
                false,
                false,
            ],
        ];
    }

    private static function frameStyleName(DrawTextBox $box): string
    {
        return (string) array_key_first($box->getFrameStyleRequirements());
    }

    private static function materializedImageStyleName(ImageElement $image): string
    {
        return (string) array_key_first($image->getImageStyleRequirements());
    }

    private function save(OdtTemplate $template, string $label): string
    {
        $output = sys_get_temp_dir() . '/odt-d5a-' . md5($label . microtime(true)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);
        $template->cleanup();
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

    private function isWellFormedXml(string $xml): bool
    {
        $dom = new DOMDocument();
        return $dom->loadXML($xml) !== false;
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private static function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}

final class CharacterizationGraphicElement extends OdtElement
{
    public function __construct(private array $definition)
    {
    }

    public function toDomNode(\DOMDocument $dom): \DOMNode
    {
        return $dom->createElement('text:span');
    }

    public function registerStyles(): void
    {
    }

    public function getFrameStyleRequirements(): array
    {
        return ['same-name' => $this->definition];
    }
}

final class CharacterizationCompositeElement extends OdtElement
{
    public function toDomNode(\DOMDocument $dom): \DOMNode
    {
        return $dom->createElement('text:p');
    }

    public function registerStyles(): void
    {
    }
}
