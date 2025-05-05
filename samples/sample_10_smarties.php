<?php

/**
 * Sample 10 – Smart Business Letter Example
 *
 * This advanced sample demonstrates how to generate a dynamic business letter
 * using the OdtTemplateEngine for PHP. It includes:
 * - Conditional logic and control structures (e.g., if/foreach)
 * - RichText elements with mixed formatting
 * - Bullet lists, key-value blocks, and tabular layouts
 * - Placeholder-based data injection
 * - Custom metadata and embedded images
 *
 * Ideal for generating structured documents like invoices, offers, or correspondence.
 *
 * Author: Walter Dietz
 * Template used: samples/templates/template_10_smarties.odt
 * Output:        samples/output/output_10_smarties.odt
 */


use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;

$template = new OdtTemplate('samples/templates/template_10_smarties.odt');
$template->load();


// Set document metadata (project-related and descriptive)
$template->setMeta([
    'title' => 'ODT Template Engine Demo Document',
    'subtitle' => 'Smart Template-Based Business Letter Generation', // ← hinzugefügt
    'subject' => 'Demonstration of control structures, filters and foreach elements',
    'description' => 'This document was auto-generated using the WaltDietzney ODT Template Engine for PHP.',
    'keywords' => 'ODT, template, filter, template foreach, template if, document automation',
    'initial_author' => 'Template Engine Example Script',
    'author' => 'WaltDietzney',
    'language' => 'en-US',
    'creation_date' => date(DATE_W3C),
    'date' => date(DATE_W3C),
    'editing_cycles' => '1',
    'editing_duration' => 'PT5M',
    'generator' => 'ODT Template Engine (PHP) - v1.0',
    'project_name' => 'Invoice Generation Demo',
    'project_version' => 'v1.2.0',
    'client' => 'Acme Corp.',
]);


// Insert a sample image
$template->setImage('image', 'assets/WaltDietzney.png', [
    'width' => '2cm',
    'wrap' => 'left',
    'align' => 'right',
    'anchor' => 'paragraph'
]);

// Custom data for the template
$custom = [
    'name' => 'Doe',
    'vname' => 'John',
    'address' => 'Maple Street 123 - D12345 Mapletown',
    'mail' => 'John@doe.com',
    'town' => 'Mapletown',
    'phone' => '01234 5678910',
    'datum' => '2025-04-26',
];

// Assign basic variables
$template->assign([
    'name' => $custom['name'],
    'vname' => $custom['vname'],
    'address' => '📌 ' . $custom['address'],
    'mail' => '✉️ ' . $custom['mail'],
    'town' => $custom['town'],
    'phone' => '📞 ' . $custom['phone'],
    'datum' => $custom['datum'],
    'envelopeAdress' => $custom['vname'] . ' ' . $custom['name'] . ' ' . $custom['address'],
    'gender' => 'female', // Possible values: 'male', 'female', or empty
    'avname' => 'Jane',
    'aname' => 'Doerson',
    'astreet' => 'Maple Leaf 456',
    'atown' => '12345 Berlin',
    'subject' => 'How to use control structures to manipulate advanced documents',
]);

// Assign a repeating block for purchased products
$template->assignRepeating('items', [
    ['product' => 'Laptop', 'price' => '899.00'],
    ['product' => 'Smartphone', 'price' => '499.00'],
    ['product' => 'Wireless Earbuds', 'price' => '149.00'],
    ['product' => 'Smartwatch', 'price' => '199.00'],
]);

// Assign a repeating block for fresh fruits offers
$template->assignRepeating('fruits', [
    ['product' => 'Strawberries', 'price' => '3.99'],
    ['product' => 'Blueberries', 'price' => '5.49'],
    ['product' => 'Cherries', 'price' => '6.29'],
    ['product' => 'Watermelon', 'price' => '4.99'],
]);

// Create a complex RichText block for {{rich1}}
$rich1 = new RichText();



// Paragraph 1: Introduction
$par1 = new Paragraph();
$par1->addText('Thank you for choosing us! We are delighted to have you as part of our community.');

// Paragraph 2 Best practice hints as bullet list
$par2 = new Paragraph();
$par2->addText('🔎 Best Practices for Template Design:', ['bold' => true]);

// bullet List
$bList = [
    'Use tables for precise layout (e.g., for tabular data or multiple columns.)',
    'Avoid placing variables across multiple tags (e.g., {{name}} inside a <text:span>).',
    'Prefer structured control blocks like {{#foreach}} or {{#if}} for clarity.',
    'Keep logical conditions simple and readable.',
    'Insert tabulators only inside clean table cells for best results.'
];


// Paragraph 3: Tabular fresh fruits offer
$tabularRows = [
    ['Product', 'Price'],
    ['Strawberries', '3.99 €'],
    ['Blueberries', '5.49 €'],
    ['Cherries', '6.29 €'],
    ['Watermelon', '4.99 €'],
];
$par3 = new Paragraph();
$par3->addTabularLines($tabularRows, [
    ['position' => 5.0, 'alignment' => 'left'],
    ['position' => 11.0, 'alignment' => 'right']
], [
    'color' => '#0066cc',
    'class' => 'InvoiceTable'
]);

// Paragraph 4: Key-Value summary
$par4 = new Paragraph();
$par4->addKeyValueLine('Subtotal', '€20.76', 11.0, ['italic' => true, 'bold' => true]);


// Now assemble the full rich text
$rich1->addParagraph($par1)
    ->addParagraphBreak(1)
    ->addParagraph($par2)
    ->addParagraphBreak()
    ->addBulletList($bList, ['italic' => true])
    ->addParagraphBreak(2)
    ->addParagraph($par3)

    ->addParagraph($par4);

$template->setElement('rich1', $rich1);

// Create a complex RichText block for {{rich2}}
$rich2 = new RichText();

// Paragraph 5: Contact info
$par5 = new Paragraph();
$par5->addText('If you have any questions, feel free to contact us at {{mail}} or call us at {{phone}}.')
    ->addText("\nWe are always happy to assist you!", ['italic' => true]);

// Paragraph 6: Signature
$par6 = new Paragraph();
$par6->addText('Best regards,')
    ->addLineBreak(1)
    ->addText("\nThe Team from {{town}} ")
    ->addLineBreak(1)
    ->addText("\nDate: {{datum}}");

// Now assemble the full rich text
$rich2->addParagraphBreak(2)
    ->addParagraph($par5)
    ->addParagraphBreak(2)
    ->addParagraph($par6);


// Assign the RichText block to the template
$template->setElement('rich2', $rich2);

// Render and save
$template->render();
$template->save('samples/output/output_10_smarties.odt');
;

