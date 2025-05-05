<?php
/**
 * Sample 13: Advanced Table Construction with Custom Styles and Rich Content
 *
 * This sample demonstrates how to create multiple tables with
 * individual styles, headers, row spans, column spans, and how to combine
 * everything into a rich, well-structured document using the OdtTemplateEngine.
 */

//require_once '../vendor/autoload.php';

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;

// ➡️ Load the ODT template
$template = new OdtTemplate('samples/templates/template_13_settingCells.odt');
$template->load();

// Create a new RichText container to collect all elements
$rich = new RichText();

/**
 * SECTION 1: Building a manually styled table with custom cell properties
 */

// ➡️ Create a new table instance
$table1 = new RichTable();

// Define styles for header and normal rows
$headerStyle = ['background' => '#4287f5', 'border' => '3px solid #4287f5', 'bold' => true, 'color' => '#ffffff'];
$rowStyle = ['background' => '#eeeeee'];

// ➡️ Add a header row
$table1->addRow([
    (new RichTableCell('Header 1', $headerStyle))->alignCenter(),
    (new RichTableCell('Header 2', $headerStyle))->alignCenter(),
    (new RichTableCell('Header 3', $headerStyle))->alignCenter(),
]);

// ➡️ Add standard rows with alternating styles
$table1->addRow([
    (new RichTableCell('Row 1'))->setStyle($rowStyle),
    new RichTableCell('Row 2', $rowStyle),
    new RichTableCell('Row 3', $rowStyle),
]);

$table1->addRow([
    (new RichTableCell('Row A'))->colspan(2)->alignCenter()->setStyle(['background' => '#4287f5', 'color' => '#c9211e']),
    new RichTableCell('Row B'),
]);

// ➡️ Demonstrate rowspan (merging cells vertically)
$table1->addRow([
    (new RichTableCell('Cell X'))->rowspan(2)->alignCenter()->setStyle($rowStyle),
    new RichTableCell('Cell Y'),
    new RichTableCell('Cell Z'),
]);

$table1->addRow([
    new RichTableCell('Cell Y2'),
    new RichTableCell('Cell Z2'),
]);

/**
 * SECTION 2: Quick table creation from an array
 *
 * This method is useful when data comes dynamically from a database or API.
 */

// ➡️ Define a simple data array
$tableData1 = [
    ['Description', 'Revenue', 'Expenses', 'Profit'], // Header row
    ['Product A', '100,000 €', '50,000 €', '50,000 €'],
    ['Product B', '110,000 €', '60,000 €', '50,000 €'],
    ['Product C', '120,000 €', '65,000 €', '55,000 €']
];

// ➡️ Create the table quickly using the array
$financeTable = (new RichTable())->buildTableFromArray($tableData1, 'finance');

/**
 * SECTION 3: Advanced styled table with custom summary recognition
 */

// ➡️ Define a second dataset with a "summary" row
$tableData2 = [
    ['Position', 'Description', 'Amount'],
    ['1', 'Income', '1000 €'],
    ['2', 'Expenses', '600 €'],
    ['3', 'Rent', '300 €'],
    ['4', 'Other', '100 €'],
    ['Total', '', '0 €'], // <-- Automatically detected as a summary row
];

// ➡️ Create a new RichTable
$table2 = new RichTable();

// Define custom keywords for summary recognition
$table2->setSummaryKeywords(['Total', 'Sum', 'Grand Total']);

// ➡️ Define detailed styles for different row types
$table2->addCustomStyle('customStyle', [
    'header' => [
        'background' => '#004d40',
        'color' => '#ffffff',
        'font-weight' => 'bold',
        'text-align' => 'center',
        'padding' => '0.2cm',
    ],
    'row' => [
        'background' => '#e0f2f1',
        'text-align' => 'left',
        'padding' => '0.2cm',
    ],
    'row-alt' => [
        'background' => '#ffffff',
        'text-align' => 'left',
        'padding' => '0.2cm',
    ],
    'summary' => [
        'background' => '#00796b',
        'color' => '#ffffff',
        'font-weight' => 'bold',
        'text-align' => 'right',
        'padding' => '0.2cm',
    ],
]);

// ➡️ Build the table using the defined array and custom style
$table2->buildTableFromArray($tableData2, 'customStyle');

/**
 * SECTION 4: Assemble the document
 *
 * Add explanatory paragraphs and the tables in a structured way.
 */

$rich
    ->addParagraph('Report: Overview of Structured Tables', 'Heading 1')
    ->addParagraph('This document demonstrates different techniques for building and styling tables in an ODT document using the OdtTemplateEngine. We showcase manually created tables, array-based tables, and tables with custom summary row detection.', 'Text Body')
    ->addParagraphBreak(2)

    ->addParagraph('Table 1: Manually Created Table with Styling', 'Heading 2')
    ->addParagraph('The following table shows manual cell creation with styles like background color, borders, row spanning, and column spanning.', 'Text Body')
    ->addParagraphBreak()
    ->addTable($table1)
    ->addParagraphBreak(2)

    ->addParagraph('Table 2: Financial Overview (Auto-Built from Array)', 'Heading 2')
    ->addParagraph('This table was created automatically from a simple PHP array, demonstrating a fast way to populate data-driven tables.', 'Text Body')
    ->addParagraphBreak()
    ->addTable($financeTable)
    ->addParagraphBreak(2)

    ->addParagraph('Table 3: Financial Breakdown with Custom Styling', 'Heading 2')
    ->addParagraph('This table applies customized styles for headers, alternating rows, and automatically detects summary rows for special styling.', 'Text Body')
    ->addParagraphBreak()
    ->addTable($table2)
    ->addParagraphBreak(2)

    ->addParagraph('Conclusion', 'Heading 2')
    ->addParagraph('By combining RichText and RichTable elements, you can create highly customizable and professional-looking ODT documents with very little code.', 'Text Body');

// ➡️ Insert the assembled RichText into the template
$template->setElement('table1', $rich);

// ➡️ Save the output document
$template->save('samples/output/output_13_settingCells.odt');

echo "✔️ Successfully created test ODT: samples/output/output_13_settingCells.odt\n";
