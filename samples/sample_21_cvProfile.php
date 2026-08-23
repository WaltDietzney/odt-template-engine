<?php

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

// Load the CV profile template.
$template = new OdtTemplate('samples/templates/template_21_cvProfile.odt');
$template->load();

// Define example contact and profile data.
$contact = [
    'Vorname' => 'Max',
    'Nachname' => 'Mustermann',
    'strasse' => 'Musterstr. 122',
    'ort' => '32456 Musterhausen',
    'mail' => 'Max@Muster.de',
    'telefon' => '01234 5678910',
];

$data = [
    'softskills' => ['Teamfähigkeit', 'Kommunikationsstärke', 'Problemlösung', 'Eigeninitiative'],
    'certs' => ['SCRUM Master', 'ITIL Foundation', 'AWS Certified Developer'],
    'languages' => [
        ['name' => 'Deutsch', 'level' => 10],
        ['name' => 'Englisch', 'level' => 9],
        ['name' => 'Französisch', 'level' => 6],
    ],
    'it' => [
        ['name' => 'PHP', 'level' => 9],
        ['name' => 'JavaScript', 'level' => 8],
        ['name' => 'SQL', 'level' => 7],
        ['name' => 'Python', 'level' => 6],
    ],
];

$data['career'] = [
    'highlights' => [
        'Erfolgreiche Einführung von DevOps-Prozessen',
        'Migration auf cloud-native Architektur',
    ],
    'berufserfahrung' => [
        ['title' => 'Senior Developer – Acme GmbH', 'desc' => 'Leitung von Backend-Architektur und API-Design.'],
        ['title' => 'Consultant – Example AG', 'desc' => 'Beratung zur digitalen Transformation.'],
    ],
    'studium' => [
        ['title' => 'B.Sc. Informatik – TU Berlin', 'desc' => 'Schwerpunkt: Software Engineering.'],
    ],
    'ausbildung' => [
        ['title' => 'Fachinformatiker – IHK Berlin', 'desc' => 'Dual bei CodeCorp GmbH.'],
    ],
    'qualifikationen' => [
        ['title' => 'Scrum Advanced Training', 'desc' => 'Zertifiziert nach SCRUM@Scale.'],
    ],
];

/**
 * Insert an array as a bullet list into a template placeholder.
 */
function addBullet(array $data, string $replace, $element): void
{
    $rich = new RichText();
    $rich->addBulletList($data);
    $element->setElement($replace, $rich);
}

/**
 * Render skill names with a ten-step visual rating aligned by tab stops.
 */
function addSkillsValues(array $data, string $replace, $element): void
{
    $richText = new RichText();
    $paragraph = new Paragraph();

    foreach ($data as $skill) {
        $level = (int) $skill['level'];
        $filled = str_repeat('◘', $level);
        $empty = str_repeat('○', 10 - $level);
        $tabStops = [
            [
                'position' => 0.2,
                'alignment' => 'left',
                'text' => $skill['name'],
                'style' => ['bold' => true],
            ],
            [
                'position' => 8.0,
                'alignment' => 'right',
                'text' => $filled . $empty,
                'style' => ['color' => '#00B050'],
            ],
        ];

        $paragraph->addTabsWithTexts($tabStops);
    }

    $richText->addParagraph($paragraph);
    $element->setElement($replace, $richText);
}

/**
 * Add a career section with a heading and formatted entry list.
 */
function addSection(string $symbol, RichText $richText, string $heading, array $entries): void
{
    if (empty($entries)) {
        return;
    }

    $options = [
        'background-color' => '#f0f8ff',
        'margin-top' => '0.5cm',
        'margin-bottom' => '0.5cm',
        'padding' => '0.2cm',
    ];

    $richText->addParagraph(
        (new Paragraph('standard', $options))->addText("{$symbol} {$heading}", ['bold' => true])
    );

    $paragraph = new Paragraph();
    $count = count($entries);

    foreach ($entries as $index => $item) {
        $paragraph
            ->addText($item['title'], ['bold' => true])
            ->addLineBreak()
            ->addText($item['desc'], ['font-size' => 'small']);

        if ($index < $count - 1) {
            $paragraph->addLineBreak();
        }
    }

    $richText->addParagraph($paragraph);
}

$template->assign($contact);
addBullet($data['softskills'], 'softskills', $template);
addBullet($data['certs'], 'certs', $template);
addSkillsValues($data['languages'], 'languages', $template);
addSkillsValues($data['it'], 'it-skills', $template);

// Build the career profile as one rich content block.
$career = new RichText();
$highlightOptions = [
    'background-color' => '#f0f8ff',
    'margin-top' => '0.5cm',
    'margin-bottom' => '0.5cm',
    'padding' => '0.2cm',
];

if (!empty($data['career']['highlights'])) {
    $career->addParagraph(
        (new Paragraph('standard', $highlightOptions))->addText('✨ Highlights', ['bold' => true])
    );
    $career->addBulletList($data['career']['highlights'], ['color' => '#007700']);
    $career->addParagraphBreak();
}

addSection('💼', $career, 'Berufserfahrung', $data['career']['berufserfahrung']);
addSection('🎓', $career, 'Studium', $data['career']['studium']);
addSection('🏫', $career, 'Ausbildung', $data['career']['ausbildung']);
addSection('📜', $career, 'Qualifikationen', $data['career']['qualifikationen']);

$template->setElement('berufserfahrungen', $career);

// Insert the profile image.
$template->setImage('foto', 'assets/Logo-2.png', [
    'width' => '3.5cm',
    'anchor' => 'paragraph',
    'align' => 'left',
]);

$template->render();
$template->save('samples/output/output_21_cvProfile.odt');

echo "CV profile sample generated successfully: samples/output/output_21_cvProfile.odt\n";
