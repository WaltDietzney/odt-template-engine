<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_S01b_cv_structured.odt');

$template->setDocumentDefaults([
    'text' => [
        'font-family' => 'Arial',
    ],
]);

// S01b flow contract: the authored paragraph styles form a native chain so
// Writer can move a complete entry when the remaining page space is too small.
// No page number or manual page break is calculated here.
foreach (['CVDate', 'P7', 'P4', 'P13'] as $styleName) {
    $template->styles()->updateParagraph($styleName, [
        'keep-with-next' => 'always',
        'keep-together' => 'always',
    ]);
}
$template->styles()->updateParagraph('CVQualification', [
    'keep-with-next' => 'always',
    'keep-together' => 'always',
]);

$cv = [
    'personal' => [
        'first_name' => 'Andrew',
        'last_name' => 'Thompson',
        'birth' => '12.04.1984',
        'city' => 'Sydney, Australia',
        'email' => 'andrew.t@example.com',
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


/**
 * VTD-00 / S01b: reproducible multi-page stress fixture.
 * All added records are fictional. The original sample and ODT template remain untouched.
 * This fixture exercises the same nested Writer-section instantiation paths.
 */
$extraJobs = [
    ['duration' => '2009 – 2012', 'name' => 'Project Coordinator', 'company' => 'Coastal Solutions, Adelaide',
     'activities' => [
         'Coordinated cross-functional delivery plans, weekly risk reviews, and supplier dependencies across several workstreams.',
         'Prepared detailed status reporting and facilitated change-control meetings with business stakeholders.',
         'Maintained programme documentation and supported acceptance testing for major platform releases.',
         'Tracked project milestones and escalated delivery constraints before they affected customer commitments.',
     ]],
    ['duration' => '2007 – 2009', 'name' => 'Operations Analyst', 'company' => 'Southern Technology Group, Perth',
     'activities' => [
         'Analysed service processes and documented improvement opportunities with operational teams.',
         'Supported implementation planning for new reporting systems and internal process changes.',
         'Compiled performance dashboards and presented findings to department managers.',
         'Facilitated workshops to align technical requirements with day-to-day business needs.',
     ]],
    ['duration' => '2005 – 2007', 'name' => 'Business Support Officer', 'company' => 'Metro Business Services, Sydney',
     'activities' => [
         'Supported project scheduling, documentation, and stakeholder communication.',
         'Coordinated training sessions and maintained materials for process rollouts.',
         'Assisted with vendor coordination and tracked open actions through to resolution.',
         'Prepared monthly summaries of project expenditure and resource utilisation.',
     ]],
];
$cv['experience'] = array_merge($cv['experience'], $extraJobs);

$cv['education'][] = [
    'duration' => '2004 – 2005',
    'name' => 'Graduate Certificate in Business Analytics',
    'institution' => 'Fictional Institute of Management, Australia',
];

$cv['qualifications'] = array_merge($cv['qualifications'], [
    ['name' => 'Advanced programme governance', 'description' => 'Fictional professional development programme covering portfolio controls, assurance, and benefits tracking.'],
    ['name' => 'Facilitation and negotiation', 'description' => 'Extended training in stakeholder workshops, collaborative decision-making, and complex negotiations.'],
    ['name' => 'Community leadership', 'description' => 'Volunteer coordination for fictional community technology mentoring programmes.'],
    ['name' => 'Conference presentations', 'description' => 'Presented case studies on distributed project delivery and risk management at fictional industry events.'],
    ['name' => 'Continuous improvement', 'description' => 'Completed a series of workshops on service design, process mapping, and measurable operational improvements.'],
    ['name' => 'Knowledge sharing', 'description' => 'Authored internal guidance on delivery governance, onboarding, and project reporting standards.'],
]);


function cvParagraph(string $text, ?string $paragraphStyle = null): Paragraph
{
    $paragraph = new Paragraph($paragraphStyle);
    $paragraph->addText($text);

    return $paragraph;
}

function addSidebarHeading(RichText $sidebar, string $heading): void
{
    $sidebar->addParagraph(cvParagraph($heading, 'S01bSidebarHeading'));
}

/** @param list<string> $items */
function addSidebarList(RichText $sidebar, string $heading, array $items): void
{
    addSidebarHeading($sidebar, $heading);
    $list = new ListElement('bullet');
    foreach ($items as $item) {
        $list->addItem(cvParagraph($item, 'S01bSidebarListItem'));
    }
    $sidebar->addElement($list);
}

// The sidebar is PHP-owned content inside template-authored Writer Frames.
// Typography and spacing stay in the template's S01bSidebar* paragraph styles.
$sidebarPage1 = new RichText();
addSidebarHeading($sidebarPage1, 'CONTACT');
$sidebarPage1->addParagraph(cvParagraph($cv['personal']['email'], 'S01bSidebarLine'));
$sidebarPage1->addParagraph(cvParagraph($cv['personal']['phone'], 'S01bSidebarLine'));
$sidebarPage1->addParagraph(cvParagraph($cv['personal']['city'], 'S01bSidebarLine'));
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
addSidebarHeading($sidebarPage1, 'PROFESSIONAL SKILLS');
foreach ([
    ['name' => 'Portfolio management', 'level' => 5],
    ['name' => 'Risk planning', 'level' => 4],
    ['name' => 'Stakeholder engagement', 'level' => 4],
] as $skill) {
    $rating = str_repeat('★', $skill['level']) . str_repeat('☆', 5 - $skill['level']);
    // Alignment is template-owned: S01bSidebarSkill defines the 5.2cm tab stop.
    $paragraph = new Paragraph('S01bSidebarSkill');
    $paragraph->addText($skill['name'])->addTab()->addText($rating);
    $sidebarPage1->addParagraph($paragraph);
}

addSidebarList($sidebarPage1, 'LICENSES', [
    'PMP · 2018',
    'PRINCE2 Practitioner · 2016',
]);
addSidebarList($sidebarPage1, 'LANGUAGES', [
    'English · native',
    'German · professional',
]);

$sidebarPage2 = new RichText();
$sidebarPage2->addParagraph(cvParagraph('ANDREW THOMPSON', 'S01bSidebarName'));
$sidebarPage2->addParagraph(cvParagraph('Senior Project Manager', 'S01bSidebarLine'));
$sidebarPage2->addParagraph(cvParagraph($cv['personal']['email'], 'S01bSidebarLine'));
$sidebarPage2->addParagraph(cvParagraph($cv['personal']['phone'], 'S01bSidebarLine'));

// The authored CVImage frame owns its geometry and placement. The current
// compatibility facade requires explicit authored dimensions to avoid its
// legacy 5cm x 3cm defaults while replacing only the image resource.
$template->replaceImageByName('CVImage', __DIR__ . '/../assets/BFoto.png', [
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

$template->bookmark('Extract')->replaceText('PROFILE');
$template->bookmark('ExtractJobHeadline')->replaceText('Senior Project Manager with 10+ years of delivery leadership');
$template->bookmark('ExtractJobDescription')->replaceText(
    'Experienced project manager specialising in agile transformation, multi-project delivery, and international teams.'
);

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
        // Flatten one job entry into one direct Section flow. Writer can form
        // a continuous keep-with-next chain across these paragraphs; a nested
        // list/Section container would be allowed to split independently.
        $jobContent = new RichText();
        $jobContent->addParagraph(cvParagraph($cv['experience'][$index]['duration'], 'CVDate'));
        $jobContent->addParagraph(cvParagraph($cv['experience'][$index]['name'], 'P7'));
        $jobContent->addParagraph(cvParagraph($cv['experience'][$index]['company'], 'P4'));
        $activityList = new ListElement('bullet');
        foreach ($activities as $activity) {
            $activityList->addItem(cvParagraph($activity, 'P13'));
        }
        $jobContent->addElement($activityList);
        $experience->replaceContent($jobContent);
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
$template->save(__DIR__ . '/output/output_S01b_cv_structured_3pages.odt');

echo "Document generated successfully: samples/output/output_S01b_cv_structured_3pages.odt\n";
