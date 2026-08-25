<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class TemplateProcessingArch04ATest extends TestCase
{
    public function testFiltersNl2brAndConditionalBranchRemainObservable(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_02_filter.odt'));

        try {
            $template->assign([
                'name' => 'Anna Beispiel',
                'email' => 'ANNA@EXAMPLE.COM',
                'kommentar' => "Line one\nLine two",
                'geburtstag' => '1995-08-15',
                'umsatz' => '1345.5',
                'is_admin' => true,
            ]);
            $template->render();

            $content = $template->contentXml();

            self::assertStringContainsString('ANNA BEISPIEL', $content);
            self::assertStringContainsString('anna@example.com', $content);
            self::assertStringContainsString('15.08.1995', $content);
            self::assertStringContainsString('1.345,50 €', $content);
            self::assertStringContainsString('Line one', $content);
            self::assertStringContainsString('Line two', $content);
            self::assertStringContainsString('text:line-break', $content);
            self::assertStringContainsString('Admin!', $content);
            self::assertStringNotContainsString('Hello User!', $content);
        } finally {
            $template->cleanup();
        }
    }

    public function testConditionalComparisonIfnotAndMissingValuesRemainCompatible(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_03_logic_elements.odt'));

        try {
            $template->assign(['price' => 0]);
            $template->render();

            $content = $template->contentXml();

            self::assertStringContainsString('Bargain Hunter', $content);
            self::assertStringContainsString('No Access!', $content);
            self::assertStringContainsString('Hello User!', $content);
            self::assertStringNotContainsString('Premium Customer', $content);
            self::assertStringNotContainsString('Standard Customer', $content);
            self::assertStringNotContainsString('{{#if', $content);
        } finally {
            $template->cleanup();
        }
    }

    public function testRepeatingRowsReplaceRowPlaceholdersAfterNormalRendering(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            $template->assignRepeating('items', [
                ['produkt' => 'Coffee', 'preis' => '4.99'],
                ['produkt' => 'Tea', 'preis' => '3.49'],
            ]);
            $template->render();

            $content = $template->contentXml();

            self::assertStringContainsString('Name: Coffee', $content);
            self::assertStringContainsString('Preis: 4.99', $content);
            self::assertStringContainsString('Name: Tea', $content);
            self::assertStringContainsString('Preis: 3.49', $content);
            self::assertStringNotContainsString('{{#foreach:items}}', $content);
            self::assertStringNotContainsString('{{#endforeach}}', $content);
        } finally {
            $template->cleanup();
        }
    }

    public function testRenderingTwiceIsCurrentlyStableAfterPlaceholdersAreConsumed(): void
    {
        $template = new Arch04InspectableTemplate($this->templatePath('template_01_simple_variables.odt'));

        try {
            $template->assign(['name' => 'Repeat render']);
            $template->render();
            $firstRender = $template->contentXml();

            $template->render();

            self::assertSame($firstRender, $template->contentXml());
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
}

final class Arch04InspectableTemplate extends OdtTemplate
{
    public function contentXml(): string
    {
        return $this->domContent->saveXML() ?: '';
    }
}
