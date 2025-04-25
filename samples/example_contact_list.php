<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;

// 1️⃣ Template laden und vorbereiten
$template = new OdtTemplate('templates/template_tabs.odt');
$template->load();

// 2️⃣ Einen neuen Paragraph‑Block erstellen mit etwas Abstand oben
$para = new Paragraph('Standard', [
    'margin-top'    => '1cm',
    'margin-bottom' => '0.5cm',
    'text-align'    => 'left',
]);

// 3️⃣ Einfache Überschrift
$para
    ->addText('📋 Contacts List', ['bold' => true, 'font-size' => '14pt'])
    ->addLineBreak()
    ->addLineBreak();

// 4️⃣ Tab‑Stops definieren (Position in cm und Ausrichtung)
$tabStops = [
    ['position' => 3.0,  'alignment' => 'left'],
    ['position' => 8.0,  'alignment' => 'center'],
    ['position' => 12.0, 'alignment' => 'right'],
];

// 5️⃣ Kopfzeile mit Fettschrift über alle Stops
$headers = ['Name', 'Role', 'Email'];
$para->addTabsWithTexts(
    $tabStops,
    $headers,
    ['bold' => true, 'underline' => true]
)
->addLineBreak();

// 6️⃣ Mehrere Datenzeilen
$rows = [
    ['Alice Smith', 'Developer',       'alice@example.com'],
    ['Bob Müller',   'Designer',        'bob@example.com'],
    ['Carol König',  'Project Manager', 'carol@example.com'],
];
foreach ($rows as $row) {
    $para
        ->addTabsWithTexts($tabStops, $row)
        ->addLineBreak();
}

// 7️⃣ Hyperlink‑Zeile (auch als Tab‑Stop)
$para->addTabsWithTexts(
        $tabStops,
        ['Visit our site', '', ''],
        ['italic' => true],
        null, // kein neuer Paragraph‑Style
        []
    )
    // an dritter Stop einen Link einfügen
    ->addTab() // stellt sicher, dass wir am Anfang stehen
    ->addTabStop(3.0, 'left', '→ ')
    ->addHyperlink('Company Website', 'https://example.com', ['color' => '#0000ff', 'underline' => true])
    ->addLineBreak();

// 8️⃣ Den Paragraph‑Block im Template einsetzen
$template->setElement('contacts_block', $para);

// 9️⃣ Datei speichern
$template->save('output/contacts_list.odt');

echo "✅ contacts_list.odt wurde erzeugt.\n";
