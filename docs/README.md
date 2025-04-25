
# 🧩 ODT Template Engine for PHP

 __        __   _       _     _         _       
 \ \      / /__| |_   _| |__ (_)_ __ __| | ___  
  \ \ /\ / / _ \ | | | | '_ \| | '__/ _` |/ _ \ 
   \ V  V /  __/ | |_| | | | | | | | (_| | (_) |
    \_/\_/ \___|_|\__, |_| |_|_|_|  \__,_|\___/ 
                  |___/   WaltDietzney Engine  
                  🧩 Create magical ODT files ✨


A powerful, extensible and developer-friendly templating engine for `.odt` files (OpenDocument Text). Supports variables, filters, loops, conditional logic, images, and even complex structures like formatted paragraphs, tables and tab stops.

Inspired by Smarty, optimized for modern PHP workflows.


[![License](https://img.shields.io/github/license/WaltDietzney/odt-template-engine?color=blue)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.0-blue.svg)](https://www.php.net/)
[![ODF Friendly](https://img.shields.io/badge/OpenDocument-ODT-success.svg)](https://en.wikipedia.org/wiki/OpenDocument)
[![Made with ❤️](https://img.shields.io/badge/Made%20with-%E2%9D%A4-red.svg)](#)

---

## ✨ Features

- 🔄 Replace variables like `{{name}}`, even with filters
- 🔁 Loops with `{{#foreach:items}} ... {{#endforeach}}`
- 🧠 Conditional logic: `if`, `elseif`, `else`, `ifnot`
- 🔤 Filters: `{{upper:name}}`, `{{date:birth|d.m.Y}}`, etc.
- 🖼 Dynamic image insertion and styling by name
- 🧱 `setElement()` and `addElement()` for rich content like:
  - ✅ Formatted paragraphs
  - ✅ Numbered/bullet lists
  - ✅ Tables
  - ✅ Images with precise positioning
- 🌐 HTML-to-ODT import support
- 🧼 Automatic span normalization (LibreOffice fixes)
- 📄 Full header, footer and styles support

---

## 📦 Installation

```bash
composer require waltdietzney/odt-template-engine
```

---

## 🌍 Why Open Document Format (ODF)?

ODF (`.odt`) is more than just a file format — it’s a future-proof, open standard for editable documents:

- **👐 Open & Transparent**  
  Based on open standards (ISO/IEC 26300), ensuring long-term accessibility without vendor lock-in.

- **📁 Fully Editable Output**  
  Unlike PDF, ODT documents remain fully editable — perfect for collaboration, review, or customization by the end user.

- **💻 Platform Independent**  
  Works on Windows, Linux, macOS — with LibreOffice, OpenOffice, or even Microsoft Word (ODF support included).

- **🏛️ Ideal for Government & Education**  
  Many public institutions rely on open formats for compliance, interoperability, and archival.

- **🔧 Developer-Friendly**  
  Under the hood, ODT files are ZIP containers with clean XML inside — perfect for dynamic content insertion.

---

## 🧰 Use Cases

You can use this engine to generate complex `.odt` documents dynamically — fully styled, human-readable, and editable.

- ✅ Invoices or quotes based on template variables  
- 📄 Contracts or forms with pre-filled user data  
- 📝 Certificates for workshops, training, or events  
- 📊 Data-driven reports (merge database content with layout)  
- 🏢 Automated letters or business offers  
- 🔄 Batch document export from CMS, CRM, or ERP systems  
- 🌐 Integrate document generation directly into your web app

---

---

## 📂 Project Structure

```text
src/
├── OdtTemplate.php               → Base template engine
├── AbstractOdtTemplate.php       → Core logic
├── Elements/
│   ├── OdtElement.php            → Abstract base for elements
│   ├── RichText.php              → Rich text with formatting
│   ├── Paragraph.php             → Paragraphs with tabs, alignment
│   ├── RichTable.php             → Complex table creation
|   |── RichTableCell.php         → Styling table cells
│   ├── ImageElement.php          → Positioned images
|── Importer/
|   |── HtmlImport.php            → Imports Html elements
|── Util
|   |── StyleMapper.php           → Maps styles do odt-styles
|   |── StyleWriter.php           → Writes stlyes do styles.xml
```

---

## 🚀 Quick Example

```php
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('templates/invoice.odt');
$template->load();

$template->setValues([
    'customer_name' => 'Jane Smith',
    'total' => '129.90',
    'is_vip' => true
]);

$template->setRepeating('items', [
    ['name' => 'Tea', 'price' => '3.50'],
    ['name' => 'Coffee', 'price' => '4.20'],
]);

$template->replaceImageByName('logo', 'assets/logo.png', ['width' => '5cm']);

$template->save('output/invoice_result.odt');
```

## 🖼 Image Handling

---
```php
//inserting via richText as embedded element
$image = new ImageElement('path/to/photo.jpg');
$style = [
    'width' => '5cm',
    'height' => '4cm',
    'align' => 'right',
    'anchor' => 'paragraph',
];
$image->setStyle($style);

$template->setElement('logo',$image);

//or use direct as image
$template -> setImage('logo','path/to/photo.jpg', $style);

//or if you want to replace an existing image by its name
$template->replaceImageByName('logo', 'assets/logo.png', ['width' => '5cm']);
```
---

## 🖊 Advanced Paragraph & RichText

```php
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;

$template = new OdtTemplate('templates/textblock.odt');
$template->load();

$rich = (new RichText())
    ->addText('Hello ', ['bold' => true])
    ->addText('world!', ['italic' => true])
    ->addLineBreak()
    ->addBulletList(['One', 'Two'])
    ->addLineBreak()


$template->setElement('intro', $rich);
$template->save('output/output_textblock.odt');
```

---

## 📐 Paragraphs with Tabs

Generate complex structures by combining Paragraphs and RichText Elements

```php
require '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;

// 1️⃣ Load your template
$template = new OdtTemplate('templates/template_with_tabstops.odt');
$template->load();


$title = new Paragraph('Title');
$title->addText('Different Ways to set Tabs');

// 2️⃣ Build a complex RichText block

$par1 = new Paragraph();

// Define shared tab stops: 5cm and 11cm from left margin
$tabStops = [
    ['position' => 5.0, 'alignment' => 'left', 'text' => 'Itam A', 'style' => ['bold' => true]],
    ['position' => 11.0, 'alignment' => 'right', 'text' => '€12.50', 'style' => ['color' => '#cfcfcf'], 'italic' => true]
];

// Single line with two values
$par1->addTabsWithTexts(
    $tabStops
);

// Tabular block: header + data rows
$rows = [
    ['Product', 'Price'],
    ['Widget', '€9.99'],
    ['Gadget', '€14.20'],
];

$par2 = new Paragraph();

$par2->addTabularLines(
    $rows,
    $tabStops,
    [
        'color' => '#0066cc',         // text color
        'InvoiceTable'
    ]
);
$par3 = new Paragraph();
// Key-Value summary
$par3->addKeyValueLine('Subtotal', '€24.19', 11.0, ['italic' => true, 'bold' => true]);

//create a complex structure via RichText
$rich = new RichText();
$rich->addParagraph($title)
    ->addParagraphBreak(4)
    ->addParagraph($par1)
    ->addParagraphBreak(2)
    ->addParagraph($par2)
    ->addParagraphBreak(2)
    ->addParagraph($par3);

// 3️⃣ Inject into your template
$template->setElement('tabular_block', $rich);

// 4️⃣ Save result
$template->save('output/final_invoice.odt');
```

---

## 🌍 HTML Import

```php
use OdtTemplateEngine\Import\HtmlImporter;

$html = '<h1>Imported Title</h1><p>This comes from HTML.</p>';
$element = HtmlImporter::fromHtml($html);
$template->setElement('html_block', $element);
```
---
## 📋 Complex tables with formatted cells

```php
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell as rtc;

$template = new OdtTemplate('templates/template_table_styled.odt');
$template->load();

$table = new RichTable();

// Kopfzeile
$table->addRow([
    new rtc('Task', ['background' => '#ddeeff', 'text-align' => 'center', 'border' => '0.05pt solid #000']),
    new rtc('Status', ['background' => '#ddeeff', 'text-align' => 'center', 'border' => '0.05pt solid #000']),
]);

// Zeile mit RichText
$rich = (new RichText())->addText('Html', ['bold' => true]);
$table->addRow([
    new rtc($rich, ['background' => '#c8facc', 'align' => 'end']),
    new rtc('✔', ['background' => '#c8facc', 'text-align' => 'center']),
]);

// Weitere Zeilen
$table->addRow([
    new rtc('Table Styling'),
    new rtc('✔', ['background' => '#c8facc', 'text-align' => 'center']),
]);

$table->addRow([
    new rtc('Pending'),
    new rtc('☐', ['background' => '#fce3e3', 'align' => 'center']),
]);

$template->setElement('tableblock', $table);
$template->save('output/output_table_styled.odt');
```


## 🔠 Filters

| Filter     | Syntax                  | Output               |
|------------|-------------------------|----------------------|
| `upper`    | `{{upper:name}}`        | `"ANNA"`             |
| `lower`    | `{{lower:email}}`       | `"anna@example.com"` |
| `nl2br`    | `{{nl2br:note}}`        | `<text:line-break/>` |
| `date`     | `{{date:birth\|d.m.Y}}`  | `"01.01.1990"`       |
| `number`   | `{{number:price\|2}}`    | `"4.20"`             |
| `currency` | `{{currency:price}}`    |  `"4.20 €"`          |

---

## 🤖 Conditional Logic

```text
{{#if:is_vip}}
  VIP Customer
{{#elseif:total>100}}
  Premium Customer
{{#else}}
  Regular Customer
{{#endif}}
```

Also supports negation: `{{#ifnot:is_blocked}}`

---

## 🧼 XML Span Fix

LibreOffice often splits placeholders across `<text:span>` tags. This engine auto-normalizes that, so you don't need to worry.

---

## 🛠 Roadmap

- [ ] Nested logic/loops
- [ ] Style presets and style exporting
- [ ] Resume generator with GUI
- [ ] Web-based template manager
- [ ] Export all template variables

---

## 🧑‍💻 Author

Created by **Walter Dietz**  
✉️ GitHub: [@WaltDietzney](https://github.com/WaltDietzney)

Feel free to contribute, fork, or drop me a message.

---

## 📜 License

MIT – free to use, modify, and share.

---

## 💡 Like it?

Give it a ⭐ on GitHub if you like this project – and stay tuned for new modules, visual tools, and more!
