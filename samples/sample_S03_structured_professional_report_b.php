<?php

declare(strict_types=1);

/**
 * S03-B1 — Different Report Composition
 *
 * This showcase reuses the canonical LibreOffice Writer-authored S03 report
 * template for a strategic review with a different identity and composition.
 * Writer continues to own the page layout, styles, tables, and image frames;
 * PHP changes only explicitly addressed native targets.
 *
 * The example also clones one authored Section and replaces the content of
 * selected semantic Sections. It deliberately does not address generated
 * bookmark names inside the clone; application-owned clone content is supplied
 * through replaceContent().
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

function strategicParagraph(string $text, string $style): Paragraph
{
    return (new Paragraph($style))->addText($text);
}

/** @param list<string> $items */
function strategicList(array $items, string $listStyle, string $paragraphStyle): RichText
{
    $content = new RichText();
    $list = new ListElement('bullet', $listStyle);
    foreach ($items as $item) {
        $list->addItem(strategicParagraph($item, $paragraphStyle));
    }

    return $content->addElement($list);
}

$template = new OdtTemplate(__DIR__ . '/templates/template_S03_structured_professional_report.odt');

$userFields = [
    'organization_name' => 'Northbridge Community Trust',
    'organization_short_name' => 'NORTHBRIDGE',
    'report_title' => 'Strategic Review',
    'report_year' => '2028',
    'program_name' => 'ForwardWorks Initiative',
    'report_period' => 'January–December 2028',
    'report_status' => 'Strategic Review',
    'report_date' => '31 January 2029',
];
foreach ($userFields as $name => $value) {
    $template->setUserField($name, $value);
}

// These values address only bookmark ranges already authored in the template.
$bookmarks = [
    'ExecutiveSummaryTitle' => 'Strategic Summary',
    'ExecutiveSummaryIntro' => 'Northbridge Community Trust reviewed the first year of the ForwardWorks Initiative across three fictional delivery locations during 2028, with a focus on local capacity, practical progression, and durable partnerships.',
    'ExecutiveSummaryApproach' => 'The strategic review keeps the Writer-authored report shell while shifting emphasis from annual performance reporting toward the conditions needed for the next phase of delivery.',
    'ExecutiveSummaryResults' => 'Completion reached 91%, while 74% of participants moved into employment, training, or an active progression route.',
    'KpiParticipantsValue' => '286',
    'KpiParticipantsLabel' => 'Participants',
    'KpiCompletionRateValue' => '91%',
    'KpiCompletionRateLabel' => 'Completion rate',
    'KpiPositiveOutcomesValue' => '74%',
    'KpiPositiveOutcomesLabel' => 'Positive outcomes',
    'KpiEmployerPartnersValue' => '52',
    'KpiEmployerPartnersLabel' => 'Partner organisations',
    'ExecutiveSummaryKeyFinding' => 'STRATEGIC PRIORITY',
    'ExecutiveSummaryKeyFindingText' => 'The next phase should invest in local delivery capacity before expanding the program footprint.',
    'ManagementReadingTitle' => 'Management reading',
    'ManagementReadingText' => 'The review points to a transition from proving the model to strengthening the systems, partnerships, and specialist capacity that can sustain it.',
    'ProgramOverviewTitle' => 'Program Overview & Delivery',
    'ProgramOverviewIntro' => 'ForwardWorks combined local partnership development, practical skills activity, and targeted progression support to build a stronger route through the fictional program.',
    'ReportingPeriodLabel' => 'Review period',
    'ReportingPeriodValue' => 'January–December 2028',
    'LocationsLabel' => 'Delivery footprint',
    'LocationsValue' => '3 locations',
    'DeliveryTeamLabel' => 'Core delivery team',
    'DeliveryTeamValue' => '16 people',
    'EmployerPartnersLabel' => 'Partner organisations',
    'EmployerPartnersValue' => '52 partners',
    'ProgramObjectivesTitle' => 'Strategic Objectives',
    'ActivitiesDeliveredTitle' => 'Delivery Portfolio',
    'DeliveryObservationTitle' => 'Delivery observation',
    'DeliveryObservationText1' => 'The portfolio is broad enough to support different starting points, but the next phase will require clearer local ownership of specialist activity.',
    'DeliveryObservationText2' => 'Partnership activity is becoming a platform for coordinated planning rather than a series of isolated events.',
    'PerformanceOutcomesTitle' => 'Performance & Outcomes',
    'PerformanceAgainstTargetsTitle' => 'Progress against strategic measures',
    'ParticipantOutcomesTitle' => 'Progression routes',
    'ParticipantOutcomesCaption' => 'ForwardWorks progression routes · 2028',
    'ParticipantOutcomesText' => 'Employment and further training remained the strongest routes forward, while active progression indicates a substantial group still building readiness for their next step.',
    'FindingsAnalysisTitle' => 'Findings & Analysis',
    'FindingIntegratedSupportTitle' => '1. Local capacity is now the critical enabler',
    'FindingIntegratedSupportText1' => 'The first year established demand for a connected support model, but delivery quality increasingly depends on the strength of local coordination around it.',
    'FindingIntegratedSupportText2' => 'The strategic question is therefore not whether the model can attract participation, but how consistently each location can turn shared practice into durable progression.',
    'FindingDigitalConfidenceTitle' => '2. Partnership depth matters more than partner count',
    'FindingDigitalConfidenceText1' => 'The review found that the most useful relationships were those with a clear role in planning, preparation, or progression rather than those measured only by attendance at events.',
    'FindingDigitalConfidenceText2' => 'A smaller group of active partners can provide more strategic value when expectations, referral routes, and feedback loops are explicit.',
    'ProgramInsightTitle' => 'PROGRAM INSIGHT',
    'ProgramInsightText' => 'employers helped local delivery teams build confidence before the model extended into additional communities.',
    'ChallengesTitle' => 'Strategic constraints',
    'ManagementImplicationTitle' => 'Management implication',
    'ManagementImplicationText' => 'Protect the quality of local delivery first: clarify ownership, invest in specialist capacity, and use partnership evidence to guide any future expansion.',
    'RecommendationsOutlookTitle' => 'Recommendations & Outlook',
    'RecommendationsTitle' => 'Priority actions',
    'ReportOutlookTitle' => 'Outlook 2029',
    'ReportOutlookText1' => 'The next review period should consolidate the operating model in existing locations while making the conditions for careful expansion more explicit.',
    'ReportOutlookText2' => 'Northbridge should use the coming year to build a stronger evidence base for local capacity, partnership depth, and sustained progression.',
    'ReportClosing' => 'Strategic review · fictional organization and data',
];
foreach ($bookmarks as $name => $value) {
    $template->bookmark($name)->replaceText($value);
}

// A native Writer Section becomes a second, visible strategic finding. The
// replacement owns only this cloned Section's structured content.
$capacityFinding = $template->section('FindingDigitalConfidence')->clone();
$capacityFinding->replaceContent(
    (new RichText())
        ->addParagraph(
            (new Paragraph('B02Subheading028012'))
                ->addText('3. Local delivery capacity needs reinforcement', ['bold' => true])
        )
        ->addParagraph(strategicParagraph('Demand increased most strongly in selected delivery areas, creating pressure on specialist workshop capacity and local coordination.', 'B02Body030'))
        ->addParagraph(strategicParagraph('Priority for 2029: strengthen local delivery partnerships and create additional specialist workshop capacity before extending the geographic footprint.', 'B02Body034'))
);

// These existing native list Sections are deliberately transferred to
// application-owned structured content while their Section containers remain
// Writer-authored and addressable.
$template->section('ProgramObjectivesContent')->replaceContent(
    strategicList([
        'Build stronger local delivery capacity and ownership.',
        'Connect practical learning to credible progression routes.',
        'Develop deeper partnerships with community and employer networks.',
        'Use evidence to guide responsible future expansion.',
    ], 'L1', 'P2')
);

$template->section('ChallengesContent')->replaceContent(
    strategicList([
        'Specialist workshop capacity is uneven across locations.',
        'Partnership roles are not yet equally clear in every area.',
        'Expansion could dilute quality if local ownership is not strengthened first.',
    ], 'L2', 'P3')
);

$template->section('RecommendationsContent')->replaceContent(
    strategicList([
        'Strengthen local delivery partnerships before expanding the footprint.',
        'Create a specialist capacity plan for the areas under greatest pressure.',
        'Introduce a shared partnership review so roles and referral routes stay clear.',
        'Build a stronger evidence base for sustained progression and future investment.',
    ], 'L3', 'P1')
);

$template->table('ActivitiesDeliveredTable')->populate([
    ['Local planning sessions', '36', '284'],
    ['Specialist workshops', '68', '232'],
    ['Community partner forums', '21', '178'],
    ['Progression clinics', '54', '219'],
    ['Employer pathway meetings', '17', '96'],
]);

$template->table('PerformanceAgainstTargetsTable')->populate([
    ['Participants enrolled', '270', '286', 'Achieved'],
    ['Program completion', '85%', '91%', 'Above target'],
    ['Employment/training outcome', '68%', '74%', 'Above target'],
    ['Partner organisations', '45', '52', 'Above target'],
]);

$template->replaceImageByName(
    'ReportTitleImage',
    __DIR__ . '/assets/s03-strategic-review-cover-2028.png',
    [
        // replaceImageByName() keeps legacy defaults when one dimension is
        // omitted, so both dimensions are explicit for this cover asset.
        'width' => '15cm',
        'height' => '8.452cm',
    ]
);
$template->replaceImageByName(
    'ParticipantOutcomesImage',
    __DIR__ . '/assets/s03-strategic-outcomes-2028.png',
    [
        'width' => '15.799cm',
        'height' => '10.007cm',
    ]
);

$template->setMeta([
    'title' => 'Strategic Review 2028',
    'subject' => 'S03-B Writer-authored structured professional report',
    'description' => 'Fictional strategic review composed through native document-semantic targets.',
    'language' => 'en-US',
]);

$outputPath = __DIR__ . '/output/output_S03_structured_professional_report_b.odt';
$template->save($outputPath);

echo "Saved S03-B structured professional report to {$outputPath}.\n";
