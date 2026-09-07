<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Contracts\HasStyles;
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

        self::assertInstanceOf(HasStyles::class, $paragraph);

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $paragraph);

        self::assertSame(
            0,
            $paragraph->styleDefinitionsCalls,
            'Current setElement() does not dispatch the Contracts\\HasStyles compatibility branch.'
        );

        $outputFile = $this->newOutputFile('paragraph');
        $template->save($outputFile);

        $stylesXml = $this->readArchiveEntry($outputFile, 'styles.xml');
        self::assertStringContainsString('fo:font-weight="bold"', $stylesXml);
    }

    public function testExternalContractsHasStylesElementDoesNotDispatchCompatibilityDefinitions(): void
    {
        $element = new ExternalHasStylesDispatchProbe();

        self::assertInstanceOf(HasStyles::class, $element);

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $element);

        self::assertSame(
            0,
            $element->styleDefinitionsCalls,
            'An external OdtElement implementing the public Contracts\\HasStyles contract is not dispatched either.'
        );
    }

    public function testNoRootNamespaceHasStylesContractExists(): void
    {
        self::assertFalse(interface_exists('OdtTemplateEngine\\HasStyles'));
        self::assertTrue(interface_exists(HasStyles::class));
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
    public int $styleDefinitionsCalls = 0;

    public function getStyleDefinitions(): array
    {
        ++$this->styleDefinitionsCalls;

        return parent::getStyleDefinitions();
    }
}

final class ExternalHasStylesDispatchProbe extends OdtElement
{
    public int $styleDefinitionsCalls = 0;

    public function registerStyles(): void
    {
    }

    public function getStyleDefinitions(): array
    {
        ++$this->styleDefinitionsCalls;

        return [];
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $paragraph = $dom->createElement('text:p');
        $paragraph->appendChild($dom->createTextNode('External HasStyles probe'));

        return $paragraph;
    }
}
