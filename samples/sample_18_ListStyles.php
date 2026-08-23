<?php

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;

// Load the ODT template.
$tpl = new OdtTemplate('samples/templates/template_18_ListStyles.odt');

// Create the numbered top-level list.
$list = new ListElement('numbered');
$list->addItem((new Paragraph())->addText('Einführung'));

// Add a bullet sublist below the first item.
$introSublist = new ListElement('bullet');
$introSublist->addItem((new Paragraph())->addText('Ziel'));
$introSublist->addItem((new Paragraph())->addText('Nutzen'));
$list->addItem($introSublist);

// Add the second top-level item and a nested bullet list.
$list->addItem((new Paragraph())->addText('Hauptteil'));

$mainSublist = new ListElement('bullet');
$mainSublist->addItem((new Paragraph())->addText('Punkt A'));

// Add another nesting level below "Punkt A".
$punktAList = new ListElement('bullet');
$punktAList->addItem((new Paragraph())->addText('Unterpunkt A.1'));
$punktAList->addItem((new Paragraph())->addText('Unterpunkt A.2'));

$mainSublist->addItem($punktAList);
$mainSublist->addItem((new Paragraph())->addText('Punkt B'));
$list->addItem($mainSublist);

// Finish with a styled top-level item.
$list->addItem(
    (new Paragraph())->addText('Fazit', ['bold' => true])
);

$tpl->setElement('my_list', $list);
$tpl->save('samples/output/output_18_ListStyles.odt');

echo "✅ List style sample generated successfully.\n";
