<?php
/**
 * Sample 16: Create a Paragraph with Tabs, Lines, and an Image in an ODT Template
 *
 * Description:
 * This script loads an ODT template, constructs a paragraph that includes tabbed text,
 * multiple lines, styled text, and an image. It also demonstrates how to add tabular data 
 * with custom alignment and colors. Finally, it saves the generated document to a new file.
 *
 * Author: Walter Dietz
 * Requires: OdtTemplateEngine library
 */

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;

//require '../vendor/autoload.php';

// Load the ODT template
$tpl = new OdtTemplate('samples/templates/template_16_tabsBasic.odt');

// Create the first paragraph
$para = new Paragraph();

// Add tab stops with texts at specific positions
$para->addTabsWithTexts([
    ['position' => 3.0, 'alignment' => 'left', 'text' => 'Code'],
    ['position' => 7.0, 'alignment' => 'center', 'text' => 'Description'],
    ['position' => 11.0, 'alignment' => 'right', 'text' => 'Price'],
]);

// Create an image element
$image = new ImageElement('assets/Logo.png', [
    'abchor' => 'paragraph',
    'wrap' => 'left',
    'align' => 'right'
]);

// Add styled text and line breaks
$para->addLineBreak();
$para->addText('This text is normal color but bold ', ['bold' => true])
    ->addText('this part is blue and italic ', ['italic' => true, 'color' => '#0e0f69'])
    ->addText('and this part is in very small font size', ['font-size' => '8pt'])
    ->addLineBreak(3)
    ->addText('You can even add images. To do that, use addElement() combined with new ImageElement().')
    ->addLineBreak(3)
    ->addText('Create a new image element with ')
    ->addText('$image = new ImageElement(\'path\toFile\') ', ['italic' => true, 'color' => '#db250d'])
    ->addLineBreak(3)
    ->addText('Then add the image to the paragraph with ')
    ->addText('$para->addElement($image) ', ['italic' => true, 'color' => '#db250d'])
    ->addText('method.')
    ->addLineBreak(2)

// Add a tabular structure with headers and sample product data
    ->addTabularLines(
        [
            ['Product', 'Description', 'Price'],
            ['Apple', 'Fresh fruit', '1.00 €'],
            ['Banana', 'Tropical fruit', '0.80 €']
        ],
        [
            ['position' => 3.0, 'alignment' => 'left'],
            ['position' => 8.0, 'alignment' => 'left'],
            ['position' => 13.0, 'alignment' => 'right']
        ],
        ['bold' => true]
    )

// Add the image element at the end of the paragraph
    ->addElement($image);

// Create a second paragraph for employee data
$para2 = new Paragraph();

// Add tabular lines to the second paragraph
$para2->addTabularLines(
    [
        // First sub-array defines the headers: Name, Position, Salary
        ['Name', 'Position', 'Salary'],
        
        // Following arrays define the content rows
        ['Anna', 'Developer', '3,000 €'],
        ['Ben', 'Designer', '2,800 €']
    ],
    [
        // Define tab positions and alignment for each column
        ['position' => 4.0, 'alignment' => 'left'],   // Name column: aligned left at 4.0 cm
        ['position' => 10.0, 'alignment' => 'left'],  // Position column: aligned left at 10.0 cm
        ['position' => 15.0, 'alignment' => 'right']  // Salary column: aligned right at 15.0 cm
    ],
    [
        // Apply bold formatting to the text
        'bold' => true
    ],
    [
        // Apply optional cell styles:
        // Row index 2 (the second data row: Ben) gets green text color
        2 => ['color' => '#007700']
    ]
);

// Add an additional styled text to the second paragraph (optional example)
$para2->addLineBreak(2) // Add 2 line breaks for spacing
    ->addText('Employee overview created successfully.', [
        'italic' => true,
        'color' => '#004488',
        'font-size' => '10pt',
        'font-family' => 'Arial'
    ]);

// Optionally you could add more dynamic content or elements (like images, other paragraphs, etc.)

// Combine the paragraphs into a RichText element
$rich = (new RichText())
    ->addParagraph($para)
    ->addParagraphBreak(2)
    ->addParagraph($para2);

// Replace the placeholder 'product_table' with the generated rich text
$tpl->setElement('product_table', $rich);

// Save the modified document
$tpl->save('samples/output/output_16_tabsBasic.odt');

