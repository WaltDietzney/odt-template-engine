<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Characterizes the table-style boundary before SR-07 semantic migration.
 *
 * These tests intentionally lock in current mixed semantic/legacy behavior,
 * including surprising process-global registrations and direct DOM side
 * effects. They are evidence for the SR-07 Change Contract, not approval of
 * the behavior as the long-term architecture.
 */
final class TableStyleSemanticsCharacterizationTest extends TestCase
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
    public function testNormalSetElementMaterializesCellDefinitionInContentAndStyles(): void
    {
        $cell = new RichTableCell('Cell', [
            'background' => '#ddeeff',
            'padding' => '0.2cm',
        ]);
        $styleName = $cell->getStyleName();
        $table = (new RichTable())->addRow([$cell]);
        $template = $this->template();

        $template->setElement('tableblock', $table);
        $output = $this->outputPath('normal-cell-dual-materialization');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');

        self::assertSame(1, $this->styleCount($content, $styleName, 'table-cell'));
        self::assertSame(1, $this->styleCount($styles, $styleName, 'table-cell'));
        self::assertSame(
            1,
            $this->styleCountInContainer($content, $styleName, 'table-cell', 'automatic-styles')
        );
        self::assertSame(
            1,
            $this->styleCountInContainer($styles, $styleName, 'table-cell', 'styles')
        );
    }

    #[RunInSeparateProcess]
    public function testLegacyAssignRenderUsesTheSameCellDefinitionChannels(): void
    {
        $cell = new RichTableCell('Legacy Cell', [
            'background' => '#fff3cd',
            'border' => '0.1pt solid #999999',
        ]);
        $styleName = $cell->getStyleName();
        $table = (new RichTable())->addRow([$cell]);
        $template = $this->template();

        $template->assign(['tableblock' => $table]);
        $template->render();
        $output = $this->outputPath('legacy-cell-dual-materialization');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');

        self::assertSame(1, $this->styleCount($content, $styleName, 'table-cell'));
        self::assertSame(1, $this->styleCount($styles, $styleName, 'table-cell'));
    }

    #[RunInSeparateProcess]
    public function testStaticTableStylesLeakIntoLaterDocumentFinalization(): void
    {
        $firstStyle = 'SR07_Table_A_' . bin2hex(random_bytes(4));
        $secondStyle = 'SR07_Table_B_' . bin2hex(random_bytes(4));

        StyleMapper::registerTableStyle($firstStyle, ['table:align' => 'left']);
        $first = $this->template();
        $firstTable = (new RichTable())->setTableStyleName($firstStyle)->addRow(['A']);
        $first->setElement('tableblock', $firstTable);
        $first->save($this->outputPath('table-leak-first'));

        StyleMapper::registerTableStyle($secondStyle, ['table:align' => 'right']);
        $second = $this->template();
        $secondTable = (new RichTable())->setTableStyleName($secondStyle)->addRow(['B']);
        $second->setElement('tableblock', $secondTable);
        $output = $this->outputPath('table-leak-second');
        $second->save($output);

        $styles = $this->entry($output, 'styles.xml');

        self::assertSame(1, $this->styleCount($styles, $firstStyle, 'table'));
        self::assertSame(1, $this->styleCount($styles, $secondStyle, 'table'));
    }

    #[RunInSeparateProcess]
    public function testStaticTableCellStylesLeakIntoLaterDocumentFinalization(): void
    {
        $firstCell = new RichTableCell('A', ['background' => '#ffe0e0']);
        $firstName = $firstCell->getStyleName();
        $first = $this->template();
        $first->setElement('tableblock', (new RichTable())->addRow([$firstCell]));
        $first->save($this->outputPath('cell-leak-first'));

        $secondCell = new RichTableCell('B', ['background' => '#e0ffe0']);
        $secondName = $secondCell->getStyleName();
        self::assertNotSame($firstName, $secondName);

        $second = $this->template();
        $second->setElement('tableblock', (new RichTable())->addRow([$secondCell]));
        $output = $this->outputPath('cell-leak-second');
        $second->save($output);

        $styles = $this->entry($output, 'styles.xml');
        $content = $this->entry($output, 'content.xml');

        self::assertSame(1, $this->styleCount($styles, $firstName, 'table-cell'));
        self::assertSame(1, $this->styleCount($styles, $secondName, 'table-cell'));
        self::assertSame(0, $this->styleCount($content, $firstName, 'table-cell'));
        self::assertSame(1, $this->styleCount($content, $secondName, 'table-cell'));
    }

    #[RunInSeparateProcess]
    public function testLoadDoesNotResetStaticTableRegistries(): void
    {
        $tableStyle = 'SR07_Load_Table_' . bin2hex(random_bytes(4));
        StyleMapper::registerTableStyle($tableStyle, ['table:align' => 'center']);

        $cell = new RichTableCell('Persistent', ['background' => '#eeeeee']);
        $cellStyle = $cell->getStyleName();

        self::assertArrayHasKey($tableStyle, StyleMapper::getRegisteredTableStyles());
        self::assertArrayHasKey($cellStyle, StyleMapper::getRegisteredTableCellStyles());

        $template = $this->template();
        $template->load();

        self::assertArrayHasKey($tableStyle, StyleMapper::getRegisteredTableStyles());
        self::assertArrayHasKey($cellStyle, StyleMapper::getRegisteredTableCellStyles());
    }

    #[RunInSeparateProcess]
    public function testExplicitColumnWidthsMaterializeAutomaticColumnStylesOnlyInContentXml(): void
    {
        $table = (new RichTable())->addRow(['A', 'B']);
        $table->setColumnWidths(['2cm', '4cm']);
        $template = $this->template();

        $template->setElement('tableblock', $table);
        $output = $this->outputPath('column-width-parts');
        $template->save($output);

        $content = $this->entry($output, 'content.xml');
        $styles = $this->entry($output, 'styles.xml');

        self::assertSame(1, $this->styleCount($content, 'co0', 'table-column'));
        self::assertSame(1, $this->styleCount($content, 'co1', 'table-column'));
        self::assertSame(0, $this->styleCount($styles, 'co0', 'table-column'));
        self::assertSame(0, $this->styleCount($styles, 'co1', 'table-column'));
        self::assertStringContainsString('style:column-width="2cm"', $content);
        self::assertStringContainsString('style:column-width="4cm"', $content);
    }

    public function testColumnWriterReusesPositionalNamesWithoutConflictResolution(): void
    {
        $dom = $this->contentDom();

        self::assertSame(['co0'], StyleWriter::writeColumnStyles($dom, ['2cm']));
        self::assertSame(['co0'], StyleWriter::writeColumnStyles($dom, ['7cm']));

        $xml = $dom->saveXML() ?: '';
        self::assertSame(2, $this->styleCount($xml, 'co0', 'table-column'));
        self::assertStringContainsString('style:column-width="2cm"', $xml);
        self::assertStringContainsString('style:column-width="7cm"', $xml);
    }

    public function testStoredRowStyleIsCurrentlyDormant(): void
    {
        $table = new RichTable();
        $table->addRow(['A', 'B'], [
            'background' => '#ff0000',
            'min-height' => '2cm',
        ]);

        $dom = $this->contentDom();
        $rendered = $table->toDomNode($dom);
        $dom->documentElement->appendChild($rendered);
        $xml = $dom->saveXML() ?: '';

        self::assertSame(0, $this->familyCount($xml, 'table-row'));
        self::assertSame(1, $this->tableRowCount($xml));
        self::assertSame(0, $this->styledTableRowCount($xml));
        self::assertStringNotContainsString('#ff0000', $xml);
        self::assertStringNotContainsString('2cm', $xml);
    }

    #[RunInSeparateProcess]
    public function testExistingCommonTableAndCellDefinitionsRemainAuthoritativeInStyleWriter(): void
    {
        $tableName = 'SR07ExistingTable';
        $cellName = 'SR07ExistingCell';
        $dom = $this->stylesDom();
        $officeStyles = $this->officeStyles($dom);

        $this->appendStyle(
            $dom,
            $officeStyles,
            $tableName,
            'table',
            'table-properties',
            ['table:align' => 'center']
        );
        $this->appendStyle(
            $dom,
            $officeStyles,
            $cellName,
            'table-cell',
            'table-cell-properties',
            ['fo:background-color' => '#123456']
        );

        StyleMapper::registerTableStyle($tableName, ['table:align' => 'left']);
        StyleMapper::registerTableCellStyle($cellName, ['background' => '#abcdef']);

        StyleWriter::writeAllStyles($dom);
        $xml = $dom->saveXML() ?: '';

        self::assertSame(1, $this->styleCount($xml, $tableName, 'table'));
        self::assertSame(1, $this->styleCount($xml, $cellName, 'table-cell'));
        self::assertStringContainsString('table:align="center"', $xml);
        self::assertStringNotContainsString('table:align="left"', $xml);
        self::assertStringContainsString('fo:background-color="#123456"', $xml);
        self::assertStringNotContainsString('fo:background-color="#abcdef"', $xml);
    }

    #[RunInSeparateProcess]
    public function testRichTableCellCompatibilityStyleApisExposeNativeCellDefinition(): void
    {
        $cell = new RichTableCell('Compatibility', [
            'background' => '#abcdef',
            'padding' => '0.3cm',
            'bold' => true,
            'text-align' => 'right',
        ]);
        $styleName = $cell->getStyleName();
        $definitions = $cell->getStyleDefinitions();

        self::assertArrayHasKey($styleName, $definitions);
        self::assertSame('#abcdef', $definitions[$styleName]['fo:background-color'] ?? null);
        self::assertSame('0.3cm', $definitions[$styleName]['fo:padding'] ?? null);
        self::assertArrayNotHasKey('fo:font-weight', $definitions[$styleName]);
        self::assertArrayNotHasKey('fo:text-align', $definitions[$styleName]);

        $dom = $this->contentDom();
        $styleNode = $cell->toStyleDomNode($dom);
        self::assertInstanceOf(DOMElement::class, $styleNode);
        self::assertSame('table-cell', $styleNode->getAttribute('style:family'));
        self::assertSame('Default', $styleNode->getAttribute('style:parent-style-name'));
        self::assertSame($styleName, $styleNode->getAttribute('style:name'));
        self::assertSame('table-cell-properties', $styleNode->firstChild?->localName);
    }

    #[RunInSeparateProcess]
    public function testRichTableCompatibilityDefinitionsDoNotTraverseOwnedCells(): void
    {
        $cell = new RichTableCell('Owned', ['background' => '#fedcba']);
        $styleName = $cell->getStyleName();
        $table = (new RichTable())->addRow([$cell]);

        self::assertArrayHasKey($styleName, $cell->getStyleDefinitions());
        self::assertSame([], $table->getStyleDefinitions());
        self::assertSame([$cell], iterator_to_array($table->ownedElements(), false));
    }

    private function template(): OdtTemplate
    {
        $template = new OdtTemplate($this->templatePath('template_15_simpleTableStyled.odt'));
        $this->templates[] = $template;

        return $template;
    }

    private function outputPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/sr07a-' . $suffix . '-' . bin2hex(random_bytes(6)) . '.odt';
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

    private function templatePath(string $name): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $name;
        self::assertFileExists($path);

        return $path;
    }

    private function styleCount(string $xml, string $name, string $family): int
    {
        $dom = $this->loadXml($xml);
        $xpath = $this->xpath($dom);

        return $xpath->query(
            sprintf('//style:style[@style:name="%s" and @style:family="%s"]', $name, $family)
        )->length;
    }

    private function styleCountInContainer(
        string $xml,
        string $name,
        string $family,
        string $container
    ): int {
        $dom = $this->loadXml($xml);
        $xpath = $this->xpath($dom);

        return $xpath->query(
            sprintf(
                '//office:%s/style:style[@style:name="%s" and @style:family="%s"]',
                $container,
                $name,
                $family
            )
        )->length;
    }

    private function familyCount(string $xml, string $family): int
    {
        $dom = $this->loadXml($xml);
        $xpath = $this->xpath($dom);

        return $xpath->query(sprintf('//style:style[@style:family="%s"]', $family))->length;
    }

    private function tableRowCount(string $xml): int
    {
        $dom = $this->loadXml($xml);
        $xpath = $this->xpath($dom);

        return $xpath->query('//table:table-row')->length;
    }

    private function styledTableRowCount(string $xml): int
    {
        $dom = $this->loadXml($xml);
        $xpath = $this->xpath($dom);

        return $xpath->query('//table:table-row[@table:style-name]')->length;
    }

    private function loadXml(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($xml));

        return $dom;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        $xpath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');

        return $xpath;
    }

    private function contentDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'office:document-content'
        );
        $dom->appendChild($root);
        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:style',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0'
        );
        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:table',
            'urn:oasis:names:tc:opendocument:xmlns:table:1.0'
        );
        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:text',
            'urn:oasis:names:tc:opendocument:xmlns:text:1.0'
        );
        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:fo',
            'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0'
        );

        $automaticStyles = $dom->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'office:automatic-styles'
        );
        $root->appendChild($automaticStyles);

        return $dom;
    }

    private function stylesDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'office:document-styles'
        );
        $dom->appendChild($root);
        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:style',
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0'
        );
        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:table',
            'urn:oasis:names:tc:opendocument:xmlns:table:1.0'
        );
        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:fo',
            'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0'
        );

        $root->appendChild($dom->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'office:styles'
        ));

        return $dom;
    }

    private function officeStyles(DOMDocument $dom): DOMElement
    {
        $xpath = $this->xpath($dom);
        $styles = $xpath->query('/office:document-styles/office:styles')->item(0);
        self::assertInstanceOf(DOMElement::class, $styles);

        return $styles;
    }

    /** @param array<string, string> $properties */
    private function appendStyle(
        DOMDocument $dom,
        DOMElement $container,
        string $name,
        string $family,
        string $propertyGroup,
        array $properties
    ): void {
        $style = $dom->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style:style'
        );
        $style->setAttributeNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style:name',
            $name
        );
        $style->setAttributeNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style:family',
            $family
        );

        $group = $dom->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style:' . $propertyGroup
        );
        foreach ($properties as $attribute => $value) {
            [$prefix] = explode(':', $attribute, 2);
            $namespace = match ($prefix) {
                'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                'table' => 'urn:oasis:names:tc:opendocument:xmlns:table:1.0',
                'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                default => null,
            };
            self::assertNotNull($namespace);
            $group->setAttributeNS($namespace, $attribute, $value);
        }

        $style->appendChild($group);
        $container->appendChild($style);
    }
}
