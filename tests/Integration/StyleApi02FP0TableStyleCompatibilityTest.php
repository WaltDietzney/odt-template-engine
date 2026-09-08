<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleApi02FP0TableStyleCompatibilityTest extends TestCase
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
    public function testLegacyRegisteredTableStyleProducesEquivalentSemanticAndOdtOutput(): void
    {
        $name = '02FP0_Table_' . bin2hex(random_bytes(4));
        $properties = [
            'table:width' => '15cm',
            'table:align' => 'left',
            'style:rel-width' => '100%',
        ];
        StyleMapper::registerTableStyle($name, $properties);

        $table = (new RichTable())
            ->setTableName('02FP0Table')
            ->setTableStyleName($name)
            ->addRow(['A']);
        $requirements = array_values(array_filter(
            iterator_to_array($table->getOwnStyleRequirements(), false),
            static fn (StyleRequirement $requirement): bool => $requirement->family() === 'table'
        ));

        self::assertCount(1, $requirements);
        self::assertSame(StyleRequirement::KIND_REFERENCE, $requirements[0]->kind());
        self::assertNull($requirements[0]->scope());
        self::assertNull($requirements[0]->documentPart());
        self::assertSame($name, $requirements[0]->name());
        self::assertSame([], $requirements[0]->propertyGroups());

        $template = new OdtTemplate($this->templatePath('template_11_table.odt'));
        $template->setElement('tableblock', $table);
        $output = sys_get_temp_dir() . '/odt-style-api-02f-p0-table-' . bin2hex(random_bytes(5)) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');
        self::assertStringContainsString('table:style-name="' . $name . '"', $content);
        self::assertStringNotContainsString('style:name="' . $name . '"', $styles);
        self::assertStringNotContainsString('table:width="15cm"', $styles);
        self::assertStringNotContainsString('table:align="left"', $styles);
        self::assertStringNotContainsString('style:rel-width="100%"', $styles);
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
}
