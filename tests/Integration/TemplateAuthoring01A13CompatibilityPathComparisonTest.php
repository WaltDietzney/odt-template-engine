<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01A13CompatibilityPathComparisonTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testSimpleRepeatingParagraphProducesEquivalentVisibleResult(): void
    {
        $body = '<text:p>{{#foreach:items}}</text:p>'
            . '<text:p text:style-name="RepeatBody">{{name}}</text:p>'
            . '<text:p>{{#endforeach}}</text:p>';

        $renderOutput = $this->renderPath(
            $body,
            [],
            ['items' => [
                ['name' => 'Alpha'],
                ['name' => 'Beta'],
            ]]
        );

        $directOutput = $this->directRepeatingPath(
            $body,
            [],
            ['items' => [
                ['name' => 'Alpha'],
                ['name' => 'Beta'],
            ]]
        );

        $render = $this->contentXPath($renderOutput);
        $direct = $this->contentXPath($directOutput);

        self::assertSame(
            ['Alpha', 'Beta'],
            $this->texts($render, '//text:p[@text:style-name="RepeatBody"]')
        );
        self::assertSame(
            ['Alpha', 'Beta'],
            $this->texts($direct, '//text:p[@text:style-name="RepeatBody"]')
        );
    }

    public function testBothPathsDuplicateNamedTableIdentityVerbatim(): void
    {
        $body = '<text:p>{{#foreach:items}}</text:p>'
            . '<table:table table:name="ItemsTable" table:style-name="ItemsTableStyle">'
            . '<table:table-row>'
            . '<table:table-cell><text:p>{{name}}</text:p></table:table-cell>'
            . '</table:table-row>'
            . '</table:table>'
            . '<text:p>{{#endforeach}}</text:p>';

        $rows = ['items' => [
            ['name' => 'Alpha'],
            ['name' => 'Beta'],
        ]];

        $render = $this->contentXPath($this->renderPath($body, [], $rows));
        $direct = $this->contentXPath($this->directRepeatingPath($body, [], $rows));

        self::assertSame(
            2,
            $render->query('//table:table[@table:name="ItemsTable"]')->length
        );
        self::assertSame(
            2,
            $direct->query('//table:table[@table:name="ItemsTable"]')->length
        );
    }

    public function testRenderPathPreservesGlobalScalarInsideRepeatBeforeRowBinding(): void
    {
        $body = '<text:p>{{#foreach:items}}</text:p>'
            . '<text:p text:style-name="RepeatBody">{{name}} / {{global}}</text:p>'
            . '<text:p>{{#endforeach}}</text:p>';

        $output = $this->renderPath(
            $body,
            ['global' => 'GLOBAL'],
            ['items' => [
                ['name' => 'Alpha'],
                ['name' => 'Beta'],
            ]]
        );

        $xpath = $this->contentXPath($output);

        self::assertSame(
            ['Alpha / GLOBAL', 'Beta / GLOBAL'],
            $this->texts($xpath, '//text:p[@text:style-name="RepeatBody"]')
        );
    }

    public function testDirectRepeatingPathConsumesUnknownGlobalScalarBeforeLaterRender(): void
    {
        $body = '<text:p>{{#foreach:items}}</text:p>'
            . '<text:p text:style-name="RepeatBody">{{name}} / {{global}}</text:p>'
            . '<text:p>{{#endforeach}}</text:p>';

        $templatePath = $this->createTemplate($body);
        $template = new OdtTemplate($templatePath);
        $template->setValues(['global' => 'GLOBAL']);

        $template->setRepeatingData([
            'items' => [
                ['name' => 'Alpha'],
                ['name' => 'Beta'],
            ],
        ]);

        // The direct compatibility path already consumed {{global}} as an
        // unknown row key. A later normal render cannot recover the token.
        $template->render();

        $output = $this->outputPath();
        $template->save($output);

        $xpath = $this->contentXPath($output);

        self::assertSame(
            ['Alpha /', 'Beta /'],
            $this->texts($xpath, '//text:p[@text:style-name="RepeatBody"]')
        );
    }

    public function testBothPathsConsumeNestedConditionMarkersDuringRowBinding(): void
    {
        $body = '<text:p>{{#foreach:items}}</text:p>'
            . '<text:p>{{#if:active}}</text:p>'
            . '<text:p text:style-name="Active">active {{name}}</text:p>'
            . '<text:p>{{#else}}</text:p>'
            . '<text:p text:style-name="Inactive">inactive {{name}}</text:p>'
            . '<text:p>{{#endif}}</text:p>'
            . '<text:p>{{#endforeach}}</text:p>';

        $rows = ['items' => [
            ['name' => 'Alpha', 'active' => true],
            ['name' => 'Beta', 'active' => false],
        ]];

        $render = $this->contentXPath(
            $this->renderPath($body, ['active' => false], $rows)
        );

        $directOutput = $this->directRepeatingPath(
            $body,
            ['active' => false],
            $rows,
            true
        );
        $direct = $this->contentXPath($directOutput);

        foreach ([$render, $direct] as $xpath) {
            self::assertSame(2, $xpath->query('//text:p[@text:style-name="Active"]')->length);
            self::assertSame(2, $xpath->query('//text:p[@text:style-name="Inactive"]')->length);
            self::assertSame(
                0,
                $xpath->query(
                    '//text:p[contains(., "{{#if:")'
                    . ' or contains(., "{{#else}}")'
                    . ' or contains(., "{{#endif}}")]'
                )->length
            );
        }
    }

    public function testRenderAndDirectPathsUseDifferentProtectedFacadeBoundaries(): void
    {
        $templatePath = $this->createTemplate(
            '<text:p>{{#foreach:items}}</text:p>'
            . '<text:p>{{name}}</text:p>'
            . '<text:p>{{#endforeach}}</text:p>'
        );

        $template = new class ($templatePath) extends OdtTemplate {
            public int $singleRepeaterCalls = 0;
            public int $allRepeaterCalls = 0;

            protected function applyRepeatingInDom(
                DOMDocument $dom,
                string $key,
                array $rows
            ): void {
                ++$this->singleRepeaterCalls;
                parent::applyRepeatingInDom($dom, $key, $rows);
            }

            protected function applyAllRepeatingBlocksInDom(
                DOMDocument $dom,
                array $repeatingData
            ): void {
                ++$this->allRepeaterCalls;
                parent::applyAllRepeatingBlocksInDom($dom, $repeatingData);
            }
        };

        $template->assignRepeating('items', [['name' => 'Alpha']]);
        $template->render();

        // content.xml + styles.xml
        self::assertSame(2, $template->singleRepeaterCalls);
        self::assertSame(0, $template->allRepeaterCalls);

        $template->load();
        $template->singleRepeaterCalls = 0;
        $template->allRepeaterCalls = 0;

        $template->setRepeatingData([
            'items' => [['name' => 'Alpha']],
        ]);

        self::assertSame(0, $template->singleRepeaterCalls);
        // content.xml + styles.xml
        self::assertSame(2, $template->allRepeaterCalls);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, array<int, array<string, mixed>>> $repeaters
     */
    private function renderPath(
        string $body,
        array $values,
        array $repeaters
    ): string {
        $template = new OdtTemplate($this->createTemplate($body));
        $template->setValues($values);

        foreach ($repeaters as $key => $rows) {
            $template->assignRepeating($key, $rows);
        }

        $template->render();

        $output = $this->outputPath();
        $template->save($output);

        return $output;
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, array<int, array<string, mixed>>> $repeaters
     */
    private function directRepeatingPath(
        string $body,
        array $values,
        array $repeaters,
        bool $renderAfter = false
    ): string {
        $template = new OdtTemplate($this->createTemplate($body));
        $template->setValues($values);
        $template->setRepeatingData($repeaters);

        if ($renderAfter) {
            $template->render();
        }

        $output = $this->outputPath();
        $template->save($output);

        return $output;
    }

    private function createTemplate(string $body): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-a13-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        $zip = new ZipArchive();
        self::assertTrue(
            $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true
        );

        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');

        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content'
            . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
            . ' xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0">'
            . '<office:automatic-styles/>'
            . '<office:body><office:text>'
            . $body
            . '</office:text></office:body>'
            . '</office:document-content>'
        );

        $zip->addFromString(
            'styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles'
            . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">'
            . '<office:styles>'
            . '<style:style style:name="RepeatBody" style:family="paragraph"/>'
            . '<style:style style:name="Active" style:family="paragraph"/>'
            . '<style:style style:name="Inactive" style:family="paragraph"/>'
            . '<style:style style:name="ItemsTableStyle" style:family="table"/>'
            . '</office:styles>'
            . '<office:automatic-styles/>'
            . '<office:master-styles/>'
            . '</office:document-styles>'
        );

        $zip->addFromString(
            'meta.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-meta'
            . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">'
            . '<office:meta/>'
            . '</office:document-meta>'
        );

        $zip->addFromString(
            'META-INF/manifest.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest:manifest'
            . ' xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">'
            . '<manifest:file-entry manifest:full-path="/"'
            . ' manifest:media-type="application/vnd.oasis.opendocument.text"/>'
            . '<manifest:file-entry manifest:full-path="content.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="styles.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="meta.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '</manifest:manifest>'
        );

        $zip->close();

        return $path;
    }

    private function outputPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-a13-out-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        return $path;
    }

    private function contentXPath(string $path): DOMXPath
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        $content = $zip->getFromName('content.xml');
        $zip->close();

        self::assertIsString($content);

        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($content));

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace(
            'office',
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0'
        );
        $xpath->registerNamespace(
            'text',
            'urn:oasis:names:tc:opendocument:xmlns:text:1.0'
        );
        $xpath->registerNamespace(
            'table',
            'urn:oasis:names:tc:opendocument:xmlns:table:1.0'
        );

        return $xpath;
    }

    /** @return list<string> */
    private function texts(DOMXPath $xpath, string $query): array
    {
        $texts = [];
        foreach ($xpath->query($query) ?: [] as $node) {
            $texts[] = trim($node->textContent);
        }

        return $texts;
    }
}
