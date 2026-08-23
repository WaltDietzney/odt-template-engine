<?php
/**
 * Sample 7 - Paragraphs and Tab Stops
 *
 * Demonstrates the creation of complex paragraph structures using:
 * 1. Custom paragraph styles (e.g., margins, alignment).
 * 2. Tab stops to align multiple text elements horizontally.
 * 3. Inserting hyperlinks within a paragraph.
 *
 * This sample builds a structured "Contacts List" using RichText and multiple Paragraph elements.
 */

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;

// 1️⃣ Load and prepare the template
$template = new OdtTemplate('samples/templates/template_07_contactList.odt');
$template->load();

// Create a new RichText container for multiple paragraphs
$rich = new RichText();

// 2️⃣ Create a paragraph block for the title with custom spacing
$para = new Paragraph('Custom', [
    'margin-top'    => '1cm',
    'margin-bottom' => '0.5cm',
    'text-align'    => 'center',
]);

// Optional: ensure the named paragraph style exists before using it.
// $template->ensureParagraphStylesExist([
//     'Custom' => [
//         'margin-top' => '1cm',
//         'margin-bottom' => '0.5cm',
//         'text-align' => 'center',
//     ]
// ]);

// 3️⃣ Add a simple title line
$para->addText('📋 Contacts List', [
    'bold'      => true,
    'font-size' => '14pt',
]);

// Create another paragraph for the tabular data
$para2 = new Paragraph();

// 4️⃣ Define tab stops (position in cm and alignment)
$tabStops = [
    ['position' => 3.0, 'alignment' => 'left'],
    ['position' => 8.0, 'alignment' => 'center'],
    ['position' => 16.0, 'alignment' => 'right'],
];

// 5️⃣ Define table data (header and rows)
$rows = [
    ['Name', 'Role', 'Email'],
    ['Alice Smith', 'Developer', 'alice@example.com'],
    ['Bob Müller', 'Designer', 'bob@example.com'],
    ['Carol König', 'Project Manager', 'carol@example.com'],
];

// Add the tabular header and data rows
$para2->addTabularLines(
    $rows,
    $tabStops,
    ['bold' => true, 'underline' => true]
);

// 6️⃣ Create a third paragraph with a hyperlink
$para3 = new Paragraph();

$para3
    ->addTabularLines(
        ['Visit our site', '', ''],
        $tabStops,
        ['italic' => true]
    )
    // Insert hyperlink at the third tab position
    ->addTab() // Ensure cursor is at beginning
    ->addTabStop(3.0, 'left', '→ ')
    ->addHyperlink(
        'Company Website',
        'https://github.com/WaltDietzney/odt-template-engine',
        ['color' => '#0000ff', 'underline' => true]
    )
    ->addLineBreak();

// 7️⃣ Assemble all paragraphs into the RichText block
$rich
    ->addParagraph($para)
    ->addParagraphBreak(2)
    ->addParagraph($para2)
    ->addParagraphBreak(2)
    ->addParagraph($para3)
    ->addParagraphBreak(2);

// 8️⃣ Insert the RichText block into the template
$template->setElement('contacts_block', $rich);

// 9️⃣ Save the final document
$template->save('samples/output/output_07_contactList.odt');
