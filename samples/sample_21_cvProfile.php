<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\PageLayoutOdtTemplate;

$template = new PageLayoutOdtTemplate('samples/templates/template_21_cvProfile.odt');
$template->setPageMargins('0cm', '0.8cm', '0cm', '0cm');

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
 * Create a styled paragraph while keeping the sample code compact.
 */
function cvParagraph(string $text, array $textStyle = [], array $paragraphStyle = []): Paragraph
{
    $styleName = 'cv_' . substr(md5(json_encode($paragraphStyle)), 0, 8);
    $paragraph = new Paragraph($styleName, $paragraphStyle);
    $paragraph->addText($text, array_merge([
        'font-family' => 'Arial',
    ], $textStyle));

    return $paragraph;
}

/**
 * Add a heading to the dark sidebar.
 */
function addSidebarHeading(RichText $rich, string $title): void
{
    $rich->addParagraph(cvParagraph($title, [
        'bold' => true,
        'font-size' => '10pt',
        'color' => '#ffffff',
    ], [
        'margin-top' => '0.16cm',
        'margin-bottom' => '0.04cm',
        'line-height' => '100%',
    ]));
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
    $rich->addParagraph(cvParagraph('', [], ['margin-bottom' => '0.05cm']));
}

/**
 * Add a section heading to the main content column.
 */
function addMainHeading(RichText $rich, string $title, string $marginTop = '0.45cm'): void
{
    $rich->addParagraph(cvParagraph($title, [
        'bold' => true,
        'font-size' => '13pt',
        'color' => '#111111',
    ], [
        'margin-top' => $marginTop,
        'margin-bottom' => '0.10cm',
        'padding-bottom' => '0.03cm',
        'line-height' => '100%',
        'border-bottom' => '1.5pt solid #12324a',
    ]));
}

// Build the dark sidebar as one rich ODT content block.
$sidebar = new RichText();

$sidebar->addParagraph(cvParagraph($cv['personal']['name'], [
    'bold' => true,
    'font-size' => '16pt',
    'color' => '#ffffff',
], [
    'margin-bottom' => '0.10cm',
    'line-height' => '100%',
]));

$sidebar->addImage(new ImageElement($cv['personal']['photo'], [
    'width' => '3.4cm',
    'height' => '3.4cm',
    'anchor' => 'as-char',
    'align' => 'left',
]));

$sidebar->addParagraph(cvParagraph('° ' . $cv['personal']['birth'], [
    'font-size' => '8.5pt',
    'color' => '#ffffff',
], [
    'margin-top' => '0.05cm',
    'margin-bottom' => '0.10cm',
]));

addSidebarHeading($sidebar, 'KONTAKT');
foreach ([
    'E-Mail: ' . $cv['personal']['email'],
    'Telefon: ' . $cv['personal']['phone'],
    'Ort: ' . $cv['personal']['city'],
] as $line) {
    $sidebar->addParagraph(cvParagraph($line, [
        'font-size' => '8.5pt',
        'color' => '#ffffff',
    ], [
        'margin-bottom' => '0.02cm',
        'line-height' => '105%',
    ]));
}

addSidebarList($sidebar, 'PLUSPUNKTE', $cv['plus_points']);
addSidebarList($sidebar, 'SOFT SKILLS', $cv['soft_skills']);

addSidebarHeading($sidebar, 'FACHKOMPETENZEN');
foreach ($cv['skills'] as $skill) {
    $rating = str_repeat('★', $skill['level']) . str_repeat('☆', 5 - $skill['level']);
    $paragraph = new Paragraph('cv_skill', [
        'margin-bottom' => '0.02cm',
        'line-height' => '105%',
    ]);
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

// Build the main CV column independently from the template layout.
$content = new RichText();

addMainHeading($content, 'PROFIL', '0cm');
$content->addParagraph(cvParagraph($cv['profile'], [
    'font-size' => '9pt',
    'color' => '#333333',
], [
    'margin-bottom' => '0.10cm',
    'line-height' => '110%',
]));

addMainHeading($content, 'BERUFSERFAHRUNG');
foreach ($cv['experience'] as $index => $entry) {
    $content->addParagraph(cvParagraph($entry['period'], [
        'bold' => true,
        'font-size' => '8.5pt',
        'color' => '#444444',
    ], [
        'margin-top' => $index === 0 ? '0.05cm' : '0.14cm',
        'margin-bottom' => '0.01cm',
        'line-height' => '100%',
    ]));

    $content->addParagraph(cvParagraph($entry['position'], [
        'bold' => true,
        'font-size' => '10.5pt',
        'color' => '#111111',
    ], [
        'margin-bottom' => '0.01cm',
        'line-height' => '100%',
    ]));

    $content->addParagraph(cvParagraph($entry['company'], [
        'font-size' => '8.5pt',
        'color' => '#666666',
    ], [
        'margin-bottom' => '0.03cm',
        'line-height' => '100%',
    ]));

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
    ], [
        'margin-top' => $index === 0 ? '0.05cm' : '0.14cm',
        'margin-bottom' => '0.01cm',
        'line-height' => '100%',
    ]));

    $content->addParagraph(cvParagraph($entry['title'], [
        'bold' => true,
        'font-size' => '10pt',
    ], [
        'margin-bottom' => '0.01cm',
        'line-height' => '100%',
    ]));

    $content->addParagraph(cvParagraph($entry['institution'], [
        'font-size' => '8.5pt',
        'color' => '#666666',
    ], [
        'margin-bottom' => '0.04cm',
        'line-height' => '100%',
    ]));
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

// The LibreOffice template defines the columns; PHP controls page geometry and structured content.
$template->setElement('cv_sidebar', $sidebar);
$template->setElement('cv_content', $content);
$template->save('samples/output/output_21_cvProfile.odt');

echo "Document generated successfully: output/output_21_cvProfile.odt\n";
