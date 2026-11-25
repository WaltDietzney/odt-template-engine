<?php
require dirname(__DIR__, 3) . '/vendor/autoload.php';
$path = dirname(__DIR__) . '/v04/native/assets/lucide-mail.svg';
$validator = new OdtTemplateEngine\Mapping\FrameImageReplacementPreflightValidator();
$method = new ReflectionMethod($validator, 'isSupportedImage');
echo json_encode(['runtime' => PHP_VERSION, 'native_metadata' => getimagesize($path),
    'preflight_accepts_svg' => $method->invoke($validator, $path)], JSON_PRETTY_PRINT) . "\n";
