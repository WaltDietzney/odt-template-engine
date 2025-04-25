<?php
require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;

// 1. Load an existing ODT template with a placeholder {{CONTENT}}
$template = new OdtTemplate('templates/template_richtextblock.odt');
$template->load();

// 2. Build a RichText block to replace {{CONTENT}}
$rich = new RichText();

// ────────────────────────────────────────────────────────────────────────────
// SECTION 1: Mixed Inline Styles, Line Breaks & Tabs
// ────────────────────────────────────────────────────────────────────────────

// 2.1 A paragraph mixing italic, bold, colored text, a line break and a tab
$para1 = new Paragraph();
$para1
    ->addText("Lorem ipsum dolor sit amet, ", ['italic' => true])
    ->addText("consectetur adipisicing elit", ['bold' => true, 'color' => '#c0392b'])
    ->addLineBreak() // inserts <text:line-break/>
    ->addText("Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.", ['color' => '#2980b9'])
    ->addTab()       // inserts <text:tab/>
    ->addText("— after a tab —", ['lower' => true]);
$rich->addParagraph($para1);

// 2.2 A paragraph with an embedded hyperlink
$para2 = new Paragraph();
$para2
    ->addText("For more information, visit ")
    ->addHyperlink("OpenAI", "https://www.openai.com", ['underline' => true, 'color' => '#2980b9'])
    ->addText(".");
$rich->addParagraph($para2);

// ────────────────────────────────────────────────────────────────────────────
// SECTION 2: Inline Tabs (equal-width) and Tab-Stops (styled columns)
// ────────────────────────────────────────────────────────────────────────────

// 2.3 Simple inline tab stops
$para3 = new Paragraph();
$para3
    ->addText("Column A")
    ->addTab()
    ->addText("Column B", ['color' => '#27ae60'])
    ->addTab()
    ->addText("Column C", ['bold' => true]);
$rich->addParagraph($para3);

// 2.4 Tab-stops via paragraph style options
//     - define three stops at 4cm (left), 8cm (center), 12cm (right)
//     - then add text + inline tabs
$para4 = (new Paragraph())
    ->setParagraphStyle("CustomTabs")               // assign a named paragraph style
    ->setParagraphStyleOptions([
        'tab-stops' => [
            ['position' => 4, 'alignment' => 'left'],    // 4cm left-aligned
            ['position' => 8, 'alignment' => 'center'],  // 8cm centered
            ['position' => 12, 'alignment' => 'right'],  // 12cm right-aligned
        ],
        // you can also add margins, indent, line-height etc. here
        'margin-top'    => '0.3cm',
        'margin-bottom' => '0.3cm',
    ])
    ->addTab()
    ->addText("Left stop")
    ->addTab()
    ->addText("Center stop")
    ->addTab()
    ->addText("Right stop");
$rich->addParagraph($para4);

// ────────────────────────────────────────────────────────────────────────────
// SECTION 3: Multiple Paragraphs in One Call
// ────────────────────────────────────────────────────────────────────────────

$loremLines = [
    "Chapter 1: Introduction to ODT templating",
    "ODT templating allows you to replace placeholders with complex structures.",
    "RichText, Tables, Images, Conditional Logic and more."
];
// first line bold, all lines text-size 10pt, gray color
$rich->addParagraphBreak()
     ->addMultiParagraph($loremLines, ['font-size' => '10pt', 'color' => '#7f8c8d'], true);

// ────────────────────────────────────────────────────────────────────────────
// SECTION 4: Bullet & Numbered Lists
// ────────────────────────────────────────────────────────────────────────────
$rich->addParagraphBreak()
     ->addBulletList([
         "Supports bullet lists out of the box",
         "Easy syntax, automatic styling",
         "Customizable list styles"
     ], ['color' => '#2c3e50'])
     ->addParagraphBreak()
     ->addNumberedList([
         "First numbered item",
         "Second numbered item",
         "Third numbered item"
     ], ['italic' => true]);

// ────────────────────────────────────────────────────────────────────────────
// SECTION 5: Register any custom paragraph styles BEFORE saving
// ────────────────────────────────────────────────────────────────────────────
$template->ensureParagraphStylesExist([
    'CustomTabs' => [
        'margin-top'    => '0.3cm',
        'margin-bottom' => '0.3cm',
        'tab-stops'     => [
            ['position' => 4, 'alignment' => 'left'],
            ['position' => 8, 'alignment' => 'center'],
            ['position' => 12, 'alignment' => 'right'],
        ],
    ],
    // you could register additional named styles here
]);

// 3. Replace the {{CONTENT}} placeholder with our RichText block
$template->setElement('TEXTBLOCK', $rich);

// 4. Save the filled template to a new file
$template->save('output/example_full.odt');

echo "✅ example_full.odt generated successfully\n";
