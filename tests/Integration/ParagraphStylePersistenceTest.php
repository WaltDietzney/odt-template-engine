<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ParagraphStylePersistenceTest extends TestCase
{
    private string $outputFile;

    protected function setUp(): void
    {
        $this->outputFile = sys_get_temp_dir() . '/odt-paragraph-style-' . uniqid('', true) . '.odt';
    }

    protected function tearDown(): void
    {
        if (is_file($this->outputFile)) {
            unlink($this->outputFile);
        }
    }

    public function testNamedParagraphStyleIsPersistedInStylesXml(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt';
        self::assertFileExists($templatePath);

        $template = new OdtTemplate($templatePath);

        $paragraph = new Paragraph('IntegrationParagraphStyle', [
            'margin-top' => '0.42cm',
            'margin-bottom' => '0.11cm',
            'border-bottom' => '1pt solid #123456',
            'line-height' => '110%',
        ]);
        $paragraph->addText('Named paragraph style', [
            'bold' => true,
        ]);

        $richText = new RichText();
        $richText->addParagraph($paragraph);

        $template->setElement('my_list', $richText);
        $template->save($this->outputFile);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($this->outputFile) === true);

        try {
            $contentXml = $zip->getFromName('content.xml');
            $stylesXml = $zip->getFromName('styles.xml');

            self::assertIsString($contentXml);
            self::assertIsString($stylesXml);

            self::assertStringContainsString(
                'text:style-name="IntegrationParagraphStyle"',
                $contentXml
            );
            self::assertStringContainsString(
                'style:name="IntegrationParagraphStyle"',
                $stylesXml
            );
            self::assertStringContainsString(
                'style:family="paragraph"',
                $stylesXml
            );
            self::assertStringContainsString('fo:margin-top="0.42cm"', $stylesXml);
            self::assertStringContainsString('fo:margin-bottom="0.11cm"', $stylesXml);
            self::assertStringContainsString('fo:border-bottom="1pt solid #123456"', $stylesXml);
            self::assertStringContainsString('fo:line-height="110%"', $stylesXml);

            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($stylesXml));
        } finally {
            $zip->close();
        }
    }
}
