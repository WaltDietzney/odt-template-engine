<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L02_conditions.odt');
$template->assign([
    'account' => 'AC-2048',
    'balance' => 0,
    'priority_account' => true,
    'review_required' => false,
]);

$template->render();
$template->save(__DIR__ . '/output/output_L02_conditions.odt');
