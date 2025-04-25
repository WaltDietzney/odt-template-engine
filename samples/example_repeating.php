<?php

require_once '../vendor/autoload.php';
# require_once '../vendor/odtoffice/OdtTemplate.php'; // Pfad zu deiner OdtTemplate-Klasse

use OdtTemplateEngine\OdtTemplate;

try {
    $template = new OdtTemplate('templates/template_repeating.odt');
    $template->load();

    // Einzelwerte (falls vorhanden)
    $template->setValues([
        'title' => 'Personenliste',
    ]);

    // Wiederholungsdaten
    $template->setRepeating('items', [
        ['name' => 'Alice', 'age' => 30],
        ['name' => 'Bob', 'age' => 25],
        ['name' => 'Charlie', 'age' => 35],
    ]);

    $template->setImage('foto','../assets/Logo.png',[
        'width'  => '6cm',
        'anchor' => 'page',
        'wrap'   => 'parallel',
    ]);

    $template->save('output/output_repeating.odt');
    echo "Die Datei 'output_repeating.odt' wurde erfolgreich erstellt.\n";

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}
