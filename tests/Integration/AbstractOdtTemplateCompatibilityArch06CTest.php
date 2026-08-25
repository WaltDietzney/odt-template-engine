<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use PHPUnit\Framework\TestCase;

final class AbstractOdtTemplateCompatibilityArch06CTest extends TestCase
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

    public function testPublicRenderDispatchesThroughInheritedProtectedHooks(): void
    {
        $template = new Arch06CProcessingProbeTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            $template->assign(['name' => 'ARCH-06C']);
            $template->render();

            self::assertSame(2, $template->fixBrokenVariablesCalls);
            self::assertSame(2, $template->setValuesInDomCalls);
            self::assertStringContainsString('ARCH-06C', $template->contentXml());
        } finally {
            $template->cleanup();
        }
    }

    public function testStructuredFacadeDispatchRemainsObservableThroughInheritedProtectedHook(): void
    {
        $template = new Arch06CStructuredProbeTemplate($this->templatePath('template_18_ListStyles.odt'));

        try {
            $template->setElement('my_list', (new Paragraph())->addText('ARCH-06C structured value'));

            self::assertGreaterThan(0, $template->replacePlaceholderCalls);
            self::assertStringContainsString('ARCH-06C structured value', $template->contentXml());
        } finally {
            $template->cleanup();
        }
    }

    public function testCompatibilityDomMirrorsRemainSynchronizedWithDocumentContext(): void
    {
        $template = new Arch06CStateProbeTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            self::assertSame($template->contentMirror(), $template->contextContent());
            self::assertSame($template->stylesMirror(), $template->contextStyles());
            self::assertSame($template->metaMirror(), $template->contextMeta());

            $initialContent = $template->contentMirror();
            $template->assign(['name' => 'state check']);
            $template->render();

            self::assertSame($initialContent, $template->contentMirror());
            self::assertSame($template->contentMirror(), $template->contextContent());
            self::assertSame($template->stylesMirror(), $template->contextStyles());

            $template->load();

            self::assertNotSame($initialContent, $template->contentMirror());
            self::assertSame($template->contentMirror(), $template->contextContent());
            self::assertSame($template->stylesMirror(), $template->contextStyles());
            self::assertSame($template->metaMirror(), $template->contextMeta());
        } finally {
            $template->cleanup();
        }
    }

    public function testPageLayoutSubclassOverrideRemainsReachableThroughSave(): void
    {
        $template = new Arch06CPageLayoutProbeTemplate(
            $this->templatePath('template_01_simple_variables.odt')
        );
        $output = $this->newOutputPath('page-layout');

        try {
            $template->save($output);

            self::assertSame(1, $template->adjustBulletIndentationCalls);
            self::assertFileExists($output);
        } finally {
            $template->cleanup();
        }
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function newOutputPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/odt-arch06c-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->outputFiles[] = $path;

        return $path;
    }
}

final class Arch06CProcessingProbeTemplate extends OdtTemplate
{
    public int $fixBrokenVariablesCalls = 0;
    public int $setValuesInDomCalls = 0;

    protected function fixBrokenVariables(\DOMNode $node): void
    {
        $this->fixBrokenVariablesCalls++;
        parent::fixBrokenVariables($node);
    }

    protected function setValuesInDom(DOMDocument $dom, array $values): void
    {
        $this->setValuesInDomCalls++;
        parent::setValuesInDom($dom, $values);
    }

    public function contentXml(): string
    {
        return $this->domContent->saveXML() ?: '';
    }
}

final class Arch06CStructuredProbeTemplate extends OdtTemplate
{
    public int $replacePlaceholderCalls = 0;

    protected function replacePlaceholderWithDom(
        DOMDocument $dom,
        string $key,
        \DOMNode $replacement
    ): void {
        $this->replacePlaceholderCalls++;
        parent::replacePlaceholderWithDom($dom, $key, $replacement);
    }

    public function contentXml(): string
    {
        return $this->domContent->saveXML() ?: '';
    }
}

final class Arch06CStateProbeTemplate extends OdtTemplate
{
    public function contentMirror(): DOMDocument
    {
        return $this->domContent;
    }

    public function stylesMirror(): DOMDocument
    {
        return $this->domStyles;
    }

    public function metaMirror(): DOMDocument
    {
        return $this->domMeta;
    }

    public function contextContent(): DOMDocument
    {
        return $this->documentContext()->contentDom();
    }

    public function contextStyles(): DOMDocument
    {
        return $this->documentContext()->stylesDom();
    }

    public function contextMeta(): DOMDocument
    {
        return $this->documentContext()->metaDom();
    }
}

final class Arch06CPageLayoutProbeTemplate extends PageLayoutOdtTemplate
{
    public int $adjustBulletIndentationCalls = 0;

    protected function adjustBulletIndentation(): void
    {
        $this->adjustBulletIndentationCalls++;
        parent::adjustBulletIndentation();
    }
}
