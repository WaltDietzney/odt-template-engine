<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_S02_professional_invoice.odt');

$rootValues = [
    'customer_first_name' => 'Andrew',
    'customer_last_name' => 'Thompson',
    'customer_address' => '123 Harbour View Road',
    'customer_state' => 'Sydney NSW 2000',
    'customer_phone' => '+61 2 9999 4567',
    'customer_mail' => 'andrew.thompson@example.com',
    'sender_address' => '555 Market Street, Sydney NSW 2000',
    'sender_phone' => '+61 2 9999 4567',
    'sender_mail' => 'billing@northstar.example',
    'sender_web' => 'www.northstar.example',
    'sender_name' => 'Jonathon Deo',
    'sender_position' => 'Account Manager',
    'bank_name' => 'Northstar Studio Bank',
    'bank_code' => '110-245',
    'paypal_mail' => 'billing@northstar.example',
    'subtotal' => '$4,800.00',
    'tax' => '$864.00',
    'total' => '$5,664.00',
];

$items = [
    [
        'name' => 'Brand strategy workshop',
        'description' => 'A focused workshop to align the project direction, audience, and launch priorities.',
        'price' => '$900.00',
        'quantity' => '1',
        'line_total' => '$900.00',
    ],
    [
        'name' => 'Web design and responsive implementation',
        'description' => 'Responsive page design and implementation for the approved Northstar Studio direction.',
        'price' => '$1,800.00',
        'quantity' => '1',
        'line_total' => '$1,800.00',
    ],
    [
        'name' => 'Content and visual identity package',
        'description' => 'Content refinement and visual identity assets prepared for the digital launch.',
        'price' => '$1,250.00',
        'quantity' => '1',
        'line_total' => '$1,250.00',
    ],
    [
        'name' => 'Digital launch consulting and implementation planning',
        'description' => 'Consulting and implementation planning covering launch coordination, handover, and the next delivery milestones.',
        'price' => '$850.00',
        'quantity' => '1',
        'line_total' => '$850.00',
    ],
];

// The template owns the two native Writer User Fields and their display locations.
$template->setUserField('invoice_number', 'INV-2026-0142');
$template->setUserField('invoice_date', '23 September 2026');

$contract = $template->inspectTemplate();

// Select the authored male salutation Section; the other native branch is removed.
$template->executeDeclarative($contract, [
    ...$rootValues,
    'male' => true,
    'female' => false,
]);

$template->assign($rootValues);
$template->assignRepeating('items', $items);
$template->render();

$template->setMeta([
    'title' => 'Professional Invoice',
    'subject' => 'Professional invoice generated from a LibreOffice-authored template',
    'description' => 'S02 professional showcase for the ODT Template Engine',
    'language' => 'en-US',
]);

$outputPath = __DIR__ . '/output/output_S02_professional_invoice.odt';
$template->save($outputPath);

echo "Saved S02 professional invoice to {$outputPath}.\n";
