<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Import\HtmlImporter;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ApiContractP0Test extends TestCase
{
    /** @var list<string> */
    private array $outputFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->outputFiles as $outputFile) {
            if (is_file($outputFile)) {
                unlink($outputFile);
            }
        }

        $this->outputFiles = [];
    }

    public function testAnonymousParagraphStyleOptionsArePersisted(): void
    {
        $paragraph = new Paragraph(null, [
            'margin-top' => '0.37cm',
            'margin-bottom' => '0.09cm',
        ]);
        $paragraph->addText('Anonymous paragraph style');

        $styles = $paragraph->getRequiredParagraphStyles();
        self::assertCount(1, $styles);
        $styleName = array_key_first($styles);
        self::assertIsString($styleName);
        self::assertStringStartsWith('para_', $styleName);

        $richText = new RichText();
        $richText->addParagraph($paragraph);

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $richText);

        $outputFile = $this->newOutputFile('paragraph');
        $template->save($outputFile);

        $this->withArchive($outputFile, function (ZipArchive $zip) use ($styleName): void {
            $contentXml = $this->readEntry($zip, 'content.xml');
            $stylesXml = $this->readEntry($zip, 'styles.xml');

            self::assertStringContainsString(
                sprintf('text:style-name="%s"', $styleName),
                $contentXml
            );
            self::assertStringContainsString(
                sprintf('style:name="%s"', $styleName),
                $stylesXml
            );
            self::assertStringContainsString('fo:margin-top="0.37cm"', $stylesXml);
            self::assertStringContainsString('fo:margin-bottom="0.09cm"', $stylesXml);
            $this->assertWellFormedXml($contentXml, 'content.xml');
            $this->assertWellFormedXml($stylesXml, 'styles.xml');
        });
    }

    public function testRichTableWrapsParagraphAndRichTextContentAsCells(): void
    {
        $table = new RichTable();
        $table->addRow([
            (new Paragraph())->addText('Paragraph cell'),
            (new RichText())->addText('RichText cell'),
        ]);

        $template = new OdtTemplate($this->templatePath('template_15_simpleTableStyled.odt'));
        $template->setElement('tableblock', $table);

        $outputFile = $this->newOutputFile('table');
        $template->save($outputFile);

        $this->withArchive($outputFile, function (ZipArchive $zip): void {
            $contentXml = $this->readEntry($zip, 'content.xml');

            self::assertStringContainsString('<table:table', $contentXml);
            self::assertStringContainsString('Paragraph cell', $contentXml);
            self::assertStringContainsString('RichText cell', $contentXml);
            $this->assertWellFormedXml($contentXml, 'content.xml');
        });
    }

    public function testImageElementUsesCalculatedAspectRatioInOutput(): void
    {
        $imagePath = dirname(__DIR__, 2) . '/assets/banner.png';
        self::assertFileExists($imagePath);

        [$pixelWidth, $pixelHeight] = getimagesize($imagePath);
        $expectedHeight = round(6.0 * ($pixelHeight / $pixelWidth), 3) . 'cm';

        $image = new ImageElement($imagePath, [
            'width' => '6cm',
            'anchor' => 'as-char',
        ]);

        $richText = new RichText();
        $richText->addImage($image);

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $richText);

        $outputFile = $this->newOutputFile('image');
        $template->save($outputFile);

        $this->withArchive($outputFile, function (ZipArchive $zip) use ($expectedHeight): void {
            $contentXml = $this->readEntry($zip, 'content.xml');

            self::assertStringContainsString('svg:width="6cm"', $contentXml);
            self::assertStringContainsString(
                sprintf('svg:height="%s"', $expectedHeight),
                $contentXml
            );
            self::assertStringContainsString('Pictures/banner.png', $contentXml);
            $this->assertWellFormedXml($contentXml, 'content.xml');
        });
    }

    public function testHtmlTableCellSupportsNestedFormattedElements(): void
    {
        $html = '<table><tbody><tr>'
            . '<td style="text-align: center; margin-top: 0.2cm">'
            . '<strong>Nested formatting</strong>'
            . '</td>'
            . '</tr></tbody></table>';

        $template = new OdtTemplate($this->templatePath('template_19_htmlTable.odt'));
        $template->setElement('tableblock', HtmlImporter::fromHtml($html));

        $outputFile = $this->newOutputFile('html-table');
        $template->save($outputFile);

        $this->withArchive($outputFile, function (ZipArchive $zip): void {
            $contentXml = $this->readEntry($zip, 'content.xml');
            $stylesXml = $this->readEntry($zip, 'styles.xml');

            self::assertStringContainsString('<table:table', $contentXml);
            self::assertStringContainsString('Nested formatting', $contentXml);
            self::assertStringContainsString('fo:font-weight="bold"', $stylesXml);
            $this->assertWellFormedXml($contentXml, 'content.xml');
            $this->assertWellFormedXml($stylesXml, 'styles.xml');
        });
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function newOutputFile(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/odt-api-contract-p0-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->outputFiles[] = $path;

        return $path;
    }

    private function withArchive(string $path, callable $callback): void
    {
        self::assertFileExists($path);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $callback($zip);
        } finally {
            $zip->close();
        }
    }

    private function readEntry(ZipArchive $zip, string $entry): string
    {
        $content = $zip->getFromName($entry);
        self::assertIsString($content, sprintf('Unable to read ODT package entry: %s', $entry));

        return $content;
    }

    private function assertWellFormedXml(string $xml, string $fileName): void
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            self::assertTrue($dom->loadXML($xml), sprintf('%s must contain well-formed XML.', $fileName));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
