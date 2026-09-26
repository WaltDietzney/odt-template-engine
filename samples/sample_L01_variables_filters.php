<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L01_variables_filters.odt');
$template->assign([
    'name' => 'Anna Beispiel',
    'email' => 'ANNA@EXAMPLE.COM',
    'birthday' => '1995-08-15',
    'amount' => '1345.5',
    'note' => "Thank you for your order.\nYour receipt is attached.",
]);

$template->render();
$template->save(__DIR__ . '/output/output_L01_variables_filters.odt');
