<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_C01_page_flow_layout.odt');

$template->setElement(
    'opening',
    (new Paragraph(null, ['keep-with-next' => 'always']))
        ->addText('Executive overview', ['bold' => true])
);
$template->setElement(
    'lead',
    (new Paragraph(null, ['keep-together' => 'always']))
        ->addText('This short introduction is kept as one paragraph block. ')
        ->addText('Writer still decides where the page ends.')
);

$longText = 'The quarterly review describes the delivery outlook, operational risks, and decisions needed by the project team. '
    . 'Its paragraph-flow policy asks Writer to retain a minimum number of lines at the top and bottom of a page. '
    . 'That policy describes reading flow; it does not calculate physical pagination or select a page style. ';
$template->setElement(
    'body_block',
    (new Paragraph(null, ['widows' => 2, 'orphans' => 2]))
        ->addText(str_repeat($longText, 5))
);

$template->setElement(
    'chapter_break',
    (new Paragraph(null, ['break-before' => 'page', 'keep-with-next' => 'always']))
        ->addText('Delivery outlook', ['bold' => true])
);
$template->setElement(
    'chapter_body',
    (new Paragraph(null, ['keep-together' => 'always', 'break-after' => 'page']))
        ->addText('The delivery outlook is stable. The team has completed the discovery work, confirmed the next review date, and assigned owners for each open decision. ')
        ->addText('This short chapter intentionally ends before the next authored page region.')
);

$template->setElement(
    'final_heading',
    (new Paragraph(null, ['keep-with-next' => 'always']))
        ->addText('Decisions and next steps', ['bold' => true])
);
$template->setElement(
    'final_body',
    (new Paragraph(null, ['widows' => 2, 'orphans' => 2]))
        ->addText('The report keeps page design in the Writer-authored template. Generated paragraph properties express keep and break intent; Writer computes the actual pagination when the document is opened or exported.')
);

$template->assign(['report_period' => 'Q3 2026']);
$template->render();
$template->save(__DIR__ . '/output/output_C01_page_flow_layout.odt');
