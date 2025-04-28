<?php

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;

// Template laden
$template = new OdtTemplate('templates/template_richtext.odt');
$template->load();

//RichText erstellen
$block = (new RichText())
    ->addText("Herzlich willkommen ", ['bold' => true,'font-size'=>'6pt','font-family'=>'MathJax_Typewriter'])
    ->addText("im System", ['italic' => true])->addLineBreak()
    ->addBulletList([
        "ToDo 1: Prüfen",
        "ToDo 2: Absenden",
        "ToDo 3: Fertig"
    ], ['color' => '#0000fc']);


$template->setElement('block', $block);
$text = (new RichText())
    ->addText("Willkommen ", ['bold' => true])
    ->addLineBreak()
    ->addText("im System!", ['color' => '#00aa00'])
    ->addLineBreak()
    ->addBulletList([
        "Eintrag 1",
        "Eintrag 2",
        "Eintrag 3"
    ]);


$run = (new RichText())
->addText('Das ist ein fette Formatierung! ', ['bold'=> true])
->addText('Das ist eine kursive Formatierung! ', ['italic'=> true])
->addText('Das ist bunt!', ['color'=> '#0000cc']);

    // Komplexes Text-Element
$template->setElement('textblock', $text);
$template->setElement('textrun', $run);

// Einfache Platzhalter setzen
$template->setValues([
    'name' => 'Max Mustermann',
    'email' => 'MAX@MUSTERMANN.COM',
    'kommentar' => "Dies ist Zeile 1\nUnd das ist Zeile 2.",
    'is_admin' => true,
    'geb' => date('09.10.1967')]);

// foreach
$template->setRepeating('rollen', [
    ['rolle' => 'Admin', 'status' => 'aktiv'],
    ['rolle' => 'Editor', 'status' => 'inaktiv'],
    ['rolle' => 'Viewer', 'status' => 'aktiv']
]);

// Bild
// $template->setImage('bild', '../assets/Logo.png', [
//     'width' => '4cm',
//     'anchor' => 'as-char',
//     'wrap' => 'right'
// ]);



$template->save('output/output_richtext.odt');

echo "✅ Datei erstellt: output/output_richtext.odt\n";
