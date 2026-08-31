<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMElement;
use LogicException;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextElementIntegrationTest extends TestCase
{
    public function testSetElementRegistersParagraphRequirementInCurrentDocument(): void
    {
        $style = '01D_Paragraph_' . bin2hex(random_bytes(4));
        $template = new StyleContextInspectableTemplate($this->templatePath());

        $template->setElement('my_list', $this->richText($style, ['margin-left' => '1cm']));

        self::assertSame(['margin-left' => '1cm'], $template->paragraphStyles()[$style]);
    }

    public function testSetElementRegistersTextRequirementInCurrentDocument(): void
    {
        $template = new StyleContextInspectableTemplate($this->templatePath());
        $textStyle = ['font-family' => '01FC Context Font', 'color' => '#123456'];
        $element = (new RichText())->addParagraph(
            (new Paragraph())->addText('Document-local text', $textStyle)
        );
        $style = array_key_first($element->getRequiredStyles());

        self::assertIsString($style);
        $template->setElement('my_list', $element);

        self::assertSame([$style => $textStyle], $template->textStyles());
    }

    public function testStructuredElementRequirementsAreDocumentIsolated(): void
    {
        $styleA = '01D_A_' . bin2hex(random_bytes(4));
        $styleB = '01D_B_' . bin2hex(random_bytes(4));
        $templateA = new StyleContextInspectableTemplate($this->templatePath());
        $templateB = new StyleContextInspectableTemplate($this->templatePath());

        $templateA->setElement('my_list', $this->richText($styleA, ['margin-left' => '2cm']));
        $templateB->setElement('my_list', $this->richText($styleB, ['margin-left' => '3cm']));

        self::assertArrayHasKey($styleA, $templateA->paragraphStyles());
        self::assertArrayNotHasKey($styleA, $templateB->paragraphStyles());
        self::assertArrayHasKey($styleB, $templateB->paragraphStyles());
        self::assertArrayNotHasKey($styleB, $templateA->paragraphStyles());
    }

    public function testEquivalentRepeatedRequirementsAreIdempotent(): void
    {
        $style = '01D_Equivalent_' . bin2hex(random_bytes(4));
        $template = new StyleContextInspectableTemplate($this->templatePath());
        $template->appendPlaceholder('my_list_second');
        $element = $this->richText($style, ['margin-left' => '4cm']);

        $template->setElement('my_list', $element);
        $template->setElement('my_list_second', $this->richText($style, ['margin-left' => '4cm']));

        self::assertSame([$style => ['margin-left' => '4cm']], $template->paragraphStyles());
    }

    public function testConflictingPendingRequirementFailsBeforeSecondElementMaterialization(): void
    {
        $style = '01D_Conflict_' . bin2hex(random_bytes(4));
        $template = new StyleContextInspectableTemplate($this->templatePath());
        $template->appendPlaceholder('my_list_second');
        $template->setElement('my_list', $this->richText($style, ['margin-left' => '5cm']));
        $before = $template->contentXml();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already registered with a different definition');

        try {
            $template->setElement('my_list_second', $this->richText($style, ['margin-left' => '6cm']));
        } finally {
            self::assertSame($before, $template->contentXml());
        }
    }

    public function testLoadResetsRequirementsCollectedThroughSetElement(): void
    {
        $style = '01D_Reset_' . bin2hex(random_bytes(4));
        $template = new StyleContextInspectableTemplate($this->templatePath());

        $template->setElement('my_list', $this->richText($style, ['margin-left' => '7cm']));
        self::assertArrayHasKey($style, $template->paragraphStyles());

        $template->load();

        self::assertArrayNotHasKey($style, $template->paragraphStyles());
    }

    public function testSetElementPersistsRegisteredParagraphStyleInSavedStylesXml(): void
    {
        $style = '01D_Persisted_' . bin2hex(random_bytes(4));
        $output = sys_get_temp_dir() . '/odt-style-context-element-' . bin2hex(random_bytes(6)) . '.odt';

        try {
            $template = new StyleContextInspectableTemplate($this->templatePath());
            $template->setElement('my_list', $this->richText($style, ['margin-left' => '8cm']));
            $template->save($output);

            $archive = new ZipArchive();
            self::assertSame(true, $archive->open($output));

            try {
                $stylesXml = $archive->getFromName('styles.xml');

                self::assertIsString($stylesXml);
                self::assertStringContainsString('style:name="' . $style . '"', $stylesXml);
                self::assertStringContainsString('fo:margin-left="8cm"', $stylesXml);
            } finally {
                $archive->close();
            }
        } finally {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    private function richText(string $style, array $options): RichText
    {
        $paragraph = new Paragraph($style, $options);
        $paragraph->addText('Structured paragraph');

        return (new RichText())->addParagraph($paragraph);
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt';
    }
}

final class StyleContextInspectableTemplate extends OdtTemplate
{
    /** @return array<string, array<string, mixed>> */
    public function paragraphStyles(): array
    {
        return $this->documentContext()->styleContext()->paragraphStyles();
    }

    /** @return array<string, array<string, mixed>> */
    public function textStyles(): array
    {
        return $this->documentContext()->styleContext()->textStyles();
    }

    public function appendPlaceholder(string $name): void
    {
        $dom = $this->documentContext()->contentDom();
        $paragraph = $dom->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
            'p'
        )->item(0);
        if (!$paragraph instanceof DOMElement) {
            throw new \RuntimeException('Template fixture has no paragraph.');
        }
        $paragraph->appendChild($dom->createTextNode('{{' . $name . '}}'));
    }

    public function contentXml(): string
    {
        return $this->documentContext()->contentDom()->saveXML() ?: '';
    }
}
