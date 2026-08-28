<?php

/**
 * Sample 25 - Instantiate a native CV section with local template values.
 *
 * The LibreOffice-authored section remains the visible prototype. Each call
 * clones its native structure, rewrites identities, and binds only that clone.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/sample_25_sectionClone.odt');

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

$outputPath = __DIR__ . '/output/output_25_sectionInstantiation.odt';
$template->save($outputPath);

echo "✅ samples/output/output_25_sectionInstantiation.odt generated successfully\n";
