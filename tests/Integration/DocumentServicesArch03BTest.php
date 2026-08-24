<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Document\MetadataManager;
use OdtTemplateEngine\Document\PageLayoutManager;
use OdtTemplateEngine\OdtPackage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DocumentServicesArch03BTest extends TestCase
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO_NS = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';

    public function testMetadataManagerReadsUpdatesAndCreatesSupportedMetadata(): void
    {
        $package = new OdtPackage($this->templatePath('template_04_metadata.odt'));

        try {
            $manager = new MetadataManager($package->context());
            $before = $manager->get();
            self::assertIsArray($before);

            $manager->set([
                'title' => 'ARCH-03B metadata',
                'author' => 'Document service',
                'coverage' => 'Integration coverage',
                'unsupported_key' => 'must be ignored',
            ]);

            $metadata = $manager->get();
            self::assertSame('ARCH-03B metadata', $metadata['title'] ?? null);
            self::assertSame('Document service', $metadata['author'] ?? null);
            self::assertSame('Integration coverage', $metadata['coverage'] ?? null);
            self::assertArrayNotHasKey('unsupported_key', $metadata);

            $xml = $package->context()->metaDom()->saveXML() ?: '';
            self::assertStringContainsString('Integration coverage', $xml);
            self::assertStringNotContainsString('must be ignored', $xml);
        } finally {
            $package->cleanup();
        }
    }

    public function testPageLayoutManagerUpdatesMarginsPageSizeAndOrientation(): void
    {
        $package = new OdtPackage($this->templatePath('template_01_simple_variables.odt'));

        try {
            $manager = new PageLayoutManager($package->context());
            $manager->setMargins('0.5cm', '1cm', '1.5cm', '2cm');
            $manager->setLayout([
                'page-width' => '29.7cm',
                'page-height' => '21cm',
                'orientation' => 'landscape',
            ]);

            $properties = $this->standardPageLayoutProperties($package);
            self::assertSame('0.5cm', $properties->getAttributeNS(self::FO_NS, 'margin-top'));
            self::assertSame('1cm', $properties->getAttributeNS(self::FO_NS, 'margin-right'));
            self::assertSame('1.5cm', $properties->getAttributeNS(self::FO_NS, 'margin-bottom'));
            self::assertSame('2cm', $properties->getAttributeNS(self::FO_NS, 'margin-left'));
            self::assertSame('29.7cm', $properties->getAttributeNS(self::FO_NS, 'page-width'));
            self::assertSame('21cm', $properties->getAttributeNS(self::FO_NS, 'page-height'));
            self::assertSame('landscape', $properties->getAttributeNS(self::STYLE_NS, 'print-orientation'));

            $manager->setLayout(['orientation' => 'portrait']);
            self::assertSame('portrait', $properties->getAttributeNS(self::STYLE_NS, 'print-orientation'));
        } finally {
            $package->cleanup();
        }
    }

    public function testPageLayoutManagerRejectsInvalidOrientation(): void
    {
        $package = new OdtPackage($this->templatePath('template_01_simple_variables.odt'));

        try {
            $manager = new PageLayoutManager($package->context());

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Page orientation must be "portrait" or "landscape".');
            $manager->setLayout(['orientation' => 'diagonal']);
        } finally {
            $package->cleanup();
        }
    }

    public function testPageLayoutManagerRejectsEmptyOption(): void
    {
        $package = new OdtPackage($this->templatePath('template_01_simple_variables.odt'));

        try {
            $manager = new PageLayoutManager($package->context());

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Page layout option "margin-top" must not be empty.');
            $manager->setLayout(['margin-top' => '   ']);
        } finally {
            $package->cleanup();
        }
    }

    public function testPageLayoutManagerRejectsUnknownMasterPage(): void
    {
        $package = new OdtPackage($this->templatePath('template_01_simple_variables.odt'));

        try {
            $manager = new PageLayoutManager($package->context());

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Master page "Missing" was not found in styles.xml.');
            $manager->setMargins('1cm', '1cm', '1cm', '1cm', 'Missing');
        } finally {
            $package->cleanup();
        }
    }

    private function standardPageLayoutProperties(OdtPackage $package): DOMElement
    {
        $xpath = new DOMXPath($package->context()->stylesDom());
        $xpath->registerNamespace('style', self::STYLE_NS);

        $master = $xpath->query('//style:master-page[@style:name="Standard"]')->item(0);
        self::assertInstanceOf(DOMElement::class, $master);

        $layoutName = $master->getAttributeNS(self::STYLE_NS, 'page-layout-name');
        self::assertNotSame('', $layoutName);

        $properties = $xpath->query(
            sprintf('//style:page-layout[@style:name="%s"]/style:page-layout-properties', $layoutName)
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $properties);

        return $properties;
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }
}
