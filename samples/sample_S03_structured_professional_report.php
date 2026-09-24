<?php

declare(strict_types=1);

/**
 * S03-A — Structured Professional Report
 *
 * This showcase updates a substantial report authored and designed in
 * LibreOffice Writer. PHP addresses the template's semantic targets; it does
 * not rebuild the report layout, lists, tables, or image geometry.
 *
 * Writer owns the document structure and formatting. The application supplies
 * User Field values, bounded bookmark text, table data, and one prepared image
 * resource. Table population uses the existing native Writer tables, while
 * the report's authored list Sections remain Writer-owned content.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_S03_structured_professional_report.odt');

$userFields = [
    'organization_name' => 'Meridian Learning Foundation',
    'organization_short_name' => 'MERIDIAN',
    'report_title' => 'Community Pathways Performance Report',
    'report_year' => '2027',
    'program_name' => 'Bridgeway Skills Programme',
    'report_period' => 'January–December 2027',
    'report_status' => 'Final Report',
    'report_date' => '31 January 2028',
];
foreach ($userFields as $name => $value) {
    $template->setUserField($name, $value);
}

// These bounded text values are exposed by Writer-authored bookmark ranges.
$bookmarks = [
    'ExecutiveSummaryTitle' => 'Executive Summary',
    'ExecutiveSummaryIntro' => 'Bridgeway Skills supported 272 participants across three delivery locations during 2027, combining coaching, practical learning, and employer contact.',
    'ExecutiveSummaryApproach' => 'The updated report retains the authored professional structure while changing the report identity, results, activities, and management reading through native targets.',
    'ExecutiveSummaryResults' => 'Completion reached 89%, and 71% of participants progressed into employment or further training.',
    'KpiParticipantsValue' => '272',
    'KpiParticipantsLabel' => 'Participants',
    'KpiCompletionRateValue' => '89%',
    'KpiCompletionRateLabel' => 'Completion rate',
    'KpiPositiveOutcomesValue' => '71%',
    'KpiPositiveOutcomesLabel' => 'Positive outcomes',
    'KpiEmployerPartnersValue' => '47',
    'KpiEmployerPartnersLabel' => 'Employer partners',
    'ExecutiveSummaryKeyFinding' => 'KEY FINDING',
    'ExecutiveSummaryKeyFindingText' => 'Participants receiving coordinated coaching and employer-facing support showed the strongest progression.',
    'ManagementReadingTitle' => 'Management reading',
    'ManagementReadingText' => 'The 2027 results point to the value of linking practical learning with earlier employer contact and targeted confidence-building support.',
    'ProgramOverviewTitle' => 'Program Overview & Delivery',
    'ProgramOverviewIntro' => 'Bridgeway Skills combined individual guidance, practical workshops, and employer-facing activity to support progression across three fictional delivery locations.',
    'ReportingPeriodLabel' => 'Reporting period',
    'ReportingPeriodValue' => 'January–December 2027',
    'LocationsLabel' => 'Locations',
    'LocationsValue' => '3 locations',
    'DeliveryTeamLabel' => 'Delivery team',
    'DeliveryTeamValue' => '14 people',
    'EmployerPartnersLabel' => 'Employer partners',
    'EmployerPartnersValue' => '47 partners',
    'ProgramObjectivesTitle' => 'Program Objectives',
    'ActivitiesDeliveredTitle' => 'Activities Delivered',
    'DeliveryObservationTitle' => 'Delivery observation',
    'DeliveryObservationText1' => 'The expanded activity mix increased opportunities for participants to practise skills in different settings.',
    'DeliveryObservationText2' => 'Employer-facing activity remained concentrated around practical preparation and progression conversations.',
    'PerformanceOutcomesTitle' => 'Performance & Outcomes',
    'PerformanceAgainstTargetsTitle' => 'Performance against targets',
    'ParticipantOutcomesTitle' => 'Participant outcomes',
    'ParticipantOutcomesCaption' => 'Participant outcomes · 2027',
    'ParticipantOutcomesText' => 'Employment and further-training outcomes together accounted for 71% of recorded progression, with active progression representing a further 17%.',
    'FindingsAnalysisTitle' => 'Findings & Analysis',
    'FindingIntegratedSupportTitle' => '1. Integrated support produced stronger outcomes',
    'FindingIntegratedSupportText1' => 'Participants who received both individual coaching and employer-facing support showed the clearest progression through the program.',
    'FindingIntegratedSupportText2' => 'This pattern supports maintaining a connected delivery journey rather than treating activities as separate interventions.',
    'FindingDigitalConfidenceTitle' => '2. Digital confidence remains uneven',
    'FindingDigitalConfidenceText1' => 'Digital practice improved confidence for many participants, but starting points and preferred learning routes remained varied.',
    'FindingDigitalConfidenceText2' => 'Targeted practice should therefore remain part of ordinary delivery instead of being reserved for a single specialist session.',
    'ProgramInsightTitle' => 'PROGRAM INSIGHT',
    'ProgramInsightText' => 'employers became involved before the formal recruitment stage rather than only receiving completed candidate profiles.',
    'ChallengesTitle' => 'Challenges',
    'ManagementImplicationTitle' => 'Management implication',
    'ManagementImplicationText' => 'The response should remain selective: protect flexible attendance support, build digital practice into ordinary delivery, and focus employer development where progression opportunities are most constrained.',
    'RecommendationsOutlookTitle' => 'Recommendations & Outlook',
    'RecommendationsTitle' => 'Recommendations',
    'ReportOutlookTitle' => 'Outlook 2028',
    'ReportOutlookText1' => 'The next reporting period should consolidate the stronger activity model while extending purposeful employer contact and practical digital support.',
    'ReportOutlookText2' => 'A clearer follow-up view will help Meridian distinguish immediate completion from sustained progression into employment or further training.',
    'ReportClosing' => 'Demonstration report · fictional organization and data',
];
foreach ($bookmarks as $name => $value) {
    $template->bookmark($name)->replaceText($value);
}

// Writer owns the header, columns, native header row, and row formatting.
$template->table('ActivitiesDeliveredTable')->populate([
    ['Mentoring clinics', '980', '210'],
    ['Digital practice labs', '72', '198'],
    ['Employer roundtables', '12', '110'],
    ['Sector visits', '9', '96'],
    ['Peer learning circles', '28', '144'],
    ['Progression workshops', '34', '173'],
]);

// Four results replace the five authored ordinary source rows, preserving the
// native header and the Writer-authored table/cell/paragraph formatting.
$template->table('PerformanceAgainstTargetsTable')->populate([
    ['Participants enrolled', '260', '272', 'Achieved'],
    ['Program completion', '82%', '89%', 'Above target'],
    ['Employment/training outcome', '65%', '71%', 'Above target'],
    ['Employer partners', '40', '47', 'Above target'],
]);

$template->replaceImageByName(
    'ParticipantOutcomesImage',
    __DIR__ . '/assets/s03-participant-outcomes-2027.png',
    [
        'width' => '15.799cm',
        'height' => '10.007cm',
    ]
);

$template->setMeta([
    'title' => 'Community Pathways Performance Report 2027',
    'subject' => 'S03 Writer-authored structured professional report',
    'description' => 'Fictional S03 showcase updated through native document-semantic targets.',
    'language' => 'en-US',
]);

$outputPath = __DIR__ . '/output/output_S03_structured_professional_report.odt';
$template->save($outputPath);

echo "Saved S03 structured professional report to {$outputPath}.\n";
