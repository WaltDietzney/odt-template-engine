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
$template->appendBody((new Paragraph())->addText('Section Authoring', ['bold' => true]));
$template->appendBody((new Paragraph())->addText('All named Sections below are generated in PHP. Inspect them in the Writer Navigator.'));

$template->appendSection((new SectionElement('SimpleSection'))
    ->addElement((new Paragraph())->addText('SimpleSection: an ordinary paragraph.')));

$template->appendSection((new SectionElement('NestedOuter'))
    ->addElement((new Paragraph())->addText('NestedOuter: before the inner Section.'))
    ->addSection((new SectionElement('NestedInner'))
        ->addElement((new Paragraph())->addText('NestedInner: editable nested content.')))
    ->addElement((new Paragraph())->addText('NestedOuter: after the inner Section.')));

$list = (new ListElement())
    ->addItem((new Paragraph())->addText('First list item'))
    ->addItem((new Paragraph())->addText('Second list item'));
$table = (new RichTable())->addRow(['Capability', 'Native structure'])->addRow(['Table', 'Editable cells']);
$image = (new ImageElement(dirname(__DIR__) . '/assets/Logo.png', ['width' => '2cm']))
    ->setFrameLayout(['anchor' => 'as-char']);
$box = (new DrawTextBox('C06Callout'))->setFrameLayout([
    'anchor' => 'as-char', 'width' => '6cm', 'height' => '1.2cm',
])->addElement((new Paragraph())->addText('Native editable text box'));
$template->appendSection((new SectionElement('MixedContent'))
    ->addElement((new Paragraph())->addText('MixedContent: ')->addHyperlink('Example link', 'https://example.org'))
    ->addElement($list)->addElement($table)->addElement($image)->addElement($box));

$template->appendBody((new Paragraph())->addText('EmptySection follows: inspect its name in the Navigator.'));
$template->appendSection(new SectionElement('EmptySection'));

$target = $template->appendSection((new SectionElement('EditableSection'))
    ->addElement((new Paragraph())->addText('Initial content')));
$target->replaceContent((new Paragraph())->addText('EditableSection: content replaced through SectionTarget.'));

$output = getenv('SECTION_AUTHORING_OUTPUT') ?: __DIR__ . '/output/output_C06_section_authoring.odt';
$template->save($output);
