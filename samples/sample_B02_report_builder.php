<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_B02_report_builder.odt');
$template->setDocumentDefaults([
    'text' => ['font-family' => 'Liberation Sans', 'color' => '#263746'],
    'paragraph' => ['margin-top' => '0cm', 'margin-bottom' => '0.18cm'],
]);
$template->setMeta([
    'title' => 'Annual Performance Report 2026',
    'subject' => 'B02 Professional Report Builder demonstration',
    'description' => 'A fictional annual performance report constructed with the ODT Template Engine.',
    'creator' => 'Asteria Demo Foundation',
    'keywords' => ['ODT Template Engine', 'B02', 'report builder', 'fictional demonstration'],
    'language' => 'en',
    'date' => '2027-01-31T00:00:00',
]);

$navy = '#0d3157';
$ink = '#263746';
$muted = '#536574';
$accent = '#2f89a3';
$paleBlue = '#e9f3f7';
$line = '#c9d9e1';
$light = '#f5f8fa';

$heading = static function (string $number, string $title, bool $breakBefore = false) use ($navy, $line): Paragraph {
    $options = [
        'keep-with-next' => 'always',
        'margin-top' => '0.2cm',
        'margin-bottom' => '0.28cm',
        'border-bottom' => '0.02cm solid ' . $line,
        'padding-bottom' => '0.16cm',
    ];
    if ($breakBefore) {
        $options['break-before'] = 'page';
    }

    return (new Paragraph($breakBefore ? 'B02PageHeading' : 'B02Heading', $options))
        ->addText($number . '  ', ['color' => '#a8c2d2', 'font-size' => '22pt', 'bold' => true])
        ->addText($title, ['color' => $navy, 'font-size' => '18pt', 'bold' => true]);
};

$bodyParagraph = static function (string $text, string $marginBottom = '0.22cm') use ($ink): Paragraph {
    $style = $marginBottom === '0.22cm'
        ? 'B02Body'
        : 'B02Body' . str_replace(['.', 'cm'], '', $marginBottom);

    return (new Paragraph($style, [
        'widows' => 2,
        'orphans' => 2,
        'line-height' => '112%',
        'margin-bottom' => $marginBottom,
    ]))->addText($text, ['color' => $ink, 'font-size' => '10pt']);
};

$subheading = static function (string $text, string $marginTop = '0.12cm', string $marginBottom = '0.08cm') use ($navy): Paragraph {
    $style = ($marginTop === '0.12cm' && $marginBottom === '0.08cm')
        ? 'B02Subheading'
        : 'B02Subheading' . str_replace(['.', 'cm'], '', $marginTop . $marginBottom);

    return (new Paragraph($style, [
        'keep-with-next' => 'always',
        'margin-top' => $marginTop,
        'margin-bottom' => $marginBottom,
    ]))->addText($text, ['color' => $navy, 'font-size' => '12pt', 'bold' => true]);
};

$plain = static function (string $text) use ($ink): Paragraph {
    return (new Paragraph('B02Body'))->addText($text, ['color' => $ink, 'font-size' => '10pt']);
};

$cell = static function (string|Paragraph|RichText $content, array $style = []): RichTableCell {
    return new RichTableCell($content, array_merge([
        'padding' => '0.16cm',
        'vertical-align' => 'middle',
    ], $style));
};

$header = (new RichText())->addParagraph(
    (new Paragraph('B02Header', ['margin-top' => '0cm', 'margin-bottom' => '0cm', 'tab-stops' => [['position' => 16.5, 'alignment' => 'right']]]))
        ->addText('ASTERIA', ['bold' => true, 'color' => $navy, 'font-size' => '8pt'])
        ->addText('  /  ANNUAL PERFORMANCE REPORT 2026', ['color' => $muted, 'font-size' => '8pt'])
);

$footer = (new RichText())->addParagraph(
    (new Paragraph('B02Footer', ['margin-top' => '0cm', 'margin-bottom' => '0cm']))
        ->addText('Asteria Demo Foundation · Pathways 360 Demo Program', ['color' => $muted, 'font-size' => '8pt'])
);

$body = new RichText();

// Cover: an editable editorial composition with the title above a larger visual.
$coverImage = new ImageElement(__DIR__ . '/assets/asteria-b02-cover.png', [
    'width' => '9.4cm',
    'anchor' => 'as-char',
]);
$cover = (new RichTable())->setTableStyle(['relative-width' => '100%', 'alignment' => 'center']);
$cover->setColumnWidthRatios([3, 2, 2, 2]);
$cover->addRow([
    $cell((new Paragraph('B02CoverImage', ['text-align' => 'center']))->addElement($coverImage), ['padding' => '0.12cm', 'background' => $light, 'border' => 'none'])->setColspan(4),
]);
$body->addParagraph((new Paragraph('B02CoverBrand', ['margin-left' => '0.28cm', 'margin-right' => '0.28cm', 'margin-bottom' => '0.08cm']))->addText('ASTERIA DEMO FOUNDATION', ['color' => $navy, 'font-size' => '11pt', 'bold' => true]));
$body->addParagraph((new Paragraph('B02CoverKicker', ['margin-left' => '0.28cm', 'margin-right' => '0.28cm', 'margin-bottom' => '0.12cm']))->addText('SAMPLE DOCUMENT · FICTIONAL ORGANIZATION AND DATA', ['color' => $accent, 'font-size' => '9pt', 'bold' => true]));
$body->addParagraph((new Paragraph('B02CoverTitle', ['keep-with-next' => 'always', 'margin-left' => '0.28cm', 'margin-right' => '0.28cm', 'margin-bottom' => '0.12cm']))->addText('Annual Performance Report 2026', ['color' => $navy, 'font-size' => '30pt', 'bold' => true]));
$body->addParagraph((new Paragraph('B02CoverProgram', ['margin-left' => '0.28cm', 'margin-right' => '0.28cm', 'margin-bottom' => '0.14cm']))->addText('Pathways 360 Demo Program', ['color' => $navy, 'font-size' => '14pt', 'bold' => true]));
$body->addParagraph((new Paragraph('B02CoverMeta', ['margin-left' => '0.28cm', 'margin-right' => '0.28cm', 'margin-bottom' => '0.38cm']))->addText('January–December 2026  ·  Final Report · 31 January 2027', ['color' => $ink, 'font-size' => '10pt']));
$body->addTable($cover);
$body->addParagraph((new Paragraph('B02CoverClosing', ['break-after' => 'page', 'margin-top' => '0.18cm', 'border-top' => '0.02cm solid ' . $line, 'padding-top' => '0.14cm']))->addText('This document is a demonstration sample created for the ODT Template Engine. All organizations, programs, persons, figures and report data shown in this document are fictional and are used solely for demonstration purposes.', ['color' => $muted, 'font-size' => '8.5pt', 'italic' => true]));

// Executive Summary.
$body->addParagraph($heading('01', 'Executive Summary'));
$body->addParagraph($bodyParagraph('The Pathways 360 Demo Program supports people to build skills, confidence and connections for sustainable employment and further training. During 2026, the program reached 248 participants across three delivery locations, combining individual coaching, practical workshops, digital skills development and direct engagement with local employers.', '0.34cm'));
$body->addParagraph($bodyParagraph('The delivery model is designed around progression rather than a single intervention. Participants can move between coaching, skills practice and employer-facing activity as their needs develop. This integrated approach helped the program maintain strong completion and outcome rates while building a broader base of employer partnerships.', '0.34cm'));
$body->addParagraph($bodyParagraph('The results indicate that coordinated support is most valuable when it is connected to realistic next steps. The strongest progression was observed among participants who received both individual coaching and employer-facing support. The year also highlighted the importance of differentiated digital support and more consistent outcome tracking as the program grows.', '0.38cm'));

$kpi = static function (string $value, string $label) use ($navy, $muted): RichText {
    return (new RichText())
        ->addParagraph((new Paragraph('B02KpiValue', ['text-align' => 'center', 'margin-bottom' => '0.04cm']))->addText($value, ['color' => $navy, 'font-size' => '20pt', 'bold' => true]))
        ->addParagraph((new Paragraph('B02KpiLabel', ['text-align' => 'center', 'margin-bottom' => '0cm']))->addText($label, ['color' => $muted, 'font-size' => '8.5pt']));
};
$kpis = (new RichTable())->setTableStyle(['relative-width' => '100%', 'alignment' => 'center']);
$kpis->setColumnWidthRatios([3, 2, 2, 2]);
$kpis->addRow([
    $cell($kpi('248', 'Participants'), ['background' => $paleBlue, 'border' => 'none']),
    $cell($kpi('87%', 'Completion rate'), ['background' => $paleBlue, 'border' => 'none']),
    $cell($kpi('64%', 'Positive outcomes'), ['background' => $paleBlue, 'border' => 'none']),
    $cell($kpi('42', 'Employer partners'), ['background' => $paleBlue, 'border' => 'none']),
], ['min-row-height' => '1.7cm']);
$body->addTable($kpis);
$body->addParagraphBreak(1);

$finding = (new DrawTextBox('B02KeyFinding', [
    'background-color' => $paleBlue,
    'border-left' => '0.08cm solid ' . $accent,
    'padding' => '0.24cm',
]))->setFrameLayout([
    'anchor' => 'paragraph',
    'width' => '16.2cm',
    'height' => '1.95cm',
    'horizontal' => ['alignment' => 'center', 'relative-to' => 'paragraph'],
    'vertical' => ['alignment' => 'top', 'relative-to' => 'paragraph'],
    'wrap' => 'none',
])->addElement(
    (new Paragraph('B02KeyFindingTitle', ['margin-bottom' => '0.05cm']))->addText('KEY FINDING', ['color' => $navy, 'font-size' => '10pt', 'bold' => true])
)->addElement(
    (new Paragraph('B02KeyFindingBody', ['margin-bottom' => '0cm']))->addText('Participants receiving combined individual coaching and employer-facing support showed the strongest progression.', ['color' => $ink, 'font-size' => '9.5pt'])
);
$body->addElement($finding);
$body->addParagraph($subheading('Management reading', '0.28cm', '0.12cm'));
$body->addParagraph($bodyParagraph('Management reading: the headline results are strongest when reach, completion and employer connection are considered together. The next planning cycle should protect this integrated model while targeting digital confidence and more consistent outcome tracking.', '0.28cm'));

// Program Overview & Delivery.
$body->addParagraph($heading('02', 'Program Overview & Delivery', true));
$body->addParagraph($bodyParagraph('Pathways 360 is a fictional demonstration program that combines coaching, practical skills development and employer engagement. Its 2026 delivery model focused on improving readiness for employment or further training while maintaining a supportive route for participants with different starting points.', '0.34cm'));
$facts = (new RichTable())->setTableStyle(['relative-width' => '100%', 'alignment' => 'center']);
$facts->setColumnWidthRatios([3, 2, 2, 2]);
$facts->addRow([
    $cell((new RichText())->addParagraph((new Paragraph('B02Fact'))->addText('REPORTING PERIOD', ['color' => $muted, 'font-size' => '8pt', 'bold' => true]))->addParagraph((new Paragraph('B02FactValue'))->addText('January–December 2026', ['color' => $navy, 'font-size' => '9pt'])), ['background' => $light, 'border' => 'none']),
    $cell((new RichText())->addParagraph((new Paragraph('B02Fact'))->addText('LOCATIONS', ['color' => $muted, 'font-size' => '8pt', 'bold' => true]))->addParagraph((new Paragraph('B02FactValue'))->addText('3 program locations', ['color' => $navy, 'font-size' => '9pt'])), ['background' => $light, 'border' => 'none']),
    $cell((new RichText())->addParagraph((new Paragraph('B02Fact'))->addText('DELIVERY TEAM', ['color' => $muted, 'font-size' => '8pt', 'bold' => true]))->addParagraph((new Paragraph('B02FactValue'))->addText('12 staff', ['color' => $navy, 'font-size' => '9pt'])), ['background' => $light, 'border' => 'none']),
    $cell((new RichText())->addParagraph((new Paragraph('B02Fact'))->addText('EMPLOYER PARTNERS', ['color' => $muted, 'font-size' => '8pt', 'bold' => true]))->addParagraph((new Paragraph('B02FactValue'))->addText('42 partners', ['color' => $navy, 'font-size' => '9pt'])), ['background' => $light, 'border' => 'none']),
]);
$body->addTable($facts);
$body->addParagraph($subheading('Program Objectives', '0.28cm', '0.12cm'));
$objectives = new ListElement('bullet');
foreach ([
    'Improve employment readiness and training opportunities.',
    'Strengthen vocational and digital skills.',
    'Connect participants with employers.',
    'Support progression into employment or further training.',
] as $objective) {
    $objectives->addItem((new Paragraph('B02ListItem'))->addText($objective, ['color' => $ink, 'font-size' => '9.5pt']));
}
$body->addElement($objectives);
$body->addParagraph($subheading('Activities Delivered', '0.30cm', '0.12cm'));
$activities = (new RichTable())->setTableStyle(['relative-width' => '100%', 'alignment' => 'center']);
$activities->setColumnWidthRatios([3, 2, 2, 2]);
$activities->setHeaderRowCount(1);
$activities->addRow([
    $cell('Activity', ['background' => $navy, 'color' => '#ffffff', 'weight' => 'bold', 'border' => 'none'])->setColspan(2),
    $cell((new Paragraph('B02TableHeader', ['text-align' => 'right']))->addText('Delivered', ['color' => '#ffffff', 'font-size' => '8.5pt', 'bold' => true]), ['background' => $navy, 'border' => 'none']),
    $cell((new Paragraph('B02TableHeader', ['text-align' => 'right']))->addText('Participants', ['color' => '#ffffff', 'font-size' => '8.5pt', 'bold' => true]), ['background' => $navy, 'border' => 'none']),
]);
foreach ([
    ['Individual coaching sessions', '1,146', '231'],
    ['Skills workshops', '84', '218'],
    ['Employer events', '18', '156'],
    ['Digital skills sessions', '46', '174'],
] as $row) {
    $activities->addRow([
        $cell($row[0], ['border-bottom' => '0.02cm solid ' . $line])->setColspan(2),
        $cell((new Paragraph('B02TableNumber', ['text-align' => 'right']))->addText($row[1], ['color' => $ink, 'font-size' => '9pt']), ['border-bottom' => '0.02cm solid ' . $line]),
        $cell((new Paragraph('B02TableNumber', ['text-align' => 'right']))->addText($row[2], ['color' => $ink, 'font-size' => '9pt']), ['border-bottom' => '0.02cm solid ' . $line]),
    ]);
}
$body->addTable($activities);
$body->addParagraph($subheading('Delivery Observation', '0.30cm', '0.12cm'));
$body->addParagraph($bodyParagraph('The activity mix combines a high volume of individual coaching with shared skills practice and employer-facing opportunities. This balance gives the program a practical route to respond to different starting points while still connecting participants to common progression goals.', '0.30cm'));
$body->addParagraph($bodyParagraph('The scale of coaching, alongside 84 workshops and 18 employer events, also shows why coordination matters: individual support carries the largest contact load, while group and employer activity creates the shared practice and external connection needed to turn preparation into progression.', '0.28cm'));

// Performance & Outcomes.
$body->addParagraph($heading('03', 'Performance & Outcomes', true));
$body->addParagraph($subheading('Performance against Targets'));
$performance = (new RichTable())->setTableStyle(['relative-width' => '100%', 'alignment' => 'center']);
$performance->setColumnWidthRatios([3, 2, 2, 2]);
$performance->setHeaderRowCount(1);
$performance->addRow([
    $cell('Indicator', ['background' => $navy, 'color' => '#ffffff', 'weight' => 'bold', 'border' => 'none']),
    $cell('Target', ['background' => $navy, 'color' => '#ffffff', 'weight' => 'bold', 'text-align' => 'right', 'border' => 'none']),
    $cell('Actual', ['background' => $navy, 'color' => '#ffffff', 'weight' => 'bold', 'text-align' => 'right', 'border' => 'none']),
    $cell('Status', ['background' => $navy, 'color' => '#ffffff', 'weight' => 'bold', 'border' => 'none']),
]);
foreach ([
    ['Participants enrolled', '240', '248', 'Achieved'],
    ['Program completion', '80%', '87%', 'Above target'],
    ['Employment / training outcome', '60%', '64%', 'Above target'],
    ['Employer partners', '35', '42', 'Above target'],
    ['Digital skills completion', '75%', '78%', 'Achieved'],
] as $row) {
    $performance->addRow([
        $cell($row[0], ['border-bottom' => '0.02cm solid ' . $line]),
        $cell((new Paragraph('B02TableNumber', ['text-align' => 'right']))->addText($row[1], ['color' => $ink, 'font-size' => '9pt']), ['border-bottom' => '0.02cm solid ' . $line]),
        $cell((new Paragraph('B02TableNumber', ['text-align' => 'right']))->addText($row[2], ['color' => $ink, 'font-size' => '9pt']), ['border-bottom' => '0.02cm solid ' . $line]),
        $cell((new Paragraph('B02Status'))->addText($row[3], ['color' => $accent, 'font-size' => '9pt', 'bold' => true]), ['border-bottom' => '0.02cm solid ' . $line]),
    ]);
}
$body->addTable($performance);
$body->addParagraphBreak(1);
$outcomes = (new RichText())
    ->addParagraph($subheading('Participant Outcomes'))
    ->addParagraph((new Paragraph('B02Chart'))->addElement(new ImageElement(__DIR__ . '/assets/asteria-b02-outcomes.png', ['width' => '15.8cm', 'anchor' => 'as-char'])))
    ->addParagraph((new Paragraph('B02Caption', ['margin-top' => '0.06cm']))->addText('Prepared visualization of fictional 2026 participant outcomes.', ['color' => $muted, 'font-size' => '8.5pt', 'italic' => true]))
    ->addParagraph($bodyParagraph('Employment represented the largest outcome group at 41%, while a further 23% moved into training and 21% remained in active progression. Together, these results indicate that the program supported several meaningful routes forward rather than a single definition of success.'));
$body->addElement($outcomes);

// Findings & Analysis.
$body->addParagraph($heading('04', 'Findings & Analysis', true));
$body->addParagraph($subheading('Integrated support produced stronger outcomes'));
$body->addParagraph($bodyParagraph('Participants who combined individual coaching with workshops and employer-facing activity showed the strongest progression. The pattern suggests that practical support is most effective when it is connected to a clear next step and reinforced by more than one type of engagement.', '0.30cm'));
$body->addParagraph($bodyParagraph('For management, the implication is to preserve the sequence between preparation and opportunity: coaching builds readiness, practice makes capability visible, and employer contact gives progression a concrete direction.', '0.34cm'));
$body->addParagraph($subheading('Digital confidence remains uneven', '0.28cm', '0.12cm'));
$body->addParagraph($bodyParagraph('Digital confidence improved across the program, but starting points varied considerably. Participants with less prior experience benefited from additional practice, more patient guidance and opportunities to apply digital skills in realistic vocational contexts.', '0.30cm'));
$body->addParagraph($bodyParagraph('This variation matters because digital tasks increasingly sit inside otherwise practical routes into work and training. A differentiated support offer can therefore strengthen both participation in the program and the quality of later progression.', '0.34cm'));

$insight = (new DrawTextBox('B02ProgramInsight', [
    'background-color' => '#dceef3',
    'border-left' => '0.08cm solid ' . $accent,
    'padding' => '0.24cm',
]))->setFrameLayout([
    'anchor' => 'paragraph',
    'width' => '9.2cm',
    'height' => '2.75cm',
    'horizontal' => ['alignment' => 'right', 'relative-to' => 'paragraph'],
    'vertical' => ['alignment' => 'top', 'relative-to' => 'paragraph'],
    'wrap' => 'left',
])->addElement(
    (new Paragraph('B02ProgramInsightTitle', ['margin-bottom' => '0.06cm']))->addText('PROGRAM INSIGHT', ['color' => $navy, 'font-size' => '10pt', 'bold' => true])
)->addElement(
    (new Paragraph('B02ProgramInsightBody', ['margin-bottom' => '0cm']))->addText('Employer engagement proved most effective when employers became involved before the formal recruitment stage rather than only receiving completed candidate profiles. Early contact made expectations clearer and gave participants a more realistic context for preparation.', ['color' => $ink, 'font-size' => '9pt'])
);
$body->addElement($insight);
$body->addParagraph($subheading('Challenges', '0.30cm', '0.12cm'));
$challenges = new ListElement('bullet');
foreach ([
    'Irregular attendance among a small participant group.',
    'Varying levels of digital literacy.',
    'Limited placement opportunities in some occupational areas.',
] as $challenge) {
    $challenges->addItem((new Paragraph('B02ListItem'))->addText($challenge, ['color' => $ink, 'font-size' => '9.5pt']));
}
$body->addElement($challenges);
$body->addParagraph($subheading('Management implication', '0.34cm', '0.12cm'));
$body->addParagraph($bodyParagraph('The response should be selective rather than broad: protect flexible support for attendance, build digital practice into ordinary delivery, and focus employer development where placement opportunities are most constrained.', '0.30cm'));

// Recommendations & Outlook.
$body->addParagraph($heading('05', 'Recommendations & Outlook', true));
$body->addParagraph($subheading('Recommendations', '0.28cm', '0.12cm'));
$recommendations = new ListElement('numbered');
foreach ([
    'Expand employer involvement by bringing partners into program design and preparation earlier. This should align practice opportunities with realistic recruitment expectations and make employer-facing activity more purposeful.',
    'Strengthen targeted digital support for participants with lower initial confidence. Additional guided practice should help participants apply digital skills with greater confidence in vocational contexts.',
    'Improve outcome tracking so longer-term progression can be understood more consistently. A clearer view after completion would help distinguish immediate results from sustained movement into work or training.',
    'Create more peer-learning opportunities around sector-specific practice and confidence building. Shared practice can reinforce capability and reduce isolation for participants working toward similar goals.',
] as $recommendation) {
    $recommendations->addItem((new Paragraph('B02Recommendation', ['margin-bottom' => '0.16cm']))->addText($recommendation, ['color' => $ink, 'font-size' => '9.5pt']));
}
$body->addElement($recommendations);
$body->addParagraph($subheading('Outlook 2027', '0.34cm', '0.12cm'));
$body->addParagraph($bodyParagraph('In 2027, Pathways 360 will build on the progress achieved in 2026 by deepening employer partnerships, strengthening targeted digital support and improving the continuity of outcome tracking. The focus will remain on high-quality, person-centred support that connects confidence, capability and realistic progression. Delivery will continue to balance individual attention with shared practice, so that growth in scale does not weaken the relationships that make progression possible.', '0.30cm'));
$body->addParagraph($bodyParagraph('The intended measure of progress is not only more activity, but clearer movement from preparation to opportunity. The program will therefore use the 2026 learning to refine support, strengthen the quality of employer connection and make outcomes easier to follow over time.', '0.30cm'));
$body->addParagraph((new Paragraph('B02Disclaimer', ['break-after' => 'page', 'margin-top' => '0.50cm', 'border-top' => '0.02cm solid ' . $line, 'padding-top' => '0.18cm']))->addText('This document is a demonstration sample created for the ODT Template Engine. All organizations, programs, persons, figures and report data shown in this document are fictional and are used solely for demonstration purposes.', ['color' => $muted, 'font-size' => '8.5pt', 'italic' => true]));

$template->setElement('report_header', $header);
$template->setElement('report_body', $body);
$template->setElement('report_footer', $footer);
$template->save(__DIR__ . '/output/output_B02_report_builder.odt');

echo "B02 Professional Report Builder generated successfully.\n";
