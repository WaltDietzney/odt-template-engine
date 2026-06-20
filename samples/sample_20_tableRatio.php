<?php

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;

// Lade das Template
$template = new OdtTemplate('samples/templates/template_20_tableRatio.odt');
$template->load();

// Erstelle eine Tabelle mit Spaltenverhältnis 2:1:1
$table = new RichTable();
$table->setColumnWidthRatios([2, 1, 1]);

// Erste Zeile (automatisch mit colspan 6 / 3 / 3)
$table->addRow([
    (new RichTableCell('Spalte A'))->setStyle(['background' => '#ffe0b2', 'text-align' => 'center','color' => '#eb4034']),
    (new RichTableCell('Spalte B'))->setStyle(['background' => '#ffe0b2', 'text-align' => 'center','color' => '#eb4034']),
    (new RichTableCell('Spalte C'))->setStyle(['background' => '#ffe0b2', 'text-align' => 'center','color' => '#eb4034']),
]);

// Weitere Zeilen
$table->addRow([
    new RichTableCell('Alpha'),
    new RichTableCell('Beta'),
    new RichTableCell('Gamma'),
]);

$table->addRow([
    new RichTableCell('X'),
    new RichTableCell('Y'),
    new RichTableCell('Z'),
]);

// Setze das Element im Template
$template->setElement('tableblock', $table);

// Speichere das Ergebnis
$template->save('samples/output/output_20_tableRatio.odt');
