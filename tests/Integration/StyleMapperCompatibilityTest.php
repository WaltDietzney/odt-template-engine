<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleMapperCompatibilityTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testParagraphRegistrationRemainsAvailableThroughFacade(): void
    {
        $style = '01E_Legacy_' . bin2hex(random_bytes(4));
        $definition = ['margin-left' => '2cm'];

        StyleMapper::registerParagraphStyle($style, $definition);

        self::assertSame($definition, StyleMapper::getParagraphStyles()[$style]);
    }

    #[RunInSeparateProcess]
    public function testEquivalentRegistrationIsCompatibleAndFirstDefinitionWinsOnConflict(): void
    {
        $style = '01E_FirstWins_' . bin2hex(random_bytes(4));
        $first = ['margin-left' => '3cm'];

        StyleMapper::registerParagraphStyle($style, $first);
        StyleMapper::registerParagraphStyle($style, ['margin-left' => '4cm']);

        self::assertSame($first, StyleMapper::getParagraphStyles()[$style]);
    }

    #[RunInSeparateProcess]
    public function testParagraphRegistrationRemainsInAggregatedFacadeResults(): void
    {
        $style = '01E_Aggregated_' . bin2hex(random_bytes(4));
        $definition = ['margin-left' => '5cm'];

        StyleMapper::registerParagraphStyle($style, $definition);

        self::assertSame($definition, StyleMapper::getRegisteredStyles()[$style]);
        self::assertSame($definition, StyleMapper::getAllRegisteredStyles()['paragraph'][$style]);
    }

    #[RunInSeparateProcess]
    public function testStyleWriterStillMaterializesFacadeParagraphRegistration(): void
    {
        $style = '01E_Writer_' . bin2hex(random_bytes(4));
        StyleMapper::registerParagraphStyle($style, ['margin-left' => '6cm']);
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML(
            '<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'
        ));

        StyleWriter::writeAllStyles($dom);

        $styles = $dom->saveXML();
        self::assertIsString($styles);
        self::assertStringContainsString('style:name="' . $style . '"', $styles);
        self::assertStringContainsString('fo:margin-left="6cm"', $styles);
    }

    #[RunInSeparateProcess]
    public function testOdtTemplateFinalizationDoesNotImportLegacyParagraphStyle(): void
    {
        $style = '01E_Leak_' . bin2hex(random_bytes(4));
        StyleMapper::registerParagraphStyle($style, ['margin-left' => '7cm']);
        $output = sys_get_temp_dir() . '/odt-stylemapper-' . bin2hex(random_bytes(6)) . '.odt';

        try {
            $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt');
            $template->save($output);

            $archive = new ZipArchive();
            self::assertTrue($archive->open($output) === true);
            try {
                $styles = $archive->getFromName('styles.xml');
                self::assertIsString($styles);
                self::assertStringNotContainsString($style, $styles);
            } finally {
                $archive->close();
            }
        } finally {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }
}
