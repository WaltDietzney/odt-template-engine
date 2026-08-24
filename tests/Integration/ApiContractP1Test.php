<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ApiContractP1Test extends TestCase
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

    public function testLegacyCellStyleIsPersistedOnCorrectOdfLayers(): void
    {
        $cell = new RichTableCell('Important', [
            'background' => '#ffeeee',
            'padding' => '0.2cm',
            'text-align' => 'center',
            'color' => '#cc0000',
            'weight' => 'bold',
        ]);

        $table = (new RichTable())->addRow([$cell]);
        $template = new OdtTemplate($this->templatePath('template_15_simpleTableStyled.odt'));
        $template->setElement('tableblock', $table);

        $outputFile = $this->newOutputFile('cell-style');
        $template->save($outputFile);

        $this->withArchive($outputFile, function (ZipArchive $zip): void {
            $contentXml = $this->readEntry($zip, 'content.xml');
            $stylesXml = $this->readEntry($zip, 'styles.xml');
            $allStyles = $contentXml . "\n" . $stylesXml;

            self::assertStringContainsString('style:family="table-cell"', $allStyles);
            self::assertStringContainsString('fo:background-color="#ffeeee"', $allStyles);
            self::assertStringContainsString('fo:padding="0.2cm"', $allStyles);
            self::assertStringContainsString('fo:text-align="center"', $allStyles);
            self::assertStringContainsString('fo:color="#cc0000"', $allStyles);
            self::assertStringContainsString('fo:font-weight="bold"', $allStyles);

            $cellStyleSection = $this->styleFamilySection($allStyles, 'table-cell');
            self::assertStringNotContainsString('fo:text-align="center"', $cellStyleSection);
            self::assertStringNotContainsString('fo:color="#cc0000"', $cellStyleSection);
            self::assertStringNotContainsString('fo:font-weight="bold"', $cellStyleSection);
        });
    }

    public function testRichTextSplitsParagraphAndTextProperties(): void
    {
        $richText = new RichText();
        $richText->addParagraph('Styled text', null, [
            'margin-left' => '1cm',
            'font-size' => '10pt',
            'color' => '#123456',
            'italic' => true,
        ]);

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $richText);

        $outputFile = $this->newOutputFile('richtext-style');
        $template->save($outputFile);

        $this->withArchive($outputFile, function (ZipArchive $zip): void {
            $stylesXml = $this->readEntry($zip, 'styles.xml');

            self::assertStringContainsString('fo:margin-left="1cm"', $stylesXml);
            self::assertStringContainsString('fo:font-size="10pt"', $stylesXml);
            self::assertStringContainsString('fo:color="#123456"', $stylesXml);
            self::assertStringContainsString('fo:font-style="italic"', $stylesXml);
        });
    }


    public function testRichTextAddTextLiftsParagraphOptionsOutOfTextStyle(): void
    {
        $richText = new RichText();
        $richText->addText('Centered bold text', [
            'text-align' => 'center',
            'bold' => true,
        ]);

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $richText);

        $outputFile = $this->newOutputFile('richtext-add-text');
        $template->save($outputFile);

        $this->withArchive($outputFile, function (ZipArchive $zip): void {
            $stylesXml = $this->readEntry($zip, 'styles.xml');

            self::assertStringContainsString('fo:text-align="center"', $stylesXml);
            self::assertStringContainsString('fo:font-weight="bold"', $stylesXml);
        });
    }

    public function testRichTextConvenienceListsApplyItemTextStyle(): void
    {
        $richText = new RichText();
        $richText->addBulletList(['One', 'Two'], [
            'bold' => true,
            'color' => '#123456',
        ]);

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $richText);

        $outputFile = $this->newOutputFile('list-style');
        $template->save($outputFile);

        $this->withArchive($outputFile, function (ZipArchive $zip): void {
            $contentXml = $this->readEntry($zip, 'content.xml');
            $stylesXml = $this->readEntry($zip, 'styles.xml');

            self::assertStringContainsString('<text:list', $contentXml);
            self::assertStringContainsString('fo:font-weight="bold"', $stylesXml);
            self::assertStringContainsString('fo:color="#123456"', $stylesXml);
        });
    }

    public function testDrawTextBoxRegistersOnlyGraphicFrameStyle(): void
    {
        $box = new DrawTextBox('ContractBox', [
            'width' => '4cm',
            'height' => '2cm',
            'background-color' => '#eeeeee',
            'border' => '0.03cm solid #444444',
        ]);
        $box->addElement((new Paragraph())->addText('Frame content'));
        $box->registerStyles();

        $dom = new DOMDocument('1.0', 'UTF-8');
        $styleNode = $box->toStyleDomNode($dom);

        self::assertNotNull($styleNode);
        self::assertSame('graphic', $styleNode->getAttribute('style:family'));
        self::assertNotEmpty(StyleMapper::$frameStyles);
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function newOutputFile(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/odt-api-contract-p1-' . $suffix . '-' . uniqid('', true) . '.odt';
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

    private function styleFamilySection(string $stylesXml, string $family): string
    {
        $pattern = sprintf(
            '/<style:style\\b[^>]*style:family="%s"[^>]*>.*?<\\/style:style>/s',
            preg_quote($family, '/')
        );
        preg_match_all($pattern, $stylesXml, $matches);

        return implode("\n", $matches[0] ?? []);
    }
}
