<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

/** Records that lifecycle finalization no longer imports global style state. */
final class D5GLifecycleCompatibilityNarrowingCharacterizationTest extends TestCase
{
    public function testLegacyParagraphLifecycleDoesNotNeedRegistryFinalization(): void
    {
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/sample_textfeld.odt');
        $template->assign(['test1' => new Paragraph()]);
        $template->render();

        $output = sys_get_temp_dir() . '/d5g-boundary-' . bin2hex(random_bytes(5)) . '.odt';
        try {
            $template->save($output);
            self::assertFileExists($output);
        } finally {
            $template->cleanup();
            if (is_file($output)) {
                unlink($output);
            }
        }
    }
}
