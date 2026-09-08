<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use LogicException;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Style\DocumentStyles;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleApi02CPublicIntegrationTest extends TestCase
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
    }

    public function testStylesReturnsStableFacadeAndMaterializesNamedParagraphThroughPublicApi(): void
    {
        $template = $this->template();
        $styles = $template->styles();

        self::assertInstanceOf(DocumentStyles::class, $styles);
        self::assertSame($styles, $template->styles());

        $styleName = 'StyleApi02CPublic_' . bin2hex(random_bytes(4));
        $template->styles()->defineParagraph($styleName, [
            'margin-top' => '0.42cm',
            'margin-bottom' => '0.11cm',
            'bold' => true,
            'color' => '#123456',
        ]);

        $output = $this->artifactPath('public-api');
        $template->setElement(
            'INLINE_BOX',
            (new Paragraph($styleName))->addText('Public document style facade')
        );
        $template->save($output);

        $content = $this->zipEntry($output, 'content.xml');
        $stylesXml = $this->zipEntry($output, 'styles.xml');

        self::assertStringContainsString('text:style-name="' . $styleName . '"', $content);
        self::assertSame(1, $this->paragraphStyleCount($stylesXml, $styleName));
        self::assertStringContainsString('fo:margin-top="0.42cm"', $stylesXml);
        self::assertStringContainsString('fo:margin-bottom="0.11cm"', $stylesXml);
        self::assertStringContainsString('fo:font-weight="bold"', $stylesXml);
        self::assertStringContainsString('fo:color="#123456"', $stylesXml);
    }

    public function testEquivalentDefinitionIsIdempotentAndConflictingDefinitionFails(): void
    {
        $template = $this->template();
        $styleName = 'StyleApi02CPublicConflict_' . bin2hex(random_bytes(4));
        $definition = ['margin-top' => '0.1cm'];

        $template->styles()->defineParagraph($styleName, $definition);
        $template->styles()->defineParagraph($styleName, $definition);

        $this->expectException(LogicException::class);
        $template->styles()->defineParagraph($styleName, ['margin-top' => '0.2cm']);
    }

    public function testAuthoredTemplateParagraphStyleIsNotOverwritten(): void
    {
        $template = $this->template();
        $template->styles()->defineParagraph('Heading', ['margin-top' => '9cm']);

        $output = $this->artifactPath('authored');
        $template->setElement(
            'INLINE_BOX',
            (new Paragraph('Heading'))->addText('Authored style remains authoritative')
        );
        $template->save($output);

        $stylesXml = $this->zipEntry($output, 'styles.xml');
        self::assertSame(1, $this->paragraphStyleCount($stylesXml, 'Heading'));
        self::assertStringNotContainsString('fo:margin-top="9cm"', $stylesXml);
    }

    public function testRetainedFacadeFollowsCurrentDocumentAfterLoadWithoutLeakingPreviousDefinition(): void
    {
        $template = $this->template();
        $styles = $template->styles();
        $oldName = 'StyleApi02COld_' . bin2hex(random_bytes(4));
        $newName = 'StyleApi02CNew_' . bin2hex(random_bytes(4));

        $styles->defineParagraph($oldName, ['margin-top' => '0.1cm']);
        $template->load();
        $styles->defineParagraph($newName, ['margin-top' => '0.2cm']);

        $output = $this->artifactPath('load-boundary');
        $template->setElement(
            'INLINE_BOX',
            (new Paragraph($newName))->addText('Current logical document')
        );
        $template->save($output);

        $stylesXml = $this->zipEntry($output, 'styles.xml');
        self::assertSame(0, $this->paragraphStyleCount($stylesXml, $oldName));
        self::assertSame(1, $this->paragraphStyleCount($stylesXml, $newName));
        self::assertStringContainsString('fo:margin-top="0.2cm"', $stylesXml);
    }

    public function testRepeatedSaveKeepsNamedParagraphDefinitionStable(): void
    {
        $template = $this->template();
        $styleName = 'StyleApi02CStable_' . bin2hex(random_bytes(4));
        $template->styles()->defineParagraph($styleName, ['margin-bottom' => '0.3cm']);
        $template->setElement(
            'INLINE_BOX',
            (new Paragraph($styleName))->addText('Stable output')
        );

        $first = $this->artifactPath('first-save');
        $second = $this->artifactPath('second-save');
        $template->save($first);
        $template->save($second);

        self::assertSame($this->zipEntry($first, 'content.xml'), $this->zipEntry($second, 'content.xml'));
        self::assertSame($this->zipEntry($first, 'styles.xml'), $this->zipEntry($second, 'styles.xml'));
        self::assertSame(1, $this->paragraphStyleCount($this->zipEntry($second, 'styles.xml'), $styleName));
    }

    private function template(): OdtTemplate
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/template_17_textfield.odt';
        self::assertFileExists($path);

        return new OdtTemplate($path);
    }

    private function artifactPath(string $label): string
    {
        $path = sys_get_temp_dir() . '/odt-style-api-02c-' . $label . '-' . bin2hex(random_bytes(5)) . '.odt';
        $this->outputFiles[] = $path;

        return $path;
    }

    private function zipEntry(string $path, string $name): string
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

    private function paragraphStyleCount(string $stylesXml, string $name): int
    {
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($stylesXml));
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');

        return $xpath->query(sprintf(
            '//style:style[@style:family="paragraph" and @style:name="%s"]',
            $name
        ))->length;
    }
}
