<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_S01b_cv_structured.odt');

$cv = [
    'personal' => [
        'first_name' => 'Andrew',
        'last_name' => 'Thompson',
        'birth' => '12.04.1984',
        'city' => 'Sydney, Australia',
        'email' => 'andrew.thompson@example.com',
        'phone' => '+61 412 555 018',
    ],
    'experience' => [
        [
            'duration' => '2021 – present',
            'name' => 'Senior Project Manager',
            'company' => 'Harbour Digital, Sydney',
            'activities' => [
                'Leading international delivery teams across a portfolio of digital transformation projects.',
                'Introduced agile planning practices that improved delivery predictability and stakeholder visibility.',
                'Managing budgets of up to AUD 5 million across parallel programmes.',
            ],
        ],
        [
            'duration' => '2017 – 2021',
            'name' => 'Project Manager',
            'company' => 'Southern Cross Consulting, Melbourne',
            'activities' => [
                'Delivered customer-platform programmes for public and private-sector organisations.',
                'Coordinated product, engineering, design, and external delivery partners.',
            ],
        ],
        [
            'duration' => '2014 – 2017',
            'name' => 'Junior Project Manager',
            'company' => 'Pacific Systems, Brisbane',
            'activities' => [
                'Supported planning, risk management, and communication for software projects.',
                'Built reporting routines that made milestones and dependencies visible to project teams.',
            ],
        ],
        [
            'duration' => '2012 – 2014',
            'name' => 'Assistant Project Manager',
            'company' => 'Northshore Technology, Sydney',
            'activities' => [
                'Maintained project documentation and supported sprint and release coordination.',
                'Prepared stakeholder updates and captured decisions for distributed teams.',
            ],
        ],
    ],
    'education' => [
        [
            'duration' => '2010 – 2012',
            'name' => 'Master of Business Administration',
            'institution' => 'University of Sydney',
        ],
        [
            'duration' => '2006 – 2010',
            'name' => 'Bachelor of Business Administration',
            'institution' => 'University of Technology Sydney',
        ],
    ],
    'qualifications' => [
        ['name' => 'Volunteer work', 'description' => 'Mentor for early-career project managers.'],
        ['name' => 'Awards', 'description' => 'Delivery Excellence Award, Harbour Digital, 2023.'],
        ['name' => 'Publications', 'description' => 'Contributor to the Australian Project Leadership Review.'],
    ],
];

/** @param array<string, mixed> $style */
function cvParagraph(string $text, array $style = []): Paragraph
{
    $paragraph = new Paragraph();
    $paragraph->addText($text, array_merge([
        'font-family' => 'Arial',
    ], $style));

    return $paragraph;
}

function addSidebarHeading(RichText $sidebar, string $heading): void
{
    $sidebar->addParagraph(cvParagraph($heading, [
        'bold' => true,
        'font-family' => 'Arial',
        'font-size' => '9pt',
        'color' => '#ffffff',
    ]));
}

/** @param list<string> $items */
function addSidebarList(RichText $sidebar, string $heading, array $items): void
{
    addSidebarHeading($sidebar, $heading);
    $list = new ListElement('bullet');
    foreach ($items as $item) {
        $list->addItem(cvParagraph($item, [
            'font-family' => 'Arial',
            'font-size' => '8pt',
            'color' => '#ffffff',
        ]));
    }
    $sidebar->addElement($list);
}

$sidebarPage1 = new RichText();
$sidebarPage1->addParagraph(cvParagraph('ANDREW THOMPSON', [
    'bold' => true,
    'font-family' => 'Arial',
    'font-size' => '13pt',
    'color' => '#ffffff',
]));
addSidebarHeading($sidebarPage1, 'CONTACT');
$sidebarPage1->addParagraph(cvParagraph($cv['personal']['email'], ['font-size' => '8pt', 'color' => '#ffffff']));
$sidebarPage1->addParagraph(cvParagraph($cv['personal']['phone'], ['font-size' => '8pt', 'color' => '#ffffff']));
$sidebarPage1->addParagraph(cvParagraph($cv['personal']['city'], ['font-size' => '8pt', 'color' => '#ffffff']));
addSidebarList($sidebarPage1, 'PLUS POINTS', [
    'Strategic project leadership',
    'International team coordination',
    'Agile transformation',
]);
addSidebarList($sidebarPage1, 'SOFT SKILLS', [
    'Clear communication',
    'Calm decision-making',
    'Collaborative leadership',
]);
addSidebarList($sidebarPage1, 'PROFESSIONAL SKILLS', [
    'Portfolio management',
    'Risk and dependency planning',
    'Stakeholder engagement',
]);
addSidebarList($sidebarPage1, 'LICENSES', [
    'PMP · 2018',
    'PRINCE2 Practitioner · 2016',
]);
addSidebarList($sidebarPage1, 'LANGUAGES', [
    'English · native',
    'German · professional',
]);

$sidebarPage2 = new RichText();
addSidebarHeading($sidebarPage2, 'ANDREW THOMPSON');
$sidebarPage2->addParagraph(cvParagraph('Senior Project Manager', ['font-size' => '8pt', 'color' => '#ffffff']));
$sidebarPage2->addParagraph(cvParagraph($cv['personal']['email'], ['font-size' => '8pt', 'color' => '#ffffff']));
$sidebarPage2->addParagraph(cvParagraph($cv['personal']['phone'], ['font-size' => '8pt', 'color' => '#ffffff']));

// The authored CVImage frame owns its geometry and placement. The current
// compatibility facade requires explicit authored dimensions to avoid its
// legacy 5cm x 3cm defaults while replacing only the image resource.
$template->replaceImageByName('CVImage', __DIR__ . '/../assets/WaltDietzney.png', [
    'width' => '4.001cm',
    'height' => '3.799cm',
]);

// {{Image}} is a separate authored insertion point in the sidebar frame.
$template->setElement('Image', new ImageElement(__DIR__ . '/../assets/WaltDietzney.png', [
    'width' => '2.4cm',
    'height' => '2.4cm',
    'anchor' => 'as-char',
]));
$template->setElement('CVSidebarPage1', $sidebarPage1);
$template->setElement('CVSidebarPage2', $sidebarPage2);

$template->assign([
    'VName' => $cv['personal']['first_name'],
    'Name' => $cv['personal']['last_name'],
    'BDate' => $cv['personal']['birth'],
    'BTown' => $cv['personal']['city'],
]);

if ($cv['experience'] === []) {
    $template->section('Experience')->instantiateMany([]);
} else {
    $experienceInstances = $template->section('Experience')->section('JobSection')->instantiateMany(
        array_map(
            static fn (array $job): array => [
                'JobDuration' => $job['duration'],
                'JobName' => $job['name'],
                'JobCompany' => $job['company'],
            ],
            $cv['experience']
        )
    );

    foreach ($experienceInstances as $index => $experience) {
        $activities = $cv['experience'][$index]['activities'];
        $experience->section('ActivitySection')->instantiateMany(
            array_map(static fn (string $activity): array => ['Activity' => $activity], $activities)
        );
    }
}

if ($cv['education'] === []) {
    $template->section('Education')->instantiateMany([]);
} else {
    $template->section('Education')->section('EducationSection')->instantiateMany(
        array_map(
            static fn (array $entry): array => [
                'EducationDuration' => $entry['duration'],
                'EducationName' => $entry['name'],
                'EducationInstitution' => $entry['institution'],
            ],
            $cv['education']
        )
    );
}

if ($cv['qualifications'] === []) {
    $template->section('AdditionalQualifications')->instantiateMany([]);
} else {
    $template->section('AdditionalQualifications')->section('QualificationSection')->instantiateMany(
        array_map(
            static fn (array $qualification): array => [
                'QualificationName' => $qualification['name'],
                'QualificationDescription' => $qualification['description'],
            ],
            $cv['qualifications']
        )
    );
}

$template->render();
$template->save(__DIR__ . '/output/output_S01b_cv_structured.odt');

echo "Document generated successfully: samples/output/output_S01b_cv_structured.odt\n";
