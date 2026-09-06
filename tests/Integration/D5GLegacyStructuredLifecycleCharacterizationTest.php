<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Document\FillImageRequirementCollector;
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
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use ZipArchive;

/**
 * Characterizes the public assign()/render() lifecycle for structured values.
 *
 * The assertions intentionally describe observed compatibility behavior. They
 * do not make the legacy lifecycle an alternative semantic ownership model.
 */
final class D5GLegacyStructuredLifecycleCharacterizationTest extends TestCase
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

    /**
     * @return array<string, array{0: OdtElement, 1: string}>
     */
    public static function legacyProducerProvider(): array
    {
        $root = dirname(__DIR__, 2);
        $image = $root . '/assets/WaltDietzney.png';

        $table = (new RichTable())
            ->setTableName('LegacyTable')
            ->addRow([
                (new RichTableCell('A', ['background' => '#ddeeff']))->setColspan(2),
                'B',
            ], ['min-row-height' => '1cm']);
        $table->setColumnWidths(['2cm', '4cm']);

        return [
            'paragraph' => [
                (new Paragraph())->addText('Legacy paragraph', ['bold' => true]),
                'Legacy paragraph',
            ],
            'rich text' => [
                (new RichText())->addParagraph(
                    (new Paragraph())->addText('Legacy rich text', ['italic' => true])
                ),
                'Legacy rich text',
            ],
            'list' => [
                (new ListElement())->addItem((new Paragraph())->addText('Legacy list item')),
                'Legacy list item',
            ],
            'image' => [
                new ImageElement($image, ['width' => '3cm', 'anchor' => 'as-char']),
                'Pictures/WaltDietzney.png',
            ],
            'circular image' => [
                new CircularImageElement($image),
                'draw:custom-shape',
            ],
            'draw text box' => [
                new DrawTextBox('Legacy box', ['background-color' => '#ddeeff']),
                'Legacy box',
            ],
            'rich table' => [$table, 'LegacyTable'],
        ];
    }

    /**
     * @param OdtElement $element
     * @param string $expected
     * @dataProvider legacyProducerProvider
     */
    public function testAssignRenderSaveProducesObservableLegacyStructuredOutput(
        OdtElement $element,
        string $expected
    ): void {
        $template = $this->legacyTemplate($element);
        $output = $this->save($template, 'producer');

        self::assertStringContainsString($expected, $this->entry($output, 'content.xml'));
        self::assertNotSame('', $this->entry($output, 'styles.xml'));
        self::assertNotSame('', $this->entry($output, 'META-INF/manifest.xml'));
    }

    #[RunInSeparateProcess]
    public function testLegacyImageStateIsStableAcrossRepeatedRenderAndSave(): void
    {
        $image = new ImageElement($this->imagePath(), [
            'align' => 'right',
            'vertical-pos' => 'top',
            'vertical-rel' => 'page',
        ]);
        $template = $this->template();
        $template->assign(['test1' => $image]);
        $template->render();

        $firstOptions = $image->getImageOptions();
        $firstLegacy = $image->getOwnImageStyleRequirements();
        $first = $this->save($template, 'image-first');

        $template->render();
        $secondOptions = $image->getImageOptions();
        $secondLegacy = $image->getOwnImageStyleRequirements();
        $second = $this->save($template, 'image-second');

        self::assertSame($firstOptions, $secondOptions);
        self::assertSame($firstLegacy, $secondLegacy);
        self::assertSame($this->entry($first, 'content.xml'), $this->entry($second, 'content.xml'));
        self::assertSame($this->entry($first, 'styles.xml'), $this->entry($second, 'styles.xml'));
        self::assertFalse($this->archiveContains($second, 'Pictures/WaltDietzney.png'));
    }

    public function testRepeatedLegacyRenderKeepsRepresentativeStructuredContentStable(): void
    {
        $elements = [
            'paragraph' => (new Paragraph())->addText('Repeated paragraph'),
            'rich text' => (new RichText())->addParagraph('Repeated rich text'),
            'list' => (new ListElement())->addItem((new Paragraph())->addText('Repeated list')),
            'circular' => new CircularImageElement($this->imagePath()),
            'frame' => new DrawTextBox('Repeated frame'),
            'table' => (new RichTable())->addRow(['A', 'B']),
        ];

        foreach ($elements as $label => $element) {
            $template = $this->legacyTemplate($element);
            $first = $this->save($template, 'repeat-' . $label . '-first');
            $template->render();
            $second = $this->save($template, 'repeat-' . $label . '-second');

            self::assertSame(
                $this->entry($first, 'content.xml'),
                $this->entry($second, 'content.xml'),
                $label . ': repeated render changed content.xml'
            );
            self::assertNotSame(
                $this->entry($first, 'styles.xml'),
                $this->entry($second, 'styles.xml'),
                $label . ': repeated legacy render changes styles.xml'
            );
        }
    }

    #[RunInSeparateProcess]
    public function testLegacyCircularImageExposesSemanticDependenciesBeforeAndCompatibilityStateAfterRender(): void
    {
        $image = new CircularImageElement($this->imagePath());
        $semanticBefore = iterator_to_array((new StyleRequirementCollector())->collectSemantic($image), false);
        $fillBefore = iterator_to_array((new FillImageRequirementCollector())->collect($image), false);
        $legacyBefore = $image->getFillImageRequirements();

        $template = $this->legacyTemplate($image);
        $output = $this->save($template, 'circular');

        self::assertNotSame([], $semanticBefore);
        self::assertCount(1, $fillBefore);
        self::assertSame([], $legacyBefore);
        self::assertNotSame([], $image->getFillImageRequirements());
        self::assertNotSame([], $image->getImageStyleRequirements());
        self::assertTrue($this->archiveContains($output, 'Pictures/WaltDietzney.png'));
        self::assertStringContainsString('draw:name="cv_photo_WaltDietzney"', $this->entry($output, 'styles.xml'));
    }

    #[RunInSeparateProcess]
    public function testLegacyFlagIsSetByStructuredRenderEvenWhenPlaceholderIsMissingAndResetByLoad(): void
    {
        $template = new LegacyLifecycleProbeTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        $template->assign(['missing' => new Paragraph()]);

        self::assertFalse($template->legacyStructuredValuesMaterialized());
        $template->render();
        self::assertTrue($template->legacyStructuredValuesMaterialized());

        $template->load();
        self::assertFalse($template->legacyStructuredValuesMaterialized());
    }

    public function testLegacyRenderProcessesContentAndStylesDocumentsIndependently(): void
    {
        $template = new LegacyLifecycleProbeTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        $template->appendPlaceholderToBothParts('probe');
        $probe = new CountingParagraph();
        $template->assign(['probe' => $probe]);
        $template->render();

        self::assertSame(2, $probe->renderCount());
        self::assertTrue($template->legacyStructuredValuesMaterialized());
    }

    public function testLegacyAndSemanticInsertionBothProduceTheirOwnObservableContent(): void
    {
        $semantic = $this->template();
        $semantic->setElement('test1', (new Paragraph())->addText('Semantic path'));
        $semanticOutput = $this->save($semantic, 'semantic');

        $legacy = $this->legacyTemplate((new Paragraph())->addText('Legacy path'));
        $legacyOutput = $this->save($legacy, 'legacy');

        self::assertStringContainsString('Semantic path', $this->entry($semanticOutput, 'content.xml'));
        self::assertStringContainsString('Legacy path', $this->entry($legacyOutput, 'content.xml'));
        self::assertNotSame(
            $this->entry($semanticOutput, 'content.xml'),
            $this->entry($legacyOutput, 'content.xml')
        );
    }

    #[RunInSeparateProcess]
    public function testMixedSetElementAndLegacyRenderKeepsBothSubtreesAndResources(): void
    {
        $template = new LegacyLifecycleProbeTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        $template->appendContentPlaceholder('legacy');
        $template->setElement('test1', (new Paragraph())->addText('Semantic content'));
        $template->assign(['legacy' => new ImageElement($this->imagePath())]);
        $template->render();
        $output = $this->save($template, 'mixed');

        $content = $this->entry($output, 'content.xml');
        self::assertStringContainsString('Semantic content', $content);
        self::assertStringContainsString('Pictures/WaltDietzney.png', $content);
        self::assertFalse($this->archiveContains($output, 'Pictures/WaltDietzney.png'));
    }

    #[RunInSeparateProcess]
    public function testLegacyDocumentsDoNotMaterializeUnrelatedStaticGraphicState(): void
    {
        $first = $this->legacyTemplate(new DrawTextBox('Document A', ['width' => '4cm']));
        $firstName = (string) array_key_first(StyleMapper::getFrameStyles());
        $this->save($first, 'document-a');

        $second = $this->legacyTemplate(new ImageElement($this->imagePath('banner.png')));
        $output = $this->save($second, 'document-b');
        $styles = $this->entry($output, 'styles.xml');

        self::assertArrayHasKey($firstName, StyleMapper::getFrameStyles());
        self::assertStringNotContainsString('style:name="' . $firstName . '"', $styles);
    }

    public function testRefreshPersistsLegacyDomThenResetsLegacyFlag(): void
    {
        $template = new LegacyLifecycleProbeTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        $template->assign(['test1' => (new Paragraph())->addText('Refresh content')]);
        $template->render();
        self::assertTrue($template->legacyStructuredValuesMaterialized());

        $template->refresh();
        self::assertFalse($template->legacyStructuredValuesMaterialized());
        $output = $this->save($template, 'refresh');
        $content = $this->entry($output, 'content.xml');
        self::assertStringNotContainsString('Refresh content', $content);
        self::assertStringContainsString('{{test1}}', $content);
    }

    private function legacyTemplate(OdtElement $element): OdtTemplate
    {
        $template = $this->template();
        $template->assign(['test1' => $element]);
        $template->render();
        return $template;
    }

    private function template(): OdtTemplate
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $this->templates[] = $template;
        return $template;
    }

    private function save(OdtTemplate $template, string $label): string
    {
        $output = sys_get_temp_dir() . '/d5g-b-' . $label . '-' . bin2hex(random_bytes(5)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);
        return $output;
    }

    private function entry(string $archive, string $name): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($archive) === true);
        try {
            $value = $zip->getFromName($name);
            self::assertIsString($value);
            return $value;
        } finally {
            $zip->close();
        }
    }

    private function archiveContains(string $archive, string $name): bool
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($archive) === true);
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

    private function imagePath(string $name = 'WaltDietzney.png'): string
    {
        return dirname(__DIR__, 2) . '/assets/' . $name;
    }
}

final class LegacyLifecycleProbeTemplate extends OdtTemplate
{
    public function legacyStructuredValuesMaterialized(): bool
    {
        return (bool) (new ReflectionProperty(OdtTemplate::class, 'legacyStructuredValuesMaterialized'))
            ->getValue($this);
    }

    public function appendPlaceholderToBothParts(string $key): void
    {
        foreach ([$this->documentContext()->contentDom(), $this->documentContext()->stylesDom()] as $dom) {
            $paragraph = $dom->createElement('text:p');
            $paragraph->appendChild($dom->createTextNode('{{' . $key . '}}'));
            $dom->documentElement?->appendChild($paragraph);
        }
    }

    public function appendContentPlaceholder(string $key): void
    {
        $dom = $this->documentContext()->contentDom();
        $paragraph = $dom->createElement('text:p');
        $paragraph->appendChild($dom->createTextNode('{{' . $key . '}}'));
        $dom->documentElement?->appendChild($paragraph);
    }
}

final class CountingParagraph extends Paragraph
{
    private int $count = 0;

    public function toDomNode(DOMDocument $dom, bool $insideTextBox = false): \DOMNode
    {
        $this->count++;
        return parent::toDomNode($dom, $insideTextBox);
    }

    public function renderCount(): int
    {
        return $this->count;
    }
}
