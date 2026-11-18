<?php

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Document\PageLayoutOptions;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class PageLayoutOdtTemplateTest extends TestCase
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO_NS = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';

    public function testSetPageMarginsUpdatesStylesXml(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $template->setPageMargins('0.5cm', '1cm', '1.5cm', '2cm');

        $output = sys_get_temp_dir() . '/odt-page-layout-' . uniqid('', true) . '.odt';
        $template->save($output);

        try {
            $properties = $this->loadPageLayoutProperties($output);

            self::assertSame('0.5cm', $properties['margin-top']);
            self::assertSame('1cm', $properties['margin-right']);
            self::assertSame('1.5cm', $properties['margin-bottom']);
            self::assertSame('2cm', $properties['margin-left']);
        } finally {
            @unlink($output);
        }
    }

    public function testSetPageMarginsPreservesSetPageLayoutPolymorphism(): void
    {
        $template = new class ('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt') extends PageLayoutOdtTemplate {
            public bool $setPageLayoutCalled = false;

            public function setPageLayout(array $options, string $masterPage = 'Standard'): static
            {
                $this->setPageLayoutCalled = true;

                return parent::setPageLayout($options, $masterPage);
            }
        };

        $template->setPageMargins('0.5cm', '1cm', '1.5cm', '2cm');

        self::assertTrue($template->setPageLayoutCalled);
    }

    public function testSetPageLayoutCanChangeOrientationAndPageSize(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $template->setPageLayout([
            'page-width' => '29.7cm',
            'page-height' => '21cm',
            'orientation' => 'landscape',
        ]);

        $output = sys_get_temp_dir() . '/odt-page-layout-' . uniqid('', true) . '.odt';
        $template->save($output);

        try {
            $properties = $this->loadPageLayoutProperties($output);

            self::assertSame('29.7cm', $properties['page-width']);
            self::assertSame('21cm', $properties['page-height']);
            self::assertSame('landscape', $properties['orientation']);
        } finally {
            @unlink($output);
        }
    }

    public function testLegacyUnknownOptionsAndPartialMutationOnInvalidOrientation(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $output = sys_get_temp_dir() . '/odt-h02-legacy-' . uniqid('', true) . '.odt';
        try {
            $template->setPageLayout(['unknown-legacy-option' => 'ignored', 'margin-left' => '1cm']);
            try {
                $template->setPageLayout(['margin-left' => '3cm', 'orientation' => 'invalid']);
                self::fail('Invalid orientation must fail.');
            } catch (\RuntimeException $exception) {
                self::assertStringContainsString('orientation', $exception->getMessage());
            }
            $template->save($output);
            self::assertSame('3cm', $this->loadPageLayoutProperties($output)['margin-left']);
        } finally {
            @unlink($output);
            $template->cleanup();
        }
    }

    public function testLegacyMasterMutationChangesSharedLayout(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $output = sys_get_temp_dir() . '/odt-h02-shared-' . uniqid('', true) . '.odt';
        try {
            $template->definePageLayout('H02Shared', ['margin-left' => '1cm']);
            $template->definePageStyle('H02A', 'H02Shared');
            $template->definePageStyle('H02B', 'H02Shared');
            $template->setPageLayout(['margin-left' => '4cm'], 'H02A');
            $template->save($output);
            $zip = new ZipArchive();
            self::assertTrue($zip->open($output) === true);
            $xml = $zip->getFromName('styles.xml');
            $zip->close();
            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($xml));
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('style', self::STYLE_NS);
            $xpath->registerNamespace('fo', self::FO_NS);
            self::assertSame(2, $xpath->query('//style:master-page[@style:page-layout-name="H02Shared"]')->length);
            self::assertSame('4cm', $xpath->evaluate('string(//style:page-layout[@style:name="H02Shared"]/style:page-layout-properties/@fo:margin-left)'));
        } finally {
            @unlink($output);
            $template->cleanup();
        }
    }

    public function testH02C1ExtendedLayoutAndRegionGeometryUsesNativeOrder(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $template->definePageLayout('H02C1', [
            'columns' => ['count' => 2, 'relative-widths' => [1, 2], 'gap' => '0.4cm'],
            'border' => ['width' => '0.1cm', 'style' => 'double', 'color' => '#112233'],
            'border-line-width' => ['0.02cm', '0.01cm', '0.02cm'],
            'padding' => '0.2cm',
            'header' => ['height' => '1.6cm', 'spacing' => '0.5cm', 'dynamic-spacing' => true],
        ]);
        $inspection = $template->inspectPageLayout('H02C1');

        self::assertSame('H02C1', $inspection['name']);
        self::assertSame('fo:border', array_key_first(array_filter(
            $inspection['properties'], static fn (mixed $value, string $key): bool => $key === 'fo:border',
            ARRAY_FILTER_USE_BOTH
        )));
        self::assertSame('svg:height', array_key_first($inspection['regions']['header']));
        self::assertSame('fo:column-count', $inspection['properties']['columns']['attributes'] !== []
            ? array_key_first($inspection['properties']['columns']['attributes'])
            : null);
    }

    public function testH02C1ExtendedMutationRollsBackAllChanges(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $before = $template->inspectPageLayout('Mpm1');
        try {
            $template->updatePageLayout('Mpm1', [
                'margin-left' => '9cm',
                'header' => ['height' => '1cm', 'min-height' => '2cm'],
            ]);
            self::fail('Conflicting region geometry must fail.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('both', $exception->getMessage());
        }
        self::assertSame($before, $template->inspectPageLayout('Mpm1'));
    }

    public function testH02C1ShorthandAndMixedBorderTransitionsPreserveEffectiveSides(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $template->definePageLayout('H02Transitions', [
            'border' => ['width' => '0.1cm', 'style' => 'double', 'color' => '#112233'],
            'border-line-width' => ['0.02cm', '0.01cm', '0.02cm'],
            'padding' => '0.2cm',
            'shadow' => '#112233 0.1cm -0.1cm',
        ]);
        $template->updatePageLayout('H02Transitions', [
            'border-top' => ['width' => '0.1cm', 'style' => 'solid', 'color' => '#445566'],
            'padding-right' => '0.3cm',
            'shadow' => 'none',
        ]);
        $after = $template->inspectPageLayout('H02Transitions');

        self::assertArrayNotHasKey('fo:border', $after['properties']);
        self::assertSame('0.1cm solid #445566', $after['properties']['fo:border-top']);
        self::assertSame('0.1cm double #112233', $after['properties']['fo:border-bottom']);
        self::assertSame('0.3cm', $after['properties']['fo:padding-right']);
        self::assertSame('0.2cm', $after['properties']['fo:padding-top']);
        self::assertArrayNotHasKey('style:border-line-width-top', $after['properties']);
        self::assertArrayHasKey('style:border-line-width-bottom', $after['properties']);
        self::assertSame('none', $after['properties']['style:shadow']);

        $template->updatePageLayout('H02Transitions', [
            'border' => ['width' => '0.1cm', 'style' => 'solid', 'color' => '#778899'],
        ]);
        $solid = $template->inspectPageLayout('H02Transitions');
        self::assertArrayNotHasKey('fo:border-top', $solid['properties']);
        self::assertArrayNotHasKey('style:border-line-width-bottom', $solid['properties']);
    }

    public function testH02C1DoubleLineWidthsRejectZeroInEveryPositionAndRegion(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $border = ['width' => '0.15cm', 'style' => 'double', 'color' => '#112233'];
        $valid = ['0.04cm', '0.05cm', '0.06cm'];
        $template->definePageLayout('H02Positive', [
            'border' => $border, 'border-line-width' => $valid,
            'header' => ['border' => $border, 'border-line-width' => $valid],
            'footer' => ['border' => $border, 'border-line-width' => $valid],
        ]);
        $before = $template->inspectPageLayout('H02Positive');
        foreach (['page', 'header', 'footer'] as $region) {
            foreach (['border-line-width', 'border-line-width-top'] as $key) {
                foreach ([0, 1, 2] as $position) {
                    $widths = $valid;
                    $widths[$position] = '0.00cm';
                    $options = $region === 'page' ? [$key => $widths] : [$region => [$key => $widths]];
                    try {
                        $template->updatePageLayout('H02Positive', $options);
                        self::fail('Zero double-line width must be rejected.');
                    } catch (\InvalidArgumentException $exception) {
                        self::assertStringContainsString('positive', $exception->getMessage());
                    }
                    self::assertSame($before, $template->inspectPageLayout('H02Positive'));
                }
            }
        }
    }

    public function testH02C1ColumnsRejectBalanceAndPreserveAuthoredChildOrder(): void
    {
        $dom = new DOMDocument();
        $properties = $dom->createElementNS(self::STYLE_NS, 'style:page-layout-properties');
        $dom->appendChild($properties);
        $background = $dom->createElementNS(self::STYLE_NS, 'style:background-image');
        $background->setAttribute('data-authored', 'bitmap');
        $unrelated = $dom->createElementNS(self::STYLE_NS, 'style:unrelated');
        $footnote = $dom->createElementNS(self::STYLE_NS, 'style:footnote-sep');
        $properties->append($background, $unrelated, $footnote);

        PageLayoutOptions::applyPage($properties, ['columns' => ['count' => 2, 'relative-widths' => [1, 2]]]);
        $names = [];
        foreach ($properties->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $names[] = $child->localName;
            }
        }
        self::assertSame(['background-image', 'unrelated', 'columns', 'footnote-sep'], $names);
        self::assertSame('bitmap', $background->getAttribute('data-authored'));

        PageLayoutOptions::applyPage($properties, ['columns' => ['count' => 1]]);
        self::assertSame(1, $properties->getElementsByTagNameNS(self::STYLE_NS, 'columns')->length);
        self::assertSame('1', $properties->getElementsByTagNameNS(self::STYLE_NS, 'columns')->item(0)->getAttributeNS(self::FO_NS, 'column-count'));

        $before = $dom->saveXML();
        try {
            PageLayoutOptions::applyPage($properties, ['columns' => ['count' => 2, 'balance' => false]]);
            self::fail('Page columns must reject balance even when false.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('balance', $exception->getMessage());
        }
        self::assertSame($before, $dom->saveXML());
    }

    public function testH02C1ExtendedSaveReopenAndRepeatedSaveRemainStable(): void
    {
        $template = new PageLayoutOdtTemplate('tests/Fixtures/LegacySamples/templates/template_01_simple_variables.odt');
        $template->definePageLayout('H02Repeated', ['columns' => ['count' => 1], 'shadow' => 'none']);
        $first = sys_get_temp_dir() . '/odt-h02-repeated-a-' . uniqid('', true) . '.odt';
        $second = sys_get_temp_dir() . '/odt-h02-repeated-b-' . uniqid('', true) . '.odt';
        try {
            $template->save($first);
            $reopened = new PageLayoutOdtTemplate($first);
            $reopened->updatePageLayout('H02Repeated', ['padding' => '0cm']);
            $reopened->save($second);
            $again = new PageLayoutOdtTemplate($second);
            $properties = $again->inspectPageLayout('H02Repeated')['properties'];
            self::assertSame('none', $properties['style:shadow']);
            self::assertSame('0cm', $properties['fo:padding']);
        } finally {
            @unlink($first);
            @unlink($second);
            $template->cleanup();
        }
    }

    /**
     * @return array<string, string>
     */
    private function loadPageLayoutProperties(string $odtPath): array
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($odtPath) === true);

        $xml = $zip->getFromName('styles.xml');
        $zip->close();

        self::assertIsString($xml);

        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($xml));

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('style', self::STYLE_NS);
        $xpath->registerNamespace('fo', self::FO_NS);

        $masterPage = $xpath->query('//style:master-page[@style:name="Standard"]')->item(0);
        self::assertInstanceOf(DOMElement::class, $masterPage);

        $layoutName = $masterPage->getAttributeNS(self::STYLE_NS, 'page-layout-name');
        self::assertNotSame('', $layoutName);

        $node = $xpath->query(
            sprintf('//style:page-layout[@style:name="%s"]/style:page-layout-properties', $layoutName)
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $node);

        return [
            'margin-top' => $node->getAttributeNS(self::FO_NS, 'margin-top'),
            'margin-right' => $node->getAttributeNS(self::FO_NS, 'margin-right'),
            'margin-bottom' => $node->getAttributeNS(self::FO_NS, 'margin-bottom'),
            'margin-left' => $node->getAttributeNS(self::FO_NS, 'margin-left'),
            'page-width' => $node->getAttributeNS(self::FO_NS, 'page-width'),
            'page-height' => $node->getAttributeNS(self::FO_NS, 'page-height'),
            'orientation' => $node->getAttributeNS(self::STYLE_NS, 'print-orientation'),
        ];
    }
}
