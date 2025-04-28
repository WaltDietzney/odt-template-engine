<?php

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;

// Template laden
$tpl = new OdtTemplate('templates/template_tab-test.odt');
$para = new Paragraph();
$para->addTabsWithTexts([
    ['position' => 3.0, 'alignment' => 'left', 'text' => 'Code'],
    ['position' => 7.0, 'alignment' => 'center', 'text' => 'Description'],
    ['position' => 11.0, 'alignment' => 'right', 'text' => 'Price'],
]);

$image = new ImageElement('../assets/Logo.png', ['abchor'=>'paragraph','wrap'=>'left', 'align'=>'right']);

// $para->addLineBreak();
// $para->addText('Das ist hier mit normaler Farbe, aber fett ',['bold'=> true])
// ->addText('hier wird es blau und kursiv ',['italic'=>true, 'color'=>'#0e0f69'])
// ->addText('und in ganz kleiner Schrift',['font-size'=>'8pt'])
// ->addParagraphBreak(3)
// ->addText('und man kann sogar Bilder hinzufügen. Dazu benutzt man addElement() in Kombination mit new ImageElement()')
// ->addLineBreak(3)
// ->addText('Erzeuge ein neues image Element mit ') ->addText('$image = new ImageElement(\'path\toFile\') ',['italic'=>true, 'color' => '#db250d'])
// ->addLineBreak(3)
// ->addText('Dann füge das Bild den Paragrapen mit ')->addText('$par = addElement(\'$image\') ',['italic'=>true, 'color' => '#db250d'])
// ->addText(' hinzu')
// ->addLineBreak(2)
// ->addTabularLines(
//     [
//         ['Product', 'Description', 'Price'],
//         ['Apple', 'Fresh fruit', '1.00 €'],
//         ['Banana', 'Tropical fruit', '0.80 €']
//     ],
//     [
//         ['position' => 3.0, 'alignment' => 'left'],
//         ['position' => 8.0, 'alignment' => 'left'],
//         ['position' => 13.0, 'alignment' => 'right']
//     ],
//     ['bold' => true]
// )
// ->addElement($image);
$para = new Paragraph();
$para->addTabularLines(
    [
        ['Name', 'Position', 'Gehalt'],
        ['Anna', 'Developer', '3.000 €'],
        ['Ben', 'Designer', '2.800 €']
    ],
    [
        ['position' => 4.0, 'alignment' => 'left'],
        ['position' => 10.0, 'alignment' => 'left'],
        ['position' => 15.0, 'alignment' => 'right']
    ],
    ['bold' => true],
    [
        2 => ['color' => '#007700']
    ]
);


$tpl->setElement('product_table', $para);

// Speichern
$tpl->save('output/output_tabulator-test.odt');
echo "✅ Datei gespeichert: output/output_tabulator-test.odt\n";
