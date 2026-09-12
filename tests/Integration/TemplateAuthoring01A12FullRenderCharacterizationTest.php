<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01A12FullRenderCharacterizationTest extends TestCase
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

    public function testFullRenderPreservesSelectedConditionalParagraphAndInlineStyles(): void
    {
        $templatePath = $this->createTemplate(
            '<text:p>{{#if:show}}</text:p>'
            . '<text:p text:style-name="BodyA"><text:span text:style-name="Strong">Selected</text:span></text:p>'
            . '<text:p>{{#else}}</text:p>'
            . '<text:p text:style-name="BodyB">Fallback</text:p>'
            . '<text:p>{{#endif}}</text:p>'
        );

        $template = new OdtTemplate($templatePath);
        $template->setValues(['show' => true]);
        $template->render();

        $output = $this->outputPath();
        $template->save($output);

        $xpath = $this->contentXPath($output);

        self::assertSame(1, $xpath->query('//office:body//text:p')->length);

        $paragraph = $xpath->query('//office:body//text:p')->item(0);
        self::assertInstanceOf(DOMElement::class, $paragraph);
        self::assertSame('BodyA', $paragraph->getAttribute('text:style-name'));

        $span = $xpath->query('//office:body//text:p/text:span')->item(0);
        self::assertInstanceOf(DOMElement::class, $span);
        self::assertSame('Strong', $span->getAttribute('text:style-name'));
        self::assertSame('Selected', $span->textContent);
    }

    public function testFullRenderLeavesUnselectedConditionalTableShellBehind(): void
    {
        $templatePath = $this->createTemplate(
            '<text:p>{{#if:show}}</text:p>'
            . '<table:table table:name="ConditionalTable" table:style-name="TableStyle">'
            . '<table:table-row>'
            . '<table:table-cell>'
            . '<text:p text:style-name="CellBody">Inside conditional table</text:p>'
            . '</table:table-cell>'
            . '</table:table-row>'
            . '</table:table>'
            . '<text:p>{{#else}}</text:p>'
            . '<text:p text:style-name="Fallback">Fallback paragraph</text:p>'
            . '<text:p>{{#endif}}</text:p>'
        );

        $template = new OdtTemplate($templatePath);
        $template->setValues(['show' => false]);
        $template->render();

        $output = $this->outputPath();
        $template->save($output);

        $xpath = $this->contentXPath($output);

        self::assertSame(
            1,
            $xpath->query('//table:table[@table:name="ConditionalTable"]')->length
        );
        self::assertSame(
            0,
            $xpath->query('//table:table[@table:name="ConditionalTable"]//text:p')->length
        );
        self::assertSame(
            1,
            $xpath->query('//text:p[@text:style-name="Fallback"]')->length
        );
    }

    public function testFullRenderForeachClonesNamedTableWithoutIdentityRewriting(): void
    {
        $templatePath = $this->createTemplate(
            '<text:p>{{#foreach:items}}</text:p>'
            . '<table:table table:name="ItemsTable" table:style-name="ItemsTableStyle">'
            . '<table:table-row>'
            . '<table:table-cell><text:p>{{name}}</text:p></table:table-cell>'
            . '</table:table-row>'
            . '</table:table>'
            . '<text:p>{{#endforeach}}</text:p>'
        );

        $template = new OdtTemplate($templatePath);
        $template->setRepeating('items', [
            ['name' => 'Alpha'],
            ['name' => 'Beta'],
        ]);
        $template->render();

        $output = $this->outputPath();
        $template->save($output);

        $xpath = $this->contentXPath($output);
        $tables = $xpath->query('//table:table[@table:name="ItemsTable"]');

        self::assertSame(2, $tables->length);
        self::assertSame('Alpha', trim($tables->item(0)?->textContent ?? ''));
        self::assertSame('Beta', trim($tables->item(1)?->textContent ?? ''));

        foreach ($tables ?: [] as $table) {
            self::assertInstanceOf(DOMElement::class, $table);
            self::assertSame('ItemsTableStyle', $table->getAttribute('table:style-name'));
        }
    }

    public function testFullRenderForeachConsumesNestedConditionMarkersBeforeConditionalPass(): void
    {
        $templatePath = $this->createTemplate(
            '<text:p>{{#foreach:items}}</text:p>'
            . '<text:p>{{#if:active}}</text:p>'
            . '<text:p text:style-name="Active">active {{name}}</text:p>'
            . '<text:p>{{#else}}</text:p>'
            . '<text:p text:style-name="Inactive">inactive {{name}}</text:p>'
            . '<text:p>{{#endif}}</text:p>'
            . '<text:p>{{#endforeach}}</text:p>'
        );

        $template = new OdtTemplate($templatePath);
        $template->setValues(['active' => false]);
        $template->setRepeating('items', [
            ['name' => 'Alpha', 'active' => true],
            ['name' => 'Beta', 'active' => false],
        ]);
        $template->render();

        $output = $this->outputPath();
        $template->save($output);

        $xpath = $this->contentXPath($output);

        $active = $xpath->query('//text:p[@text:style-name="Active"]');
        $inactive = $xpath->query('//text:p[@text:style-name="Inactive"]');

        self::assertSame(2, $active->length);
        self::assertSame(2, $inactive->length);

        self::assertSame('active Alpha', trim($active->item(0)?->textContent ?? ''));
        self::assertSame('active Beta', trim($active->item(1)?->textContent ?? ''));
        self::assertSame('inactive Alpha', trim($inactive->item(0)?->textContent ?? ''));
        self::assertSame('inactive Beta', trim($inactive->item(1)?->textContent ?? ''));

        self::assertSame(
            0,
            $xpath->query('//text:p[contains(., "{{#if:") or contains(., "{{#else}}") or contains(., "{{#endif}}")]')->length
        );
    }

    public function testRenderedOutputCanBeReopenedWithoutChangingCharacterizedStructure(): void
    {
        $templatePath = $this->createTemplate(
            '<text:p>{{#foreach:items}}</text:p>'
            . '<text:p text:style-name="RepeatBody"><text:span text:style-name="RepeatStrong">{{name}}</text:span></text:p>'
            . '<text:p>{{#endforeach}}</text:p>'
        );

        $template = new OdtTemplate($templatePath);
        $template->setRepeating('items', [
            ['name' => 'Alpha'],
            ['name' => 'Beta'],
        ]);
        $template->render();

        $firstOutput = $this->outputPath();
        $template->save($firstOutput);

        $reopened = new OdtTemplate($firstOutput);
        $secondOutput = $this->outputPath();
        $reopened->save($secondOutput);

        $first = $this->contentXPath($firstOutput);
        $second = $this->contentXPath($secondOutput);

        self::assertSame(
            2,
            $first->query('//text:p[@text:style-name="RepeatBody"]')->length
        );
        self::assertSame(
            2,
            $second->query('//text:p[@text:style-name="RepeatBody"]')->length
        );

        self::assertSame(
            ['Alpha', 'Beta'],
            $this->texts($first, '//text:p[@text:style-name="RepeatBody"]')
        );
        self::assertSame(
            ['Alpha', 'Beta'],
            $this->texts($second, '//text:p[@text:style-name="RepeatBody"]')
        );
    }

    private function createTemplate(string $body): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-a12-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);

        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');

        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content'
            . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">'
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
            . '<style:style style:name="BodyA" style:family="paragraph"/>'
            . '<style:style style:name="BodyB" style:family="paragraph"/>'
            . '<style:style style:name="Fallback" style:family="paragraph"/>'
            . '<style:style style:name="CellBody" style:family="paragraph"/>'
            . '<style:style style:name="Active" style:family="paragraph"/>'
            . '<style:style style:name="Inactive" style:family="paragraph"/>'
            . '<style:style style:name="RepeatBody" style:family="paragraph"/>'
            . '<style:style style:name="Strong" style:family="text"/>'
            . '<style:style style:name="RepeatStrong" style:family="text"/>'
            . '<style:style style:name="ItemsTableStyle" style:family="table"/>'
            . '<style:style style:name="TableStyle" style:family="table"/>'
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
            . '<manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/>'
            . '<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>'
            . '</manifest:manifest>'
        );

        $zip->close();

        return $path;
    }

    private function outputPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-a12-out-');
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
