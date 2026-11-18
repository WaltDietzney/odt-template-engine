<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class H02C2PageFillTest extends TestCase
{
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const DRAW = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const FO = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';

    public function testGradientProjectsToLayoutAndDrawingPageAndTransitionsAtomically(): void
    {
        $template = $this->template();
        $template->styles()->defineGradient('H02Gradient', [
            'type' => 'linear', 'start-color' => '#112233', 'end-color' => '#abcdef', 'angle' => 30,
        ]);
        $template->definePageLayout('H02GradientLayout', ['background' => ['type' => 'gradient', 'name' => 'H02Gradient']]);
        $template->definePageStyle('H02GradientMaster', 'H02GradientLayout');
        $inspection = $template->inspectPageLayout('H02GradientLayout');
        self::assertSame('gradient', $inspection['fill']['type']);
        self::assertSame('H02Gradient', $inspection['fill']['fill-gradient-name']);

        $before = $template->inspectPageLayout('H02GradientLayout');
        try {
            $template->updatePageLayout('H02GradientLayout', ['background' => ['type' => 'gradient', 'name' => 'missing']]);
            self::fail('Missing resource must fail before mutation.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('Unknown', $exception->getMessage());
        }
        self::assertSame($before, $template->inspectPageLayout('H02GradientLayout'));
    }

    public function testSharedDrawingStyleIsolatedAndNewMasterReceivesCurrentFill(): void
    {
        $template = $this->template();
        $template->definePageLayout('H02SharedFill', ['background-color' => '#123456']);
        $template->definePageStyle('H02SharedA', 'H02SharedFill');
        $template->definePageLayout('H02Other', []);
        $template->definePageStyle('H02OtherMaster', 'H02Other');
        $template->setPageLayout(['background-color' => '#654321'], 'H02SharedA');
        $template->definePageStyle('H02SharedB', 'H02SharedFill');
        self::assertNotSame(
            $template->inspectPageStyle('H02SharedA')['drawing-style-name'],
            $template->inspectPageStyle('H02SharedB')['drawing-style-name']
        );
        self::assertSame('H02SharedFill', $template->inspectPageStyle('H02SharedB')['page-layout-name']);
    }

    public function testTextboxArrayBackgroundKeepsStringCompatibilityAndValidatesNamedResources(): void
    {
        $template = $this->template();
        $template->styles()->defineGradient('H02TextGradient', [
            'type' => 'linear', 'start-color' => '#000000', 'end-color' => '#ffffff',
        ]);
        $box = (new DrawTextBox('H02TextBox'))->setBackground(['type' => 'gradient', 'name' => 'H02TextGradient']);
        $template->definePageLayout('H02TextLayout', []);
        $template->definePageStyle('H02TextMaster', 'H02TextLayout', ['header' => $box]);
        $output = sys_get_temp_dir() . '/h02-c2-textbox-' . uniqid('', true) . '.odt';
        try {
            $template->save($output);
            $zip = new ZipArchive();
            self::assertTrue($zip->open($output));
            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($zip->getFromName('styles.xml')));
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('style', self::STYLE);
            $xpath->registerNamespace('draw', self::DRAW);
            self::assertSame(1, $xpath->query('//style:graphic-properties[@draw:fill-gradient-name="H02TextGradient"]')->length);
            $zip->close();
        } finally {
            @unlink($output);
        }
    }

    public function testTextboxBackgroundTransitionsRemovePreviousRepresentation(): void
    {
        $box = new DrawTextBox('Transition');
        $box->setBackground('#123456');
        $box->setBackground(['type' => 'gradient', 'name' => 'Gradient']);
        $properties = array_values($box->getFrameStyleRequirements())[0];
        self::assertSame('gradient', $properties['draw:fill']);
        self::assertArrayNotHasKey('fo:background-color', $properties);
        self::assertArrayNotHasKey('draw:fill-color', $properties);
        $box->setBackground('#654321');
        $properties = array_values($box->getFrameStyleRequirements())[0];
        self::assertSame('solid', $properties['draw:fill']);
        self::assertArrayNotHasKey('draw:fill-gradient-name', $properties);
        self::assertNull($box->semanticBackgroundOptions());
    }

    public function testInvalidTextboxFillPreservesPreviousStyle(): void
    {
        $box = (new DrawTextBox('Invalid'))->setBackground('#123456');
        $before = $box->getFrameStyleRequirements();
        foreach ([['type' => 'solid', 'color' => 'red'], ['type' => 'bitmap', 'name' => 'B', 'repeat' => 'bad'],
            ['type' => 'none', 'extra' => true], ['type' => 'gradient']] as $fill) {
            try {
                $box->setBackground($fill);
                self::fail('Invalid fill accepted.');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame($before, $box->getFrameStyleRequirements());
            }
        }
    }

    public function testMissingTextboxReferenceRejectedInBodyAndPlaceholder(): void
    {
        $template = $this->template();
        $box = (new DrawTextBox('Missing'))->setBackground(['type' => 'gradient', 'name' => 'Missing']);
        foreach (['appendBody', 'setElement'] as $method) {
            try {
                if ($method === 'appendBody') {
                    $template->appendBody($box);
                } else {
                    $template->setElement('name', $box);
                }
                self::fail('Missing resource accepted.');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('Unknown background resource', $exception->getMessage());
            }
        }
    }

    private function template(): PageLayoutOdtTemplate
    {
        return new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
    }
}
