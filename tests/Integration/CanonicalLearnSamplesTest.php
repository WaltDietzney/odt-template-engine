<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class CanonicalLearnSamplesTest extends TestCase
{
    public function testL03ExplicitlyAssignedEmptyCollectionRemovesRepeatablePrototype(): void
    {
        $template = new OdtTemplate(
            dirname(__DIR__, 2) . '/samples/templates/template_L03_repeating_content.odt'
        );
        $outputPath = sys_get_temp_dir() . '/odt-l03-empty-' . bin2hex(random_bytes(6)) . '.odt';

        try {
            $template->assign([
                'order_number' => 'SO-EMPTY',
                'customer' => 'No line items',
            ]);
            $template->assignRepeating('items', []);
            $template->render();
            $template->save($outputPath);

            $archive = new ZipArchive();
            self::assertSame(true, $archive->open($outputPath));
            $content = $archive->getFromName('content.xml');
            $archive->close();

            self::assertIsString($content);
            self::assertStringContainsString('SO-EMPTY', $content);
            self::assertStringNotContainsString('{{#foreach:items}}', $content);
            self::assertStringNotContainsString('{{#endforeach}}', $content);
            foreach (['Notebook', 'Fountain pen', 'Ink bottle', 'PAP-01', 'WR-14', 'INK-03'] as $itemText) {
                self::assertStringNotContainsString($itemText, $content);
            }
            self::assertStringContainsString('does not invent an empty-state message', $content);
        } finally {
            $template->cleanup();
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }
    }
}
