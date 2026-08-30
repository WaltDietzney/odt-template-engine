<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextCharacterizationTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/odt-style-context-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory . '/*') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($this->temporaryDirectory);
    }

    #[RunInSeparateProcess]
    public function testExplicitParagraphRegistrationLeaksIntoLaterDocumentInSameProcess(): void
    {
        $styleName = 'StyleContextLeak_' . bin2hex(random_bytes(4));

        StyleMapper::registerParagraphStyle($styleName, [
            'margin-left' => '6.75cm',
        ]);

        $stylesA = $this->readStyles($this->saveUnmodifiedTemplate('A'));
        self::assertStringContainsString($styleName, $stylesA);

        $stylesB = $this->readStyles($this->saveUnmodifiedTemplate('B'));
        self::assertStringContainsString(
            $styleName,
            $stylesB,
            'Current behavior: explicit StyleMapper registrations remain process-wide and are written into later documents.'
        );
    }

    private function saveUnmodifiedTemplate(string $suffix): string
    {
        $output = $this->temporaryDirectory . '/document-' . $suffix . '.odt';
        $template = new OdtTemplate(
            dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt'
        );

        try {
            $template->save($output);
        } finally {
            $template->cleanup();
        }

        return $output;
    }

    private function readStyles(string $path): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $styles = $zip->getFromName('styles.xml');
            self::assertIsString($styles);
            return $styles;
        } finally {
            $zip->close();
        }
    }
}
