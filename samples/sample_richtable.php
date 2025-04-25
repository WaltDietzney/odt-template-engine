<?php
require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\OdtElement;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;

// Load the template
$template = new OdtTemplate('templates/template_table.odt');
$template->load();

// Helper function for creating checkboxes (✔ or ☐)
function checkbox(bool $checked): string {
    return $checked ? '✔' : '☐';
}

// Create a RichText cell with a title and a checkbox
function makeRichCell(string $title, bool $checked): RichText {
    $rich = new RichText();
    $rich->addParagraph((new Paragraph())
        ->addText($title, ['bold' => true]) // Title text with bold
        ->addLineBreak() // Add a line break
        ->addText('Done: ' . checkbox($checked), ['italic' => true]) // "Done" status in italics
    );
    return $rich;
}

// Build the table
$table = new RichTable();

// Add headers
$table->addRow(['Task', 'Status']);

// Add rows with rich text and checkboxes
$table->addRow([
    makeRichCell('HTML Importer', true),
    makeRichCell('Checkbox Logic', true)
]);

$table->addRow([
    makeRichCell('Table Functions', true),
    makeRichCell('Test Successful', true)
]);

$table->addRow([
    makeRichCell('Colors & Formatting', false),
    makeRichCell('Still Pending', false)
]);

// Set the table element in the template
$template->setElement('table', $table);

// Set dynamic values (optional)
$template->setValues(['is_done' => true]);

// Save the generated document
$template->save('output/output_table_test.odt');

echo "✅ Table successfully added\n";
