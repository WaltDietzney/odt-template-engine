<?php

/**
 * Sample 25 - Complete CV showcase with scalar and native section collections.
 *
 * This demonstrates a LibreOffice-authored CV template, scalar replacement,
 * native ExperienceEntry and nested ActivityEntry collection instantiation,
 * collection finalization, and preservation of native ODT structure.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/sample_25_sectionClone.odt');

$template->assign([
    'firstname' => 'Max',
    'lastname' => 'Mustermann',
    'profession' => 'Senior Projektmanager',
    'phone' => '+49 151 12345678',
    'adress' => 'Musterstraße 12, 33602 Bielefeld',
    'mail' => 'max.mustermann@example.com',
]);

$experiences = [
    [
        'note' => 'Aktuelle Position',
        'position' => 'Senior Projektmanager',
        'activities' => [
            'Leitung eines interdisziplinären Projektteams.',
            'Teams koordiniert und Fristen eingehalten.',
            'Agiles Arbeiten eingeführt und Produktivität gesteigert.',
        ],
    ],
    [
        'note' => 'Vorherige Position',
        'position' => 'Marketing-Spezialist',
        'activities' => [
            'Entwicklung digitaler Marketingkampagnen.',
            'Zusammenarbeit mit internen Teams und externen Agenturen.',
        ],
    ],
    [
        'note' => 'Frühere Position',
        'position' => 'Projektkoordinator',
        'activities' => [
            'Planung von Projekten und Koordination der Beteiligten.',
            'Fristen und Budgets zuverlässig überwacht.',
            'Arbeitsabläufe dokumentiert und verbessert.',
            'Kunden- und Teamkommunikation strukturiert.',
        ],
    ],
];

$experienceInstances = $template->section('ExperienceEntry')->instantiateMany(array_map(
    static fn (array $experienceData): array => [
        'note' => $experienceData['note'],
        'position' => $experienceData['position'],
    ],
    $experiences
));

foreach ($experienceInstances as $index => $experience) {
    $experience->section('ActivityEntry')->instantiateMany(array_map(
        static fn (string $activity): array => ['activity' => $activity],
        $experiences[$index]['activities']
    ));
}

$template->render();

$outputPath = __DIR__ . '/output/output_25_sectionInstantiation.odt';
$template->save($outputPath);

echo "✅ samples/output/output_25_sectionInstantiation.odt generated successfully\n";
