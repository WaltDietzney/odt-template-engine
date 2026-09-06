<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Records the observable family routing caused by the coarse legacy switch.
 *
 * These tests intentionally describe the pre-D5G-C behavior. They are the
 * evidence for the narrow routing change and are updated only where the
 * contract explicitly changes that behavior.
 */
final class D5GLifecycleCompatibilityNarrowingCharacterizationTest extends TestCase
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

    #[RunInSeparateProcess]
    public function testLegacyParagraphDoesNotActivateUnrelatedTableAndCellFinalization(): void
    {
        StyleMapper::registerTableStyle('D5GC_UnrelatedTable', ['table:align' => 'left']);
        StyleMapper::registerTableCellStyle('D5GC_UnrelatedCell', [
            'fo:background-color' => '#abcdef',
        ]);

        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $template->assign(['test1' => new Paragraph()]);
        $template->render();
        $styles = $this->saveAndRead($template, 'legacy-paragraph');

        self::assertStringNotContainsString('style:name="D5GC_UnrelatedTable"', $styles);
        self::assertStringNotContainsString('style:name="D5GC_UnrelatedCell"', $styles);
        $template->cleanup();
    }

    #[RunInSeparateProcess]
    public function testMissingPlaceholderDoesNotActivateUnrelatedTableFinalization(): void
    {
        StyleMapper::registerTableStyle('D5GC_MissingTable', ['table:align' => 'left']);

        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $template->assign(['missing' => new Paragraph()]);
        $template->render();
        $styles = $this->saveAndRead($template, 'missing-placeholder');

        self::assertStringNotContainsString('style:name="D5GC_MissingTable"', $styles);
        $template->cleanup();
    }

    #[RunInSeparateProcess]
    public function testSemanticInsertionAlreadyFiltersUnrelatedRegisteredTableFamilies(): void
    {
        StyleMapper::registerTableStyle('D5GC_SemanticUnrelatedTable', ['table:align' => 'left']);
        StyleMapper::registerTableCellStyle('D5GC_SemanticUnrelatedCell', [
            'fo:background-color' => '#abcdef',
        ]);

        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $template->setElement('test1', new Paragraph());
        $styles = $this->saveAndRead($template, 'semantic-filter');

        self::assertStringNotContainsString('style:name="D5GC_SemanticUnrelatedTable"', $styles);
        self::assertStringNotContainsString('style:name="D5GC_SemanticUnrelatedCell"', $styles);
        $template->cleanup();
    }

    private function saveAndRead(OdtTemplate $template, string $label): string
    {
        $output = sys_get_temp_dir() . '/d5gc-' . $label . '-' . bin2hex(random_bytes(5)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($output) === true);
        try {
            $styles = $zip->getFromName('styles.xml');
            self::assertIsString($styles);
            return $styles;
        } finally {
            $zip->close();
        }
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }
}
