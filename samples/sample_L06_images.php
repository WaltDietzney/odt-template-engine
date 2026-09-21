<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L06_images.odt');

// LibreOffice authored this named frame; PHP replaces its image resource.
$template->replaceImageByName(
    'LearnTemplatePosition',
    __DIR__ . '/../assets/Logo.png',
    ['width' => '4.5cm', 'height' => '2.4cm']
);

// PHP creates a separate native frame as part of this generated ODT element.
$generatedImage = new ImageElement(__DIR__ . '/../assets/banner.png', [
    'width' => '4.5cm',
    'anchor' => 'as-char',
]);
$template->setElement('generated_image', $generatedImage);

$template->save(__DIR__ . '/output/output_L06_images.odt');
