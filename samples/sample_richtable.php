<?php

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;

// Template laden
$template = new OdtTemplate('templates/template_table.odt');
$template->load();

// Checkbox-Helfer (✔ oder ☐)
function checkbox(bool $checked): string {
    return $checked ? '✔' : '☐';
}

// Zelle mit RichText
function makeRichCell(string $title, bool $checked): RichText {
    $rich = new RichText();
    $rich->addParagraph((new Paragraph())
        ->addText($title, ['bold' => true])
        ->addLineBreak()
        ->addText('Erledigt: ' . checkbox($checked), ['italic' => true])
    );
    return $rich;
}

// Tabelle aufbauen
$table = new RichTable();
$table->addRow(['Aufgabe', 'Status']);
$table->addRow([
    makeRichCell('HTML Importer', true),
    makeRichCell('Checkbox Logik', true)
]);
$table->addRow([
    makeRichCell('Tabellenfunktionen', true),
    makeRichCell('Test erfolgreich', true)
]);
$table->addRow([
    makeRichCell('Farben & Format', false),
    makeRichCell('Noch offen', false)
]);

$template->setElement('tabelle', $table);

$template->setValues(['is_done'=>true]);

$template->save('output/output_table_test.odt');
echo "✅ Tabelle erfolgreich eingefügt\n";
