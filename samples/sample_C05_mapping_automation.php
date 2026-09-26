<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\ConcreteMappingPreflight;
use OdtTemplateEngine\Mapping\DependencyMapping;
use OdtTemplateEngine\Mapping\DocumentCapabilityMapping;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\Mapping\NativeObjectActionMapping;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_C05_mapping_automation.odt');
$contract = $template->inspectTemplate();

$mappings = new MappingDefinition(
    [
        new DependencyMapping(ApplicationPath::parse('customer.display_name'), 'client'),
        new DependencyMapping(ApplicationPath::parse('project.title'), 'project_title'),
        new DependencyMapping(ApplicationPath::parse('project.team[]'), 'members[]'),
        new DependencyMapping(ApplicationPath::parse('project.team[].full_name'), 'members[].name'),
        new DependencyMapping(ApplicationPath::parse('project.team[].responsibility'), 'members[].role'),
    ],
    [
        new NativeObjectActionMapping(ApplicationPath::parse('project.summary'), 'section', 'ExecutiveSummary', 'replace-content'),
        new NativeObjectActionMapping(ApplicationPath::parse('branding.logo'), 'frame', 'CompanyLogo', 'replace-image'),
    ],
    [new DocumentCapabilityMapping(ApplicationPath::parse('document.author'), 'metadata', 'creator')]
);

$application = [
    'customer' => ['display_name' => 'Northstar Studio'],
    'project' => [
        'title' => 'Template modernization',
        'summary' => (new Paragraph())->addText('The modernization keeps authored report structure in Writer while mapping application values explicitly.', ['bold' => true]),
        'team' => [
            ['full_name' => 'Anna Example', 'responsibility' => 'Template design'],
            ['full_name' => 'Ben Example', 'responsibility' => 'Integration'],
        ],
    ],
    'branding' => ['logo' => [
        'source' => dirname(__DIR__) . '/assets/Logo.png',
        'options' => [],
    ]],
    'document' => ['author' => 'Northstar Delivery Team'],
];

$preflight = (new ConcreteMappingPreflight())->preflight(
    $mappings,
    $contract,
    $application,
    $template->inspect()
);

if (!$preflight->ready()) {
    foreach ($preflight->diagnostics() as $diagnostic) {
        fwrite(STDERR, sprintf("%s: %s\n", $diagnostic->code(), $diagnostic->message()));
    }
    exit(1);
}

// The shared Phase-E invocation starts only after complete concrete preflight succeeds.
$template->automate($contract, $preflight);
$template->save(__DIR__ . '/output/output_C05_mapping_automation.odt');
