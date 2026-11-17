<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\SectionElement;
use OdtTemplateEngine\OdtTemplate;

// Writer supplies only the document shell. PHP owns the new native Sections.
$template = new OdtTemplate(__DIR__ . '/templates/template_C06_section_authoring.odt');
$template->styles()->defineSection('C06Equal', [
    'columns' => ['count' => 2, 'relative-widths' => [1, 1], 'balance' => true],
]);
$template->styles()->defineSection('C06Unequal', [
    'margin-left' => '0.5cm',
    'margin-right' => '0.5cm',
    'background-color' => '#f2f2f2',
    'columns' => [
        'count' => 2,
        'gap' => '0.4cm',
        'relative-widths' => [2, 3],
        'balance' => false,
        'separator' => ['style' => 'solid', 'width' => '0.03cm', 'color' => '#78909c', 'height' => '100%'],
    ],
]);
$template->styles()->defineSection('C06Three', [
    'columns' => ['count' => 3, 'relative-widths' => [1, 2, 3], 'separator' => [
        'style' => 'solid', 'width' => '0.03cm', 'color' => '#78909c', 'height' => '100%',
    ]],
]);
$template->styles()->defineSection('C06Indented', [
    'background-color' => '#fff2cc',
    'columns' => ['count' => 2, 'relative-widths' => [1, 1], 'column-indents' => [
        ['start' => '0cm', 'end' => '0.2cm'], ['start' => '0.3cm', 'end' => '0cm'],
    ]],
]);
$template->appendBody((new Paragraph())->addText('Section Authoring', ['bold' => true]));
$template->appendBody((new Paragraph())->addText('All named Sections below are generated in PHP. Inspect them in the Writer Navigator.'));

$template->appendSection((new SectionElement('SimpleSection'))
    ->addElement((new Paragraph())->addText('SimpleSection: an ordinary paragraph.')));

$template->appendSection((new SectionElement('FilledEqual'))->setSectionStyle('C06Equal')
    ->addElement((new Paragraph())->addText('Filled equal-width columns.'))
    ->addElement((new Paragraph())->addText('Second paragraph in the equal section. This paragraph provides enough flowing content to make the two native columns visible in Writer.'))
    ->addElement((new Paragraph())->addText('Equal columns remain balanced by their Section style.')));
$template->appendSection((new SectionElement('FilledUnequal'))->setSectionStyle('C06Unequal')
    ->addElement((new Paragraph())->addText('Filled unequal-width columns with an exact split gap. The wider second column demonstrates the authored 2:3 relationship.'))
    ->addElement((new Paragraph())->addText('Balance is disabled here so the native column flow is easy to inspect.')));
$template->appendSection((new SectionElement('ThreeColumnSeparator'))->setSectionStyle('C06Three')
    ->addElement((new Paragraph())->addText('Three columns with a native separator. This section contains enough text to expose all three native columns in a Writer roundtrip.'))
    ->addElement((new Paragraph())->addText('The separator remains a Section column property.')));
$template->appendSection((new SectionElement('ColoredIndented'))->setSectionStyle('C06Indented')
    ->addElement((new Paragraph())->addText('Colored section with explicit per-column indents.')));

$template->appendSection((new SectionElement('NestedOuter'))
    ->addElement((new Paragraph())->addText('NestedOuter: before the inner Section.'))
    ->addSection((new SectionElement('NestedInner'))
        ->addElement((new Paragraph())->addText('NestedInner: editable nested content.')))
    ->addElement((new Paragraph())->addText('NestedOuter: after the inner Section.')));

$template->appendSection((new SectionElement('NestedStyledOuter'))->setSectionStyle('C06Equal')
    ->addElement((new Paragraph())->addText('Outer styled section.'))
    ->addSection((new SectionElement('NestedStyledInner'))->setSectionStyle('C06Indented')
        ->addElement((new Paragraph())->addText('Nested styled section.'))));

$list = (new ListElement())
    ->addItem((new Paragraph())->addText('First list item'))
    ->addItem((new Paragraph())->addText('Second list item'));
$table = (new RichTable())->addRow(['Capability', 'Native structure'])->addRow(['Table', 'Editable cells']);
$image = (new ImageElement(dirname(__DIR__) . '/assets/Logo.png', ['width' => '2cm']))
    ->setFrameLayout(['anchor' => 'as-char']);
$box = (new DrawTextBox('C06Callout'))->setFrameLayout([
    'anchor' => 'as-char', 'width' => '6cm', 'height' => '1.2cm',
])->addElement((new Paragraph())->addText('Native editable text box'));
$template->appendSection((new SectionElement('MixedContent'))->setSectionStyle('C06Unequal')
    ->addElement((new Paragraph())->addText('MixedContent: ')->addHyperlink('Example link', 'https://example.org'))
    ->addElement((new Paragraph())->setParagraphStyleOptions(['break-before' => 'column'])
        ->addText('Native paragraph column break starts the next column.'))
    ->addElement($list)->addElement($table)->addElement($image)->addElement($box));

$template->appendBody((new Paragraph())->addText('EmptySection follows: inspect its name in the Navigator.'));
$template->appendSection(new SectionElement('EmptySection'));

$target = $template->appendSection((new SectionElement('EditableSection'))
    ->addElement((new Paragraph())->addText('Initial content')));
$target->replaceContent((new Paragraph())->addText('EditableSection: content replaced through SectionTarget.'));

$output = getenv('SECTION_AUTHORING_OUTPUT') ?: __DIR__ . '/output/output_C06_section_authoring.odt';
$template->save($output);
