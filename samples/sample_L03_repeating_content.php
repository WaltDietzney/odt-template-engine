<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L03_repeating_content.odt');
$template->assign([
    'order_number' => 'SO-1042',
    'customer' => 'Anna Beispiel',
]);
$template->assignRepeating('items', [
    ['name' => 'Notebook', 'sku' => 'PAP-01', 'quantity' => '2', 'price' => '8.50'],
    ['name' => 'Fountain pen', 'sku' => 'WR-14', 'quantity' => '1', 'price' => '19.90'],
    ['name' => 'Ink bottle', 'sku' => 'INK-03', 'quantity' => '3', 'price' => '4.20'],
]);

$template->render();
$template->save(__DIR__ . '/output/output_L03_repeating_content.odt');
