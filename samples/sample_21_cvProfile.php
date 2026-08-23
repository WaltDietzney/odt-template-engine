<?php

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;

// Load the template.
$template = new OdtTemplate('samples/templates/template_21_cvProfile.odt');
$template->load();

// Example data.
$address = 'Musterstr. 122, 32456 Musterhausen';
$contact = [
    'Vorname' => 'Max',
    'Nachname' => 'Mustermann',
    'strasse' => 'Musterstr. 122',
    'ort' => '32456 Musterhausen',
    'adresse' => $address,
    'adress' => $address,
    'address' => $address,
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

function addBullet(array $data, string $replace, $element)
{
    $rich = new RichText();
    $rich->addBulletList($data);
    $element->setElement($replace, $rich);
}

$template->assign($contact);

addBullet($data['softskills'], 'softskills', $template);
addBullet($data['certs'], 'certs', $template);

function addSkillsValues($data, $replace, $element)
{
    $rtIT = new RichText();
    $par = new Paragraph();

    foreach ($data as $skill) {
        $level = (int) $skill['level'];
        $filled = str_repeat('◘', $level);
        $empty = str_repeat('○', 10 - $level);
        $tabStops = [
            ['position' => 0.2, 'alignment' => 'left', 'text' => $skill['name'], 'style' => ['bold' => true]],
            ['position' => 8.0, 'alignment' => 'right', 'text' => $filled . $empty, 'style' => ['color' => '#00B050']],
        ];

        $par->addTabsWithTexts($tabStops);
    }

    $rtIT->addParagraph($par);
    $element->setElement($replace, $rtIT);
}

addSkillsValues($data['languages'], 'languages', $template);
addSkillsValues($data['it'], 'it-skills', $template);

$rtCareer = new RichText();
$opt = [
    'background-color' => '#f0f8ff',
    'margin-top' => '0.5cm',
    'margin-bottom' => '0.5cm',
    'padding' => '0.2cm',
];

// Add the highlights section.
if (!empty($data['career']['highlights'])) {
    $rtCareer->addParagraph((new Paragraph('standard', $opt))->addText('✨ Highlights', ['bold' => true]));
    $rtCareer->addBulletList($data['career']['highlights'], ['color' => '#007700']);
    $rtCareer->addParagraphBreak();
}

// Helper for a career section consisting of a heading and [title, description] entries.
function addSection($symbol, RichText $rt, string $heading, array $entries)
{
    $opt = [
        'background-color' => '#f0f8ff',
        'margin-top' => '0.5cm',
        'margin-bottom' => '0.5cm',
        'padding' => '0.2cm',
    ];

    if (empty($entries)) {
        return;
    }

    $rt->addParagraph((new Paragraph('standard', $opt))->addText("$symbol {$heading}", ['bold' => true]));
    $par = new Paragraph();
    $count = count($entries);

    foreach ($entries as $index => $item) {
        $par->addText($item['title'], ['bold' => true])
            ->addLineBreak()
            ->addText($item['desc'], ['font-size' => 'small']);

        if ($index < $count - 1) {
            $par->addLineBreak();
        }
    }

    $rt->addParagraph($par);
}

// Add the remaining career sections.
addSection('💼', $rtCareer, 'Berufserfahrung', $data['career']['berufserfahrung']);
addSection('🎓', $rtCareer, 'Studium', $data['career']['studium']);
addSection('🏫', $rtCareer, 'Ausbildung', $data['career']['ausbildung']);
addSection('📜', $rtCareer, 'Qualifikationen', $data['career']['qualifikationen']);

// Insert the career block into the template.
$template->setElement('berufserfahrungen', $rtCareer);

$template->setImage('foto', 'assets/Logo-2.png', [
    'width' => '3.5cm',
    'anchor' => 'paragraph',
    'align' => 'left',
]);

$template->setImage('qrCode', 'assets/sample_21_vcard_qr.png', [
    'width' => '2.8cm',
    'anchor' => 'as-char',
    'wrap' => 'none',
]);

// Render and save the document.
$template->render();
$template->save('samples/output/output_21_cvProfile.odt');

echo "Document generated successfully: output/output_21_cvProfile.odt\n";
