<?php
require dirname(__DIR__, 3) . '/vendor/autoload.php';
$path = __DIR__ . '/assets/a/panel.svg';
$info = OdtTemplateEngine\Utils\ImageInfo::fromPath($path);
$validator = new OdtTemplateEngine\Mapping\FrameImageReplacementPreflightValidator();
$method = new ReflectionMethod($validator, 'isSupportedImage');
echo json_encode([
    'php' => PHP_VERSION, 'native_svg_metadata' => @getimagesize($path),
    'native_square_icon_metadata' => @getimagesize(dirname(__DIR__) . '/v04/native/assets/lucide-mail.svg'),
    'shared_metadata' => ['width' => $info->width, 'height' => $info->height, 'mime' => $info->mime],
    'proportional_dimensions' => $info->completeDimensions('40mm', null),
    'preflight_accepts' => $method->invoke($validator, $path),
], JSON_PRETTY_PRINT) . "\n";
