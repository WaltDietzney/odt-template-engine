<?php
require dirname(__DIR__, 3) . '/vendor/autoload.php';
$t = new OdtTemplateEngine\PageLayoutOdtTemplate(__DIR__ . '/roundtrip/shared-before.odt');
$t->replaceBitmapFillByName('FillA', __DIR__ . '/assets/b/panel.svg');
$t->replaceBitmapFillByName('HeaderFill', __DIR__ . '/assets/b/panel.svg');
$t->replaceImageByName('DirectA', __DIR__ . '/assets/b/panel.svg', ['width' => '4cm', 'height' => '2cm']);
$t->save(__DIR__ . '/writer-rebound.odt');
$t->cleanup();
