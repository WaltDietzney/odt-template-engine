
<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\AbstractOdtTemplate;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Utils\StyleMapper;

$template = new OdtTemplate('templates/template_table_styled.odt');
$template->load();

$table = new RichTable();

// 🔴 Erste Zelle mit rotem Hintergrund und fettem Text
$cell1 = (new RichTableCell('Zelle A1'))
->setStyle([
    'background' => '#ffdddd',
    'padding' => '1cm',
    'weight' => 'bold',
    'color' => '#ff0000',
    'align' => 'right'
]);


// 🔵 Zweite Zelle mit blauem Hintergrund und Kursivschrift
$cell2 = (new RichTableCell('Zelle A2'))
->setStyle([
    'background' => '#ffdddd',
    'padding' => '1cm',
    'weight' => 'bold',
    'color' => '#ff0000',
    'align' => 'right'
]);

 
// Zweite Zeile mit normalen Stil
$cell3 = new RichTableCell('Zelle B1');
$cell4 = new RichTableCell('Zelle B2');

// 🧱 Tabelle aufbauen
$table
    ->addRow([$cell1, $cell2])
    ->addRow([$cell3, $cell4]);

// 🔄 Platzhalter ersetzen
$template->setElement('tableblock', $table);

// 💾 Datei speichern
$template->save('output/output_test_table.odt');

// 🧪 Debug-Ausgabe
echo "✅ Tabelle mit Formatierung erfolgreich generiert.\n";
$styles = StyleMapper::getRegisteredTableCellStyles();
echo "🎨 Registrierte Zellen-Styles:\n";
print_r($styles);

