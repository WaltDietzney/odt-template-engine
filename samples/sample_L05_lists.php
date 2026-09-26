<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L05_lists.odt');

$plan = new RichText();
$numberedSteps = new ListElement('numbered');
$numberedSteps
    ->addItem((new Paragraph())->addText('Prepare the project'))
    ->addItem((new Paragraph())->addText('Build and review'))
    ->addItem((new Paragraph())->addText('Deliver the document'));
$plan->addElement($numberedSteps);

$plan->addParagraphBreak();
$plan->addParagraph('Workstreams and nested tasks', null, ['bold' => true, 'color' => '#27616d']);
$workstreams = new ListElement('bullet');
$workstreams->addItem((new Paragraph())->addText('Preparation'));
$preparation = new ListElement('bullet');
$preparation
    ->addItem((new Paragraph())->addText('Confirm requirements'))
    ->addItem((new Paragraph())->addText('Gather the resources'));
$workstreams->addSubList($preparation);

$workstreams->addItem((new Paragraph())->addText('Implementation'));
$implementation = new ListElement('bullet');
$implementation->addItem((new Paragraph())->addText('Develop the document workflow'));
$nestedTasks = new ListElement('bullet');
$nestedTasks
    ->addItem((new Paragraph())->addText('Create the template'))
    ->addItem((new Paragraph())->addText('Generate an editable ODT'));
$implementation->addSubList($nestedTasks);
$implementation->addItem((new Paragraph())->addText('Test the result'));
$workstreams->addSubList($implementation);

$workstreams->addItem((new Paragraph())->addText('Delivery'));
$plan->addElement($workstreams);

$plan->addParagraphBreak();
$plan->addParagraph('Project principles', null, ['bold' => true, 'color' => '#27616d']);
$principles = new ListElement('bullet');
$principles
    ->addItem((new Paragraph())->addText('Keep the source data separate'))
    ->addItem((new Paragraph())->addText('Keep the generated document editable'));
$plan->addElement($principles);

$template->setElement('project_plan', $plan);
$template->save(__DIR__ . '/output/output_L05_lists.odt');
