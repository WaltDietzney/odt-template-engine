<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Document\FillImageRequirementCollector;
use OdtTemplateEngine\Document\StructuredResourceCollector;
use OdtTemplateEngine\Document\StyleRequirementCollector;
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
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes the current D5F setElement lifecycle without changing it.
 *
 * These assertions distinguish semantic requirements, deterministic render
 * state, legacy compatibility state, and physical package resources.
 */
final class D5FLifecycleCharacterizationTest extends TestCase
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

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, string>}> 
     */
    public static function imageOptionProvider(): array
    {
        return [
            'no alignment' => [
                [],
                [
                    'text:anchor-type' => 'paragraph',
                    'svg:width' => '5cm',
                    'svg:height' => '3cm',
                ],
            ],
            'left alignment' => [
                ['align' => 'left'],
                [
                    'text:anchor-type' => 'paragraph',
                    'svg:width' => '5cm',
                    'svg:height' => '3cm',
                    'style:wrap' => 'right',
                    'style:horizontal-pos' => 'left',
                    'style:horizontal-rel' => 'paragraph',
                ],
            ],
            'right alignment' => [
                ['align' => 'right'],
                [
                    'text:anchor-type' => 'paragraph',
                    'svg:width' => '5cm',
                    'svg:height' => '3cm',
                    'style:wrap' => 'left',
                    'style:horizontal-pos' => 'right',
                    'style:horizontal-rel' => 'paragraph',
                ],
            ],
            'center alignment' => [
                ['align' => 'center'],
                [
                    'text:anchor-type' => 'paragraph',
                    'svg:width' => '5cm',
                    'svg:height' => '3cm',
                    'style:wrap' => 'none',
                    'style:horizontal-pos' => 'center',
                    'style:horizontal-rel' => 'paragraph',
                ],
            ],
            'absolute alignment' => [
                ['align' => 'absolute'],
                [
                    'text:anchor-type' => 'paragraph',
                    'svg:width' => '5cm',
                    'svg:height' => '3cm',
                    'style:wrap' => 'none',
                    'style:horizontal-pos' => 'from-left',
                    'style:horizontal-rel' => 'page-content',
                ],
            ],
            'explicit wrap' => [
                ['wrap' => 'left'],
                [
                    'text:anchor-type' => 'paragraph',
                    'svg:width' => '5cm',
                    'svg:height' => '3cm',
                    'style:wrap' => 'left',
                ],
            ],
            'explicit horizontal placement' => [
                ['horizontal-pos' => 'right', 'horizontal-rel' => 'paragraph'],
                [
                    'text:anchor-type' => 'paragraph',
                    'svg:width' => '5cm',
                    'svg:height' => '3cm',
                    'style:horizontal-pos' => 'right',
                    'style:horizontal-rel' => 'paragraph',
                ],
            ],
            'explicit vertical placement' => [
                ['vertical-pos' => 'top', 'vertical-rel' => 'page'],
                [
                    'text:anchor-type' => 'paragraph',
                    'svg:width' => '5cm',
                    'svg:height' => '3cm',
                    'style:vertical-pos' => 'top',
                    'style:vertical-rel' => 'page',
                ],
            ],
            'anchor and unreachable svg coordinates' => [
                ['anchor' => 'as-char', 'svg:x' => '1cm', 'svg:y' => '2cm'],
                [
                    'text:anchor-type' => 'as-char',
                    'svg:width' => '5cm',
                    'svg:height' => '3cm',
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $expectedFrame
     */
    #[DataProvider('imageOptionProvider')]
    public function testImageOptionsAndLegacyRequirementsBeforeAndAfterDomMaterialization(
        array $input,
        array $expectedFrame
    ): void {
        $image = new ImageElement($this->imagePath(), $input);
        $optionsBefore = $image->getImageOptions();
        $legacyBefore = $image->getOwnImageStyleRequirements();
        $styleNameBefore = (string) array_key_first($legacyBefore);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $image->toDomNode($dom);

        $optionsAfter = $image->getImageOptions();
        $legacyAfter = $image->getOwnImageStyleRequirements();
        $styleNameAfter = (string) array_key_first($legacyAfter);

        self::assertSame($expectedFrame, $this->selectedAttributes($frame, array_keys($expectedFrame)));
        self::assertSame($styleNameBefore, $styleNameAfter);
        self::assertNotSame($optionsBefore, $optionsAfter);
        self::assertNotSame($legacyBefore, $legacyAfter);
        self::assertSame($optionsAfter, $legacyAfter[$styleNameAfter]);

        $expectedOptions = $optionsBefore + [
            'style:wrap' => $expectedFrame['style:wrap'] ?? '',
            'style:horizontal-pos' => $expectedFrame['style:horizontal-pos'] ?? '',
            'style:horizontal-rel' => $expectedFrame['style:horizontal-rel'] ?? '',
        ];
        if (isset($expectedFrame['style:vertical-pos'])) {
            $expectedOptions['style:vertical-pos'] = $expectedFrame['style:vertical-pos'];
            $expectedOptions['style:vertical-rel'] = $expectedFrame['style:vertical-rel'];
        }

        self::assertSame($expectedOptions, $optionsAfter);
    }

    public function testRepeatedImageMaterializationKeepsTheResolvedStateStable(): void
    {
        $image = new ImageElement($this->imagePath(), ['align' => 'right']);
        $dom = new DOMDocument('1.0', 'UTF-8');

        $image->toDomNode($dom);
        $first = $image->getImageOptions();
        $image->toDomNode($dom);
        $second = $image->getImageOptions();

        self::assertSame($first, $second);
    }

    public function testRepresentativeCollectorsAreStableSemanticallyBeforeAndAfterDomMaterialization(): void
    {
        foreach ($this->representativeElements() as $label => $element) {
            $before = $this->collectorSnapshot($element);
            $dom = $label === 'RichTable'
                ? $this->contentDom()
                : new DOMDocument('1.0', 'UTF-8');
            $element->toDomNode($dom);
            $after = $this->collectorSnapshot($element);

            self::assertSame($before['semantic'], $after['semantic'], $label . ': semantic collector changed');
            self::assertSame($before['resources'], $after['resources'], $label . ': resource collector changed');
            self::assertSame($before['fillImage'], $after['fillImage'], $label . ': fill-image collector changed');

            if (in_array($label, ['ImageElement', 'CircularImageElement'], true)) {
                self::assertNotSame($before['legacy'], $after['legacy'], $label . ': expected legacy mutation was absent');
            } else {
                self::assertSame($before['legacy'], $after['legacy'], $label . ': unexpected legacy mutation');
            }
        }
    }

    public function testImageSemanticCollectorRemainsEmptyAcrossMaterialization(): void
    {
        $image = new ImageElement($this->imagePath(), ['align' => 'right']);
        $collector = new StyleRequirementCollector();

        $before = iterator_to_array($collector->collectSemantic($image), false);
        $image->toDomNode(new DOMDocument('1.0', 'UTF-8'));
        $after = iterator_to_array($collector->collectSemantic($image), false);

        self::assertSame([], $before);
        self::assertSame([], $after);
    }

    public function testNestedImageResourceDiscoveryIsCompleteBeforeDomMaterialization(): void
    {
        $image = new ImageElement($this->imagePath(), ['width' => '2cm']);
        $root = (new RichText())->addImage($image);
        $collector = new StructuredResourceCollector();

        $before = iterator_to_array($collector->collect($root), false);
        $root->toDomNode(new DOMDocument('1.0', 'UTF-8'));
        $after = iterator_to_array($collector->collect($root), false);

        self::assertSame($before, $after);
        self::assertSame([
            ['id' => 'WaltDietzney.png', 'path' => $this->imagePath()],
        ], $before);
    }

    public function testCircularFillImageDependencyIsCompleteBeforeDomMaterialization(): void
    {
        $image = new CircularImageElement($this->imagePath());
        $collector = new FillImageRequirementCollector();

        $before = $this->fillImageSnapshot($collector->collect($image));
        $image->toDomNode(new DOMDocument('1.0', 'UTF-8'));
        $after = $this->fillImageSnapshot($collector->collect($image));

        self::assertSame($before, $after);
        self::assertSame([
            [
                'part' => 'styles.xml',
                'name' => 'cv_photo_WaltDietzney',
                'href' => 'Pictures/WaltDietzney.png',
            ],
        ], $before);
    }

    #[RunInSeparateProcess]
    public function testSetElementSaveAndRepeatedSavePreserveResourcesWithoutCrossDocumentLeak(): void
    {
        $imageA = new ImageElement($this->imagePath(), ['width' => '2cm']);
        $imageB = new ImageElement($this->imagePath(), ['width' => '7cm']);
        $templateA = $this->newTemplate();
        $templateB = $this->newTemplate();

        $templateA->setElement('test1', $imageA);
        $templateB->setElement('test1', $imageB);

        $firstA = $this->save($templateA, 'a-first');
        $secondA = $this->save($templateA, 'a-second');
        $outputB = $this->save($templateB, 'b');

        $contentA = $this->zipEntry($firstA, 'content.xml');
        $contentA2 = $this->zipEntry($secondA, 'content.xml');
        $contentB = $this->zipEntry($outputB, 'content.xml');
        self::assertStringContainsString('Pictures/WaltDietzney.png', $contentA);
        self::assertStringContainsString('Pictures/WaltDietzney.png', $contentB);
        self::assertSame(1, substr_count($contentA, 'draw:image'));
        self::assertSame(1, substr_count($contentA2, 'draw:image'));
        self::assertSame(1, substr_count($contentB, 'draw:image'));
        self::assertStringContainsString('draw:style-name="' . $imageB->getImageOptions()['style-name'] . '"', $contentB);
        self::assertStringNotContainsString('draw:style-name="' . $imageA->getImageOptions()['style-name'] . '"', $contentB);
        self::assertTrue($this->archiveContains($outputB, 'Pictures/WaltDietzney.png'));
    }

    /** @return array<string, OdtElement> */
    private function representativeElements(): array
    {
        $table = new RichTable();
        $table->setColumnWidths(['2cm', '4cm']);
        $table->addRow([
            new RichTableCell('A', ['background' => '#ddeeff']),
            'B',
        ], ['min-row-height' => '1cm']);

        $ratioTable = new RichTable();
        $ratioTable->setColumnWidthRatios([2, 1, 1]);
        $ratioTable->addRow(['A', 'B', 'C']);

        return [
            'Paragraph' => (new Paragraph())->addText('styled', ['bold' => true]),
            'RichText' => (new RichText())->addParagraph(
                (new Paragraph())->addText('nested', ['color' => '#123456'])
            ),
            'ListElement' => (new ListElement())->addItem(
                (new Paragraph())->addText('item', ['italic' => true])
            ),
            'ImageElement' => new ImageElement($this->imagePath(), ['align' => 'right']),
            'CircularImageElement' => new CircularImageElement($this->imagePath()),
            'DrawTextBox' => new DrawTextBox('D5FBox', ['background-color' => '#ddeeff']),
            'RichTable' => $table,
            'RichTableRatio' => $ratioTable,
        ];
    }

    /** @return array{semantic: array, legacy: array, resources: array, fillImage: array} */
    private function collectorSnapshot(OdtElement $element): array
    {
        $styleCollector = new StyleRequirementCollector();
        $semantic = [];
        foreach ($styleCollector->collectSemantic($element) as $requirement) {
            $semantic[] = [
                'kind' => $requirement->kind(),
                'scope' => $requirement->scope(),
                'family' => $requirement->family(),
                'part' => $requirement->documentPart(),
                'name' => $requirement->name(),
                'parent' => $requirement->parentStyleName(),
                'groups' => $requirement->propertyGroups(),
            ];
        }

        $fillCollector = new FillImageRequirementCollector();
        return [
            'semantic' => $semantic,
            'legacy' => iterator_to_array($styleCollector->collect($element), false),
            'resources' => iterator_to_array((new StructuredResourceCollector())->collect($element), false),
            'fillImage' => $this->fillImageSnapshot($fillCollector->collect($element)),
        ];
    }

    /** @param iterable<\OdtTemplateEngine\Document\FillImageRequirement> $requirements */
    private function fillImageSnapshot(iterable $requirements): array
    {
        $snapshot = [];
        foreach ($requirements as $requirement) {
            $snapshot[] = [
                'part' => $requirement->documentPart(),
                'name' => $requirement->name(),
                'href' => $requirement->href(),
            ];
        }
        return $snapshot;
    }

    /** @param array<string> $attributes */
    private function selectedAttributes(\DOMNode $node, array $attributes): array
    {
        $selected = [];
        foreach ($attributes as $attribute) {
            if ($node instanceof \DOMElement && $node->hasAttribute($attribute)) {
                $selected[$attribute] = $node->getAttribute($attribute);
            }
        }
        return $selected;
    }

    private function newTemplate(): OdtTemplate
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        return $template;
    }

    private function contentDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElement('office:document-content');
        $root->appendChild($dom->createElement('office:automatic-styles'));
        $dom->appendChild($root);
        return $dom;
    }

    private function save(OdtTemplate $template, string $label): string
    {
        $output = sys_get_temp_dir() . '/d5f-b-' . $label . '-' . bin2hex(random_bytes(5)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);
        return $output;
    }

    private function zipEntry(string $path, string $entry): string
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

    private function archiveContains(string $path, string $entry): bool
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            return $zip->locateName($entry) !== false;
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
