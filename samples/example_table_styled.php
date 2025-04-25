<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Utils\StyleMapper;

// Template laden
$template = new OdtTemplate('templates/template_table_styled.odt');
$template->load();

$table = new RichTable();

// Kopfzeile
$table->addRow([
    new RichTableCell('Aufgabe', ['background' => '#ddeeff', 'text-align' => 'center', 'border' => '0.05pt solid #000000']),
    new RichTableCell('Status', ['background' => '#ddeeff', 'text-align' => 'center', 'border' => '0.05pt solid #000000'])
]);


$c1 = new Paragraph();
$c1 ->addText('HtmlImport');

$r = new RichText();

// Inhalte mit RichText oder Paragraphs
$table->addRow([
    new RichTableCell( $r->addText('Html', ['text-align'=>'center', 'bold'=>true]),['background' => '#c8facc','align'=>'end']),
    new RichTableCell('✔', ['background' => '#c8facc', 'text-align' => 'center']),
]);

$table->addRow([
    new RichTableCell('Tabellen-Styling', ['background' => '#ffffff']),
    new RichTableCell('✔', ['background' => '#c8facc', 'text-align' => 'center']),
]);

$table->addRow([
    new RichTableCell('Noch offen', ['background' => '#ffffff']),
    new RichTableCell('☐', ['background' => '#fce3e3', 'align' => 'center']),
]);

// Platzhalter ersetzen
$template->setElement('tableblock', $table);

error_log("🔍 Tabelle wurde gesetzt. Registrierte Zellstyles:");
error_log(print_r(StyleMapper::getRegisteredTableCellStyles(), true));


// Datei speichern
$template->save( 'output/output_table_styled.odt');

echo "✅ Tabelle mit Formatierung erfolgreich generiert.\n";
echo print_r(StyleMapper::getRegisteredTableCellStyles());
