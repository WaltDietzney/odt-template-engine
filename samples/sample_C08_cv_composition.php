<?php

declare(strict_types = 1);

require_once __DIR__  .  '/bootstrap.php';

use OdtTemplateEngine\PageLayoutOdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\SectionElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\ImageElement;

$t = new PageLayoutOdtTemplate(__DIR__ . '/templates/template_C06_section_authoring.odt');
$t->styles()->defineGradient('CVSidebarGradient', ['type' => 'linear', 'start-color' => '#233f70', 'end-color' => '#152b4e', 'angle' => 90]);
$t->styles()->defineFillImage('CVFooterBitmap', __DIR__ . '/../assets/cv-footer-texture.png');
$gradient = ['type' => 'gradient', 'name' => 'CVSidebarGradient'];
$footer = ['min-height' => '0.6cm', 'spacing' => '0.3cm', 'margin-left' => '8.5cm', 'background' => ['type' => 'bitmap', 'name' => 'CVFooterBitmap', 'repeat' => 'stretch']];
function para(string $style,  string $value): Paragraph
{
    return (new Paragraph($style))->addText($value);
}
foreach ([
 'CVFooter' => ['font-size' => '9pt', 'color' => '#233f70', 'text-align' => 'right'],
 'SidePhoto' => ['text-align' => 'center', 'margin-top' => '0.8cm', 'margin-bottom' => '0.3cm'],
 'SideName' => ['margin-top' => '0.8cm', 'font-size' => '14pt', 'bold' => true, 'color' => '#ffffff'],
 'SideHeading' => ['font-size' => '11pt', 'bold' => true, 'color' => '#ffffff', 'margin-top' => '0.65cm', 'margin-bottom' => '0.15cm'],
 'SideLine' => ['font-size' => '9pt', 'color' => '#ffffff'],
 'CVName' => ['margin-top' => '0.8cm', 'margin-bottom' => '0.15cm', 'font-size' => '23pt', 'bold' => true, 'color' => '#233f70'],
 'CVHeading' => ['font-size' => '14pt', 'bold' => true, 'color' => '#233f70', 'margin-top' => '0.5cm', 'margin-bottom' => '0.2cm'],
 'CVBody' => ['font-size' => '11pt', 'margin-bottom' => '0.15cm'],
 'CVJob' => ['font-size' => '12pt', 'bold' => true, 'margin-top' => '0.3cm'],
 'CVDate' => ['font-size' => '9pt', 'color' => '#536878'],
] as $name => $options) {
    $t->styles()->defineParagraph($name, $options + ['font-family' => 'Arial']);
}
$t->definePageLayout('FirstLayout', ['page-width' => '21cm', 'page-height' => '29.7cm', 'margin-top' => '0cm', 'margin-left' => '0cm', 'margin-right' => '2cm', 'margin-bottom' => '2cm', 'header' => ['height' => '0.6cm', 'spacing' => '0.499cm', 'dynamic-spacing' => false], 'footer' => $footer]);
$box = (new DrawTextBox('Sidebar', ['padding' => '0.5cm']))->setFrameLayout(['anchor' => 'paragraph', 'width' => '8cm', 'height' => '29.7cm', 'horizontal' => ['offset' => '0cm', 'relative-to' => 'paragraph'], 'vertical' => ['offset' => '0cm', 'relative-to' => 'paragraph'], 'wrap' => 'parallel'])->setBackground($gradient)->setAllowOverlap(true);
$box->addElement((new Paragraph('SidePhoto'))->addElement((new ImageElement(__DIR__ . '/../assets/BFoto.png', ['width' => '3.4cm', 'height' => '4.08cm']))->setFrameLayout(['anchor' => 'as-char'])));
foreach (['CONTACT' => ['{{Email}}', '{{Phone}}', '{{BTown}}'], 'PLUS POINTS' => ['Strategic project leadership', 'International team coordination', 'Agile transformation'], 'SOFT SKILLS' => ['Clear communication', 'Calm decision-making', 'Collaborative leadership'], 'PROFESSIONAL SKILLS' => ['Portfolio management', 'Risk planning', 'Stakeholder engagement'], 'LICENSES' => ['PMP · 2018', 'PRINCE2 Practitioner · 2016'], 'LANGUAGES' => ['English · native', 'German · professional']] as $heading => $items) {
 $box->addElement(para('SideHeading', $heading));
 if ($heading==='CONTACT') { foreach($items as $item) $box->addElement(para('SideLine', $item)); }
 else { $list = new ListElement('bullet'); foreach($items as $item) $list->addItem(para('SideLine', $item)); $box->addElement($list); }
}
$t->definePageStyle('FirstPage', 'FirstLayout', ['header' => $box, 'footer' => para('CVFooter', '{{VName}} {{Name}} · Curriculum Vitae')]);
$t->definePageLayout('NextLayout', ['page-width' => '21cm', 'page-height' => '29.7cm', 'margin-top' => '0cm', 'margin-left' => '0cm', 'margin-right' => '2cm', 'margin-bottom' => '2cm', 'header' => ['min-height' => '1.6cm', 'spacing' => '1.499cm', 'dynamic-spacing' => false], 'footer' => $footer]);
$next = (new DrawTextBox('SidebarNext', ['padding' => '0.5cm']))->setFrameLayout(['anchor' => 'char', 'width' => '8cm', 'height' => '29.7cm', 'horizontal' => ['offset' => '0cm', 'relative-to' => 'paragraph'], 'vertical' => ['offset' => '0cm', 'relative-to' => 'paragraph'], 'wrap' => 'parallel'])->setBackground($gradient)->setAllowOverlap(true);
foreach (['{{VName}} {{Name}}', '{{Role}}', '{{Email}}', '{{Phone}}'] as $i => $line) {
    $next->addElement(para($i === 0 ? 'SideName' : 'SideLine', $line));
}
$t->definePageStyle('NextPage', 'NextLayout', ['header' => $next, 'footer' => para('CVFooter', '{{VName}} {{Name}} · Curriculum Vitae')]);
$t->setNextPageStyle('FirstPage', 'NextPage');
$t->assignPageStyle('Standard', 'FirstPage');
$t->styles()->defineSection('MainFlow', ['margin-left' => '8.5cm']);
$main = (new SectionElement('CVMain'))->setSectionStyle('MainFlow');
$main->addElement(para('CVName', '{{VName}} {{Name}}'))->addElement(para('CVBody', '{{Role}}'))->addElement(para('CVDate', '{{BDate}} · {{BTown}}'));
$main->addSection((new SectionElement('Profile'))->addElement(para('CVHeading', 'PROFILE'))->addElement(para('CVJob', '{{ProfileHeadline}}'))->addElement(para('CVBody', '{{ProfileDescription}}')));
$job = (new SectionElement('JobSection'))->addElement(para('CVDate', '{{JobDuration}}'))->addElement(para('CVJob', '{{JobName}}'))->addElement(para('CVBody', '{{JobCompany}}'))->addSection((new SectionElement('ActivitySection'))->addElement(para('CVBody', '• {{Activity}}')));
$main->addSection((new SectionElement('Experience'))->addElement(para('CVHeading', 'EXPERIENCE'))->addSection($job));
$main->addSection((new SectionElement('Education'))->addElement(para('CVHeading', 'EDUCATION'))->addSection((new SectionElement('EducationSection'))->addElement(para('CVDate', '{{EducationDuration}}'))->addElement(para('CVJob', '{{EducationName}}'))->addElement(para('CVBody', '{{EducationInstitution}}'))));
$main->addSection((new SectionElement('AdditionalQualifications'))->addElement(para('CVHeading', 'QUALIFICATIONS'))->addSection((new SectionElement('QualificationSection'))->addElement(para('CVJob', '{{QualificationName}}'))->addElement(para('CVBody', '{{QualificationDescription}}'))));
$t->appendSection($main);
$templatePath = getenv('C08_TEMPLATE_OUTPUT') ?: __DIR__  .  '/output/template_C08_cv_composition.odt';
$t->save($templatePath);
$t->cleanup();

// Reopen the generated template and populate its native section prototypes.
$template = new OdtTemplateEngine\OdtTemplate($templatePath);
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
        ['name' => 'Volunteer work',  'description' => 'Mentor for early-career project managers.'],
        ['name' => 'Awards',  'description' => 'Delivery Excellence Award, Harbour Digital, 2023.'],
        ['name' => 'Publications',  'description' => 'Contributor to the Australian Project Leadership Review.'],
    ],
];

$template->assign(['VName' => $cv['personal']['first_name'], 'Name' => $cv['personal']['last_name'], 'BDate' => $cv['personal']['birth'], 'BTown' => $cv['personal']['city'], 'Email' => $cv['personal']['email'], 'Phone' => $cv['personal']['phone'], 'Role' => 'Senior Project Manager', 'ProfileHeadline' => 'Senior Project Manager with 10+ years of delivery leadership', 'ProfileDescription' => 'Experienced project manager specialising in agile transformation, multi-project delivery, and international teams.']);
if ($cv['experience'] === []) {
    $template->section('CVMain')->section('Experience')->instantiateMany([]);
} else {
    $experienceInstances = $template->section('CVMain')->section('Experience')->section('JobSection')->instantiateMany(
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
            array_map(static fn (string $activity): array => ['Activity' => $activity],  $activities)
        );
    }
}

if ($cv['education'] === []) {
    $template->section('CVMain')->section('Education')->instantiateMany([]);
} else {
    $template->section('CVMain')->section('Education')->section('EducationSection')->instantiateMany(
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
    $template->section('CVMain')->section('AdditionalQualifications')->instantiateMany([]);
} else {
    $template->section('CVMain')->section('AdditionalQualifications')->section('QualificationSection')->instantiateMany(
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
$outputPath = getenv('C08_OUTPUT') ?: __DIR__  .  '/output/output_C08_cv_composition.odt';
$template->save($outputPath);
$template->cleanup();
