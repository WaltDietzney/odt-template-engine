<?php

//require_once 'vendor/autoload.php'; // Dein Autoloader

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\ListElement;

// 1. Lade dein ODT Template
$tpl = new OdtTemplate('samples/templates/template_18_ListStyles.odt');

// Hauptliste: nummeriert
$list = new ListElement('numbered');

// 1. Einführung
$list->addItem((new Paragraph())->addText('Einführung'));

// 🔹 Subliste unter 1: Bullet
$introSublist = new ListElement('bullet');
$introSublist->addItem((new Paragraph())->addText('Ziel'));
$introSublist->addItem((new Paragraph())->addText('Nutzen'));

// einfügen als Sublist
$list->addItem($introSublist);

// 2. Hauptteil
$list->addItem((new Paragraph())->addText('Hauptteil'));

// 🔹 Subliste unter 2
$mainSublist = new ListElement('bullet');
$mainSublist->addItem((new Paragraph())->addText('Punkt A'));

// 🔹 Unterliste unter Punkt A
$punktAList = new ListElement('bullet');
$punktAList->addItem((new Paragraph())->addText('Unterpunkt A.1'));
$punktAList->addItem((new Paragraph())->addText('Unterpunkt A.2'));

$mainSublist->addItem($punktAList);
$mainSublist->addItem((new Paragraph())->addText('Punkt B'));

$list->addItem($mainSublist);

// 3. Fazit mit Stil
$list->addItem(
    (new Paragraph())->addText('Fazit', ['bold' => true])
);



$tpl->setElement('my_list', $list);
$tpl->save('samples/output/output_18_ListStyles.odt');

echo "✅ Dokument mit Liste erstellt!\n";
