<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_C04_declarative_structured_collections.odt');
$contract = $template->inspectTemplate();

// The records already use the template's dependency names: no mapping is involved.
$template->executeDeclarative($contract, [
    'projects' => [
        [
            'title' => 'Aurora',
            'status' => 'on track',
            'featured' => true,
            'archived' => false,
            'milestones' => [
                ['milestone' => 'Discovery complete'],
                ['milestone' => 'Template review'],
            ],
        ],
        [
            'title' => 'Beacon',
            'status' => 'planned',
            'featured' => false,
            'archived' => true,
            'milestones' => [
                ['milestone' => 'Kickoff'],
            ],
        ],
    ],
]);

$template->save(__DIR__ . '/output/output_C04_declarative_structured_collections.odt');
