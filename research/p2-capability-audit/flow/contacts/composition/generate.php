<?php

declare(strict_types=1);

require __DIR__ . '/../native/bootstrap.php';
require __DIR__ . '/BuildingBlocks.php';
require __DIR__ . '/ContactComposition.php';

use H07Research\ContactBlock;
use H07Research\ContactStrip;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;

$data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);
foreach (['assets', 'templates', 'repeat'] as $directory) {
    if (!is_dir(__DIR__ . '/' . $directory)) {
        mkdir(__DIR__ . '/' . $directory);
    }
}
function asset(string $name, string $color, string $family = 'lucide'): string
{
    $path = __DIR__ . '/../native/assets/' . $family . '-' . $name . '.svg';
    $dom = new DOMDocument();
    $dom->load($path, LIBXML_NONET);
    $dom->documentElement->setAttribute('stroke', $color);
    $target = __DIR__ . '/assets/' . $family . '-' . $name . '-' . substr($color, 1) . '.svg';
    $dom->save($target);
    return $target;
}
function item(string $label, string $icon, array $lines, array $data, ?float $width = null): array
{
    $tokens = [];
    foreach ($lines as $line) {
        $present = array_values(array_filter($line, static fn ($key) => trim($data[$key] ?? '') !== ''));
        if ($present === []) {
            continue;
        }
        if ($tokens !== []) {
            $tokens[] = "\n";
        }
        foreach ($present as $index => $key) {
            if ($index > 0) {
                $tokens[] = ' ';
            }
            $tokens[] = ['name' => $key, 'value' => $data[$key]];
        }
    }
    return ['label' => $label, 'icon' => $icon, 'tokens' => $tokens, 'width' => $width];
}
function templateFor(string $name, array $items): array
{
    $active = ['name'];
    foreach ($items as $item) {
        foreach ($item['tokens'] as $token) {
            if (is_array($token)) {
                $active[] = $token['name'];
            }
        }
    }
    $active = array_values(array_unique($active));
    $path = __DIR__ . '/templates/' . $name . '.odt';
    copy(__DIR__ . '/seed.odt', $path);
    return [$path, $active];
}
$specs = [];
function document(string $name, string $title, array $items, array $data, array $style, bool $strip = false): void
{
    global $specs;
    $items = array_values(array_filter($items, static fn ($item) => $item['tokens'] !== []));
    [$source, $active] = templateFor($name, $items);
    $t = new OdtTemplate($source);
    foreach ($active as $field) {
        $t->declareUserField($field, '');
        $t->setUserField($field, $data[$field] ?? '');
    }
    $t->appendBody((new Paragraph(null, ['font-size' => '14pt', 'color' => '#375E80', 'bold' => true, 'fo:margin-bottom' => '0.35cm']))->addText($title));
    if ($items !== []) {
        $component = $strip
            ? new ContactStrip($name, $items, $style)
            : new ContactBlock($name, $items, $style);
        $p = new Paragraph(null, ['font-size' => '1pt', 'fo:line-height' => '100%', 'fo:margin-bottom' => '0.1cm']);
        $p->addElement($component);
        $t->appendBody($p);
    }
    $t->appendBody((new Paragraph(null, ['font-size' => '8pt', 'color' => '#5D7890', 'fo:margin-top' => '0.25cm']))->addText('ENDE · native User Fields · keine Tabellen'));
    $t->save(__DIR__ . '/' . $name . '.odt');
    $t->save(__DIR__ . '/repeat/' . $name . '.odt');
    $t->cleanup();
    $specs[$name] = ['items' => $items, 'data' => array_intersect_key($data, array_flip($active)), 'style' => $style, 'strip' => $strip];
}
function vertical(array $data, string $color, bool $labels = false, string $family = 'lucide'): array
{
    return [
        item($labels ? 'ANSCHRIFT' : '', asset('map-pin', $color, $family), [['street', 'house'], ['postal', 'city'], ['country']], $data),
        item($labels ? 'TELEFON' : '', asset('phone', $color, $family), [['phone']], $data),
        item($labels ? 'E-MAIL' : '', asset('mail', $color, $family), [['email']], $data),
        item($labels ? 'WEBSEITE' : '', asset($family === 'tabler' ? 'world' : 'globe', $color, $family), [['website']], $data),
    ];
}
$short = array_replace($data, [
    'street' => 'Am Wissenschaftspark', 'house' => '128 a', 'postal' => '32049',
    'city' => 'Herford', 'country' => 'Deutschland', 'website' => 'example.invalid/ada',
]);
document('compact', 'A · Kompakte Zeichenkomposition', vertical($short, '#375E80'), $short, [
    'width' => 5.8, 'fontSize' => 8.5, 'iconSize' => 0.28, 'iconGap' => 0.12, 'rowGap' => 0.10,
]);
document('sidebar-round', 'B · Sidebar · einheitliche Kreise', vertical($short, '#375E80'), $short, [
    'width' => 5.8, 'background' => '#203A52', 'backing' => 'ellipse',
    'backingColor' => '#DCE8F2', 'color' => '#FFFFFF',
    'fontSize' => 8.5, 'rowGap' => 0.13,
]);
document('sidebar-rounded', 'C · Sidebar · abgerundete Rechtecke', vertical($short, '#375E80', false, 'tabler'), $short, [
    'width' => 5.8, 'background' => '#203A52', 'backing' => 'round-rectangle',
    'backingColor' => '#DCE8F2', 'color' => '#FFFFFF', 'fontSize' => 8.5,
]);
document('editorial', 'D · Beschriftet · Serifenschrift und eigene Farben', vertical($short, '#9A5439', true), $short, [
    'font' => 'Liberation Serif', 'fontSize' => 9.5, 'color' => '#6E3D2C',
    'labelColor' => '#9A5439', 'labelSize' => 6.5, 'rowGap' => 0.12,
]);
$stripItems = [
    item('', asset('phone', '#375E80'), [['phone']], $short, 2.7),
    item('', asset('mail', '#375E80'), [['email']], $short, 4.1),
    item('', asset('globe', '#375E80'), [['website']], $short, 3.6),
];
document('strip-above', 'E · Volle Inhaltsbreite · Symbole oben', $stripItems, $short, [
    'width' => 18.0, 'fontSize' => 8.5, 'placement' => 'above', 'iconGap' => 0.09,
], true);
$inlineItems = $stripItems;
$inlineItems[0]['width'] = 3.3;
$inlineItems[1]['width'] = 5.2;
$inlineItems[2]['width'] = 4.0;
document('strip-inline', 'F · Volle Inhaltsbreite · Symbole links', $inlineItems, $short, [
    'width' => 18.0, 'fontSize' => 8.5, 'placement' => 'left', 'iconGap' => 0.12,
], true);
document('long', 'G · Lange Adresse · natürliche Zeilenhöhen', vertical($data, '#375E80'), $data, [
    'width' => 5.8, 'fontSize' => 8.5, 'rowGap' => 0.12, 'backing' => 'ellipse',
]);
$minimal = ['name' => $short['name'], 'email' => $short['email']];
document('minimal', 'H · Fehlende Einträge entfallen vollständig', vertical($minimal, '#375E80'), $minimal, [
    'width' => 5.8, 'fontSize' => 8.5, 'backing' => 'ellipse',
]);
document('empty', 'I · Keine Kontaktangaben · kein Kontaktobjekt', [], ['name' => $short['name']], []);
file_put_contents(__DIR__ . '/specs.json', json_encode($specs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
file_put_contents(__DIR__ . '/outputs.json', json_encode(array_keys($specs), JSON_PRETTY_PRINT) . "\n");
echo implode("\n", array_keys($specs)) . "\n";
