<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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

    #[RunInSeparateProcess]
    public function testLegacyNameOnlyParagraphPreservesCompleteRegisteredDefinition(): void
    {
        $style = 'SR4B_LegacyHeading_' . bin2hex(random_bytes(4));
        StyleMapper::registerParagraphStyle($style, [
            'margin-top' => '0.45cm',
            'margin-bottom' => '0.10cm',
            'padding-bottom' => '0.03cm',
            'line-height' => '100%',
            'border-bottom' => '1.5pt solid #12324a',
        ]);

        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt');
        $template->setElement(
            'my_list',
            (new RichText())->addParagraph((new Paragraph($style))->addText('Legacy heading'))
        );
        $template->save($this->outputFile);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($this->outputFile) === true);

        try {
            $stylesXml = $zip->getFromName('styles.xml');
            self::assertIsString($stylesXml);
            self::assertSame(1, substr_count($stylesXml, 'style:name="' . $style . '"'));
            foreach ([
                'fo:margin-top="0.45cm"',
                'fo:margin-bottom="0.10cm"',
                'fo:padding-bottom="0.03cm"',
                'fo:line-height="100%"',
                'fo:border-bottom="1.5pt solid #12324a"',
            ] as $attribute) {
                self::assertStringContainsString($attribute, $stylesXml);
            }
        } finally {
            $zip->close();
        }
    }

    public function testNativeParagraphAndTextPropertiesReachFinalStylesXml(): void
    {
        $paragraph = (new Paragraph('SR4B_NativeParagraph', [
            'fo:margin-top' => '0.42cm',
            'fo:border-bottom' => '1pt solid #123456',
            'fo:text-align' => 'center',
        ]))->addText('Native text', [
            'fo:color' => '#123456',
            'fo:font-size' => '13pt',
            'fo:font-weight' => 'bold',
            'fo:font-style' => 'italic',
            'style:font-name' => 'Liberation Sans',
            'style:text-underline-style' => 'solid',
        ]);

        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt');
        $template->setElement('my_list', (new RichText())->addParagraph($paragraph));
        $template->save($this->outputFile);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($this->outputFile) === true);

        try {
            $stylesXml = $zip->getFromName('styles.xml');
            self::assertIsString($stylesXml);
            foreach ([
                'fo:margin-top="0.42cm"',
                'fo:border-bottom="1pt solid #123456"',
                'fo:text-align="center"',
                'fo:color="#123456"',
                'fo:font-size="13pt"',
                'fo:font-weight="bold"',
                'fo:font-style="italic"',
                'style:font-name="Liberation Sans"',
                'style:text-underline-style="solid"',
            ] as $attribute) {
                self::assertStringContainsString($attribute, $stylesXml);
            }
        } finally {
            $zip->close();
        }
    }
}
