<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;

// Load the LibreOffice-designed CV template. The template defines the
// two-column structure, while PHP supplies the dynamic document content.
$template = new PageLayoutOdtTemplate('samples/templates/template_21_cvProfile.odt');

// Page geometry can be adjusted without editing the original ODT template.
$template->setPageMargins('0cm', '0.8cm', '0cm', '0cm');

// Register reusable paragraph styles once. The generated paragraphs reference
// these semantic names instead of creating hash-based paragraph style names.
$paragraphStyles = [
    'CVSidebarName' => [
        'margin-bottom' => '0.10cm',
        'line-height' => '100%',
    ],
    'CVSidebarBirth' => [
        'margin-top' => '0.05cm',
        'margin-bottom' => '0.10cm',
    ],
    'CVSidebarHeading' => [
        'margin-top' => '0.16cm',
        'margin-bottom' => '0.04cm',
        'line-height' => '100%',
    ],
    'CVSidebarLine' => [
        'margin-bottom' => '0.02cm',
        'line-height' => '105%',
    ],
    'CVSidebarSpacer' => [
        'margin-bottom' => '0.05cm',
    ],
    'CVSkill' => [
        'margin-bottom' => '0.02cm',
        'line-height' => '105%',
    ],
    'CVMainHeading' => [
        'margin-top' => '0.45cm',
        'margin-bottom' => '0.10cm',
        'padding-bottom' => '0.03cm',
        'line-height' => '100%',
        'border-bottom' => '1.5pt solid #12324a',
    ],
    'CVMainHeadingFirst' => [
        'margin-top' => '0cm',
        'margin-bottom' => '0.10cm',
        'padding-bottom' => '0.03cm',
        'line-height' => '100%',
        'border-bottom' => '1.5pt solid #12324a',
    ],
    'CVProfile' => [
        'margin-bottom' => '0.10cm',
        'line-height' => '110%',
    ],
    'CVEntryDate' => [
        'margin-top' => '0.14cm',
        'margin-bottom' => '0.01cm',
        'line-height' => '100%',
    ],
    'CVEntryDateFirst' => [
        'margin-top' => '0.05cm',
        'margin-bottom' => '0.01cm',
        'line-height' => '100%',
    ],
    'CVEntryTitle' => [
        'margin-bottom' => '0.01cm',
        'line-height' => '100%',
    ],
    'CVEntryCompany' => [
        'margin-bottom' => '0.03cm',
        'line-height' => '100%',
    ],
    'CVEducationInstitution' => [
        'margin-bottom' => '0.04cm',
        'line-height' => '100%',
    ],
];

foreach ($paragraphStyles as $styleName => $styleOptions) {
    StyleMapper::registerParagraphStyle($styleName, $styleOptions);
}

// In a real application, this data would typically come from a database,
// API, form submission, or another application layer.
$cv = [
    'personal' => [
        'name' => 'Max Mustermann',
        'birth' => '01.10.1990, Musterstadt',
        'email' => 'max.mustermann@example.de',
        'phone' => '+49 123 456789',
        'city' => '32456 Musterhausen',
        'photo' => 'assets/WaltDietzney.png',
    ],
    'profile' => 'Erfahrener Softwareentwickler mit Schwerpunkt auf PHP, Webanwendungen und dokumentenbasierten Workflows. '
        . 'Strukturierte, wartbare Lösungen und eine verständliche technische Kommunikation stehen im Mittelpunkt.',
    'plus_points' => [
        'Mehrjährige Projekterfahrung',
        'Strukturierte Arbeitsweise',
        'Technische Kommunikation',
    ],
    'soft_skills' => [
        'Teamfähigkeit',
        'Eigeninitiative',
        'Problemlösung',
    ],
    'skills' => [
        ['name' => 'PHP', 'level' => 5],
        ['name' => 'ODT / XML', 'level' => 5],
        ['name' => 'JavaScript', 'level' => 4],
        ['name' => 'SQL', 'level' => 4],
    ],
    'languages' => [
        'Deutsch – Muttersprache',
        'Englisch – sehr gut',
    ],
    'experience' => [
        [
            'period' => '2022 – heute',
            'position' => 'Senior Softwareentwickler',
            'company' => 'Example Solutions GmbH · Bielefeld',
            'tasks' => [
                'Entwicklung und Wartung PHP-basierter Fachanwendungen.',
                'Automatisierte Erzeugung editierbarer Office-Dokumente.',
                'Konzeption wiederverwendbarer Komponenten und Schnittstellen.',
            ],
        ],
        [
            'period' => '2018 – 2022',
            'position' => 'Softwareentwickler',
            'company' => 'Acme Digital AG · Hannover',
            'tasks' => [
                'Backend-Entwicklung mit PHP und SQL.',
                'Integration externer Dienste und Datenquellen.',
            ],
        ],
        [
            'period' => '2015 – 2018',
            'position' => 'Junior Softwareentwickler',
            'company' => 'Muster Software GmbH · Dortmund',
            'tasks' => [
                'Entwicklung interner Webanwendungen und Schnittstellen.',
                'Pflege bestehender PHP-Anwendungen und Datenbanken.',
            ],
        ],
        [
            'period' => '2014 – 2015',
            'position' => 'Softwareentwickler',
            'company' => 'Digital Services OHG · Münster',
            'tasks' => [
                'Umsetzung kleiner Webprojekte mit PHP, HTML und JavaScript.',
                'Technische Dokumentation und Anwendersupport.',
            ],
        ],
    ],
    'education' => [
        [
            'period' => '2014 – 2018',
            'title' => 'B.Sc. Informatik',
            'institution' => 'Technische Universität Musterstadt',
        ],
        [
            'period' => '2011 – 2014',
            'title' => 'Fachinformatiker Anwendungsentwicklung',
            'institution' => 'IHK Musterstadt',
        ],
    ],
    'qualifications' => [
        'Scrum Advanced Training',
        'ITIL Foundation',
    ],
];

/**
 * Create a styled paragraph using a reusable named paragraph style.
 *
 * Paragraph layout is defined once through StyleMapper, while text styling
 * remains local to the text run. This keeps the generated ODT style model
 * predictable and easier to inspect in LibreOffice.
 */
function cvParagraph(string $text, array $textStyle = [], ?string $paragraphStyle = null): Paragraph
{
    $paragraph = new Paragraph($paragraphStyle);
    $paragraph->addText($text, array_merge([
        'font-family' => 'Arial',
    ], $textStyle));

    return $paragraph;
}

/**
 * Add a consistently styled heading to the dark sidebar.
 */
function addSidebarHeading(RichText $rich, string $title): void
{
    $rich->addParagraph(cvParagraph($title, [
        'bold' => true,
        'font-size' => '10pt',
        'color' => '#ffffff',
    ], 'CVSidebarHeading'));
}

/**
 * Add a native ODF bullet list to the sidebar.
 *
 * @param list<string> $items
 */
function addSidebarList(RichText $rich, string $title, array $items): void
{
    addSidebarHeading($rich, $title);

    $list = new ListElement('bullet');
    foreach ($items as $item) {
        $paragraph = new Paragraph();
        $paragraph->addText($item, [
            'font-family' => 'Arial',
            'font-size' => '8.5pt',
            'color' => '#ffffff',
        ]);
        $list->addItem($paragraph);
    }

    $rich->addElement($list);
    $rich->addParagraph(cvParagraph('', [], 'CVSidebarSpacer'));
}

/**
 * Add a consistently styled section heading to the main content column.
 */
function addMainHeading(RichText $rich, string $title, bool $first = false): void
{
    $rich->addParagraph(cvParagraph($title, [
        'bold' => true,
        'font-size' => '13pt',
        'color' => '#111111',
    ], $first ? 'CVMainHeadingFirst' : 'CVMainHeading'));
}

// Build the sidebar as a structured ODT content block. RichText can contain
// paragraphs, images, lists, and other ODT elements before insertion into
// the corresponding template placeholder.
$sidebar = new RichText();

$sidebar->addParagraph(cvParagraph($cv['personal']['name'], [
    'bold' => true,
    'font-size' => '16pt',
    'color' => '#ffffff',
], 'CVSidebarName'));

$sidebar->addImage(new ImageElement($cv['personal']['photo'], [
    'width' => '3.4cm',
    'height' => '3.4cm',
    'anchor' => 'as-char',
    'align' => 'left',
]));

$sidebar->addParagraph(cvParagraph('° ' . $cv['personal']['birth'], [
    'font-size' => '8.5pt',
    'color' => '#ffffff',
], 'CVSidebarBirth'));

addSidebarHeading($sidebar, 'KONTAKT');
foreach ([
    'E-Mail: ' . $cv['personal']['email'],
    'Telefon: ' . $cv['personal']['phone'],
    'Ort: ' . $cv['personal']['city'],
] as $line) {
    $sidebar->addParagraph(cvParagraph($line, [
        'font-size' => '8.5pt',
        'color' => '#ffffff',
    ], 'CVSidebarLine'));
}

addSidebarList($sidebar, 'PLUSPUNKTE', $cv['plus_points']);
addSidebarList($sidebar, 'SOFT SKILLS', $cv['soft_skills']);

addSidebarHeading($sidebar, 'FACHKOMPETENZEN');
foreach ($cv['skills'] as $skill) {
    $rating = str_repeat('★', $skill['level']) . str_repeat('☆', 5 - $skill['level']);
    $paragraph = new Paragraph('CVSkill');
    $paragraph->addText($skill['name'] . '  ', [
        'font-family' => 'Arial',
        'font-size' => '8.5pt',
        'bold' => true,
        'color' => '#ffffff',
    ]);
    $paragraph->addText($rating, [
        'font-family' => 'Arial',
        'font-size' => '8.5pt',
        'color' => '#ffffff',
    ]);
    $sidebar->addParagraph($paragraph);
}

addSidebarList($sidebar, 'SPRACHEN', $cv['languages']);

// Build the main CV column independently from the template layout. The ODT
// template controls where this block is placed; PHP controls which sections,
// entries, styles, and lists are generated.
$content = new RichText();

addMainHeading($content, 'PROFIL', true);
$content->addParagraph(cvParagraph($cv['profile'], [
    'font-size' => '9pt',
    'color' => '#333333',
], 'CVProfile'));

addMainHeading($content, 'BERUFSERFAHRUNG');
foreach ($cv['experience'] as $index => $entry) {
    $content->addParagraph(cvParagraph($entry['period'], [
        'bold' => true,
        'font-size' => '8.5pt',
        'color' => '#444444',
    ], $index === 0 ? 'CVEntryDateFirst' : 'CVEntryDate'));

    $content->addParagraph(cvParagraph($entry['position'], [
        'bold' => true,
        'font-size' => '10.5pt',
        'color' => '#111111',
    ], 'CVEntryTitle'));

    $content->addParagraph(cvParagraph($entry['company'], [
        'font-size' => '8.5pt',
        'color' => '#666666',
    ], 'CVEntryCompany'));

    // Each task collection becomes a native ODF list rather than simulated
    // bullet characters, so the resulting document remains structurally rich.
    $tasks = new ListElement('bullet');
    foreach ($entry['tasks'] as $task) {
        $paragraph = new Paragraph();
        $paragraph->addText($task, [
            'font-family' => 'Arial',
            'font-size' => '8.5pt',
            'color' => '#333333',
        ]);
        $tasks->addItem($paragraph);
    }
    $content->addElement($tasks);
}

addMainHeading($content, 'AUSBILDUNG');
foreach ($cv['education'] as $index => $entry) {
    $content->addParagraph(cvParagraph($entry['period'], [
        'bold' => true,
        'font-size' => '8.5pt',
        'color' => '#444444',
    ], $index === 0 ? 'CVEntryDateFirst' : 'CVEntryDate'));

    $content->addParagraph(cvParagraph($entry['title'], [
        'bold' => true,
        'font-size' => '10pt',
    ], 'CVEntryTitle'));

    $content->addParagraph(cvParagraph($entry['institution'], [
        'font-size' => '8.5pt',
        'color' => '#666666',
    ], 'CVEducationInstitution'));
}

addMainHeading($content, 'ZUSATZQUALIFIKATIONEN');
$qualifications = new ListElement('bullet');
foreach ($cv['qualifications'] as $qualification) {
    $paragraph = new Paragraph();
    $paragraph->addText($qualification, [
        'font-family' => 'Arial',
        'font-size' => '8.5pt',
        'color' => '#333333',
    ]);
    $qualifications->addItem($paragraph);
}
$content->addElement($qualifications);

// Insert both generated content blocks into placeholders defined by the ODT
// template. This separates LibreOffice-based layout design from PHP-driven
// document content and styling.
$template->setElement('cv_sidebar', $sidebar);
$template->setElement('cv_content', $content);

// Save a native OpenDocument Text file. The generated CV remains editable in
// LibreOffice and other ODF-compatible applications.
$template->save('samples/output/output_21_cvProfile.odt');

echo "Document generated successfully: output/output_21_cvProfile.odt\n";
