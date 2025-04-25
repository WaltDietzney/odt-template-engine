<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Utils\StyleMapper;

$template = new OdtTemplate('templates/template_image.odt');

$imagePath = '../assets/logo.png';
$img = new ImageElement('../assets/Logo.png',[
    'align'  => 'right',       // Mapped to style:horizontal-pos
    'anchor' => 'paragraph',   // Mapped to text:anchor-type + style:horizontal-rel
    'wrap'   => 'none',        // No text flow
    'width'  => '4cm',
    'height' => '3cm'
]);

// $img -> setStyle([
//     'align'  => 'right',       // Mapped to style:horizontal-pos
//     'anchor' => 'paragraph',   // Mapped to text:anchor-type + style:horizontal-rel
//     'wrap'   => 'none',        // No text flow
//     'width'  => '4cm',
//     'height' => '3cm'
// ]);


$template->setElement('logo', $img);

// $template->setImage('logo',$imagePath,[
//     'width' => '6cm',
//     'anchor' => 'paragraph',
//     'wrap' => 'none',
// ]);
$template->save('output/output_image.odt');

print_r(StyleMapper::getRegisteredImageStyles());


echo "✅ ODT mit Bild erfolgreich erzeugt.\n";
