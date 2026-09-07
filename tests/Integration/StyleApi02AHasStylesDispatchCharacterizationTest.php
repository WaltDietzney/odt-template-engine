<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleApi02AHasStylesDispatchCharacterizationTest extends TestCase
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

    public function testNormalContractsHasStylesParagraphDoesNotDispatchCompatibilityDefinitions(): void
    {
        $paragraph = new HasStylesParagraphDispatchProbe();
        $paragraph->addText('HasStyles paragraph probe', ['bold' => true]);

        self::assertFalse(method_exists($paragraph, 'registerStyles'));
        self::assertFalse(method_exists($paragraph, 'getStyleDefinitions'));

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $paragraph);

        $outputFile = $this->newOutputFile('paragraph');
        $template->save($outputFile);

        $stylesXml = $this->readArchiveEntry($outputFile, 'styles.xml');
        self::assertStringContainsString('fo:font-weight="bold"', $stylesXml);
    }

    public function testExternalContractsHasStylesElementDoesNotDispatchCompatibilityDefinitions(): void
    {
        $element = new ExternalHasStylesDispatchProbe();

        self::assertFalse(method_exists($element, 'registerStyles'));
        self::assertFalse(method_exists($element, 'getStyleDefinitions'));

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $element);

    }

    public function testNoRootNamespaceHasStylesContractExists(): void
    {
        self::assertFalse(interface_exists('OdtTemplateEngine\\HasStyles'));
        self::assertFalse(interface_exists('OdtTemplateEngine\\Contracts\\HasStyles'));
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function newOutputFile(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/odt-style-api-02a-hasstyles-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->outputFiles[] = $path;

        return $path;
    }

    private function readArchiveEntry(string $path, string $entry): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $content = $zip->getFromName($entry);
            self::assertIsString($content);

            return $content;
        } finally {
            $zip->close();
        }
    }
}

final class HasStylesParagraphDispatchProbe extends Paragraph
{
}

final class ExternalHasStylesDispatchProbe extends OdtElement
{
    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $paragraph = $dom->createElement('text:p');
        $paragraph->appendChild($dom->createTextNode('External HasStyles probe'));

        return $paragraph;
    }
}
