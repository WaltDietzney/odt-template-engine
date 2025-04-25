<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('templates/template_basic.odt');
$template->load();

$template->setValues([
    'name' => 'Anna Beispiel',
    'datum' => date('d.m.Y')
]);

$template->setRepeating('items', [
    ['produkt' => 'Kaffee', 'preis' => '4,99 €'],
    ['produkt' => 'Tee',    'preis' => '3,49 €'],
    ['produkt' => 'Kakao',  'preis' => '2,99 €']
]);


$template->setImage('foto','../assets/Logo.png', [
    'width' => '1cm'
]);

$template->save('output/output_basics.odt');

echo "✅ Dokument erfolgreich erstellt: output.odt\n";
