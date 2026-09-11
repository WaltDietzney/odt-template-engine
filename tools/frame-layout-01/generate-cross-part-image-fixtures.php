<?php

declare(strict_types=1);

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use ZipArchive;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
const TEXT_NS = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
const DRAW_NS = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
const XLINK_NS = 'http://www.w3.org/1999/xlink';

$root = dirname(__DIR__, 2);
$outputDir = $root . '/tmp/frame-layout-01-cross-part';
$imagePath = $root . '/assets/Logo.png';
$base = $argv[1] ?? ($root . '/research/frame-layout-01-cross-part-base.odt');

if (!is_file($base)) {
    throw new RuntimeException(
        'Writer-authored base fixture not found. Pass it as the first argument or create: ' . $base
    );
}

if (!is_file($imagePath)) {
    throw new RuntimeException('Expected image asset not found: ' . $imagePath);
}

if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
    throw new RuntimeException('Unable to create output directory: ' . $outputDir);
}

assertBaseFixture($base);

$h1 = $outputDir . '/H1-setImage-header-as-char.odt';
$template = new OdtTemplate($base);
$template->setImage('header_logo', $imagePath, [
    'width' => '1cm',
    'anchor' => 'as-char',
]);
$template->save($h1);

$h2 = $outputDir . '/H2-imageElement-header-as-char.odt';
$template = new OdtTemplate($base);
$template->setElement('header_logo', new ImageElement($imagePath, [
    'width' => '1cm',
    'anchor' => 'as-char',
]));
$template->save($h2);

$h3 = $outputDir . '/H3-imageElement-header-without-style-name.odt';
copy($h2, $h3);
mutateEntry($h3, 'styles.xml', static function (DOMDocument $dom): void {
    $frame = headerFrame($dom);
    if (!$frame instanceof DOMElement) {
        throw new RuntimeException('H3: header frame not found.');
    }
    $frame->removeAttributeNS(DRAW_NS, 'style-name');
    $frame->removeAttribute('draw:style-name');
});

$h4 = $outputDir . '/H4-imageElement-header-writer-graphic-style.odt';
copy($h2, $h4);
mutateEntry($h4, 'styles.xml', static function (DOMDocument $dom): void {
    $frame = headerFrame($dom);
    if (!$frame instanceof DOMElement) {
        throw new RuntimeException('H4: header frame not found.');
    }
    $frame->setAttributeNS(DRAW_NS, 'draw:style-name', 'Graphics');
});

$h5 = $outputDir . '/H5-setImage-structure-with-imageElement-style.odt';
copy($h1, $h5);
$h2Style = imageElementStyleDefinition($h2);
if ($h2Style === null) {
    throw new RuntimeException('H5: could not locate ImageElement graphic style definition in H2.');
}
[$h2StyleName, $h2StylesXml, $h2Container] = $h2Style;
mutateEntry($h5, 'styles.xml', static function (DOMDocument $dom) use (
    $h2StyleName,
    $h2StylesXml,
    $h2Container
): void {
    $frame = headerFrame($dom);
    if (!$frame instanceof DOMElement) {
        throw new RuntimeException('H5: header frame not found.');
    }
    $frame->setAttributeNS(DRAW_NS, 'draw:style-name', $h2StyleName);

    $sourceDom = new DOMDocument();
    if (!$sourceDom->loadXML($h2StylesXml)) {
        throw new RuntimeException('H5: unable to parse H2 styles.xml.');
    }
    $sourceXPath = xpath($sourceDom);
    $sourceStyle = $sourceXPath->query(
        '//style:style[@style:name="' . $h2StyleName . '" and @style:family="graphic"]'
    )->item(0);
    if (!$sourceStyle instanceof DOMElement) {
        throw new RuntimeException('H5: copied ImageElement style not found in H2.');
    }

    $xpath = xpath($dom);
    $container = $xpath->query('//office:' . $h2Container)->item(0);
    if (!$container instanceof DOMElement) {
        throw new RuntimeException('H5: target style container not found: ' . $h2Container);
    }

    $container->appendChild($dom->importNode($sourceStyle, true));
});

$h6 = $outputDir . '/H6-imageElement-body-as-char.odt';
$template = new OdtTemplate($base);
$template->setElement('body_logo', new ImageElement($imagePath, [
    'width' => '1cm',
    'anchor' => 'as-char',
]));
$template->save($h6);

$h7 = $outputDir . '/H7-imageElement-header-as-char-identical.odt';
$template = new OdtTemplate($base);
$template->setElement('header_logo', new ImageElement($imagePath, [
    'width' => '1cm',
    'anchor' => 'as-char',
]));
$template->save($h7);

$h6Frame = frameXml($h6, 'content.xml', false);
$h7Frame = frameXml($h7, 'styles.xml', true);

$summary = [
    'FRAME-LAYOUT-01 cross-part image fixture matrix',
    'Writer-authored base: ' . $base,
    '',
    'H1  setImage(), as-char                       known visible baseline',
    'H2  ImageElement, as-char                     known invisible baseline',
    'H3  ImageElement frame with style-name removed',
    'H4  ImageElement frame referencing Graphics style',
    'H5  setImage paragraph/frame structure + ImageElement generated style-name',
    'H6  ImageElement subtree in body',
    'H7  ImageElement subtree in header',
    '',
    'H1 body placeholder remains: ' . (str_contains(entry($h1, 'content.xml'), '{{body_logo}}') ? 'YES' : 'NO'),
    'H6 body placeholder remains: ' . (str_contains(entry($h6, 'content.xml'), '{{body_logo}}') ? 'YES' : 'NO'),
    'H6 body draw:image count: ' . drawImageCount($h6, 'content.xml'),
    'H7 header draw:image count: ' . headerDrawImageCount($h7),
    '',
    'H6/H7 canonical frame subtree equal: ' . (($h6Frame === $h7Frame) ? 'YES' : 'NO'),
    'H6 frame SHA-256: ' . hash('sha256', $h6Frame),
    'H7 frame SHA-256: ' . hash('sha256', $h7Frame),
    '',
    'Open every H1-H7 file in LibreOffice Writer and record image visibility.',
    'Do not commit generated tmp/ artifacts.',
];

file_put_contents($outputDir . '/RESULTS.txt', implode(PHP_EOL, $summary) . PHP_EOL);

echo implode(PHP_EOL, $summary) . PHP_EOL;
echo PHP_EOL . 'Generated in: ' . $outputDir . PHP_EOL;

function assertBaseFixture(string $odtPath): void
{
    $content = entry($odtPath, 'content.xml');
    $styles = entry($odtPath, 'styles.xml');

    if (!str_contains($content, '{{body_logo}}')) {
        throw new RuntimeException(
            'Writer-authored base fixture must contain {{body_logo}} in content.xml.'
        );
    }

    if (!str_contains($styles, '{{header_logo}}')) {
        throw new RuntimeException(
            'Writer-authored base fixture must contain {{header_logo}} in styles.xml header content.'
        );
    }

    $stylesDom = new DOMDocument();
    if (!$stylesDom->loadXML($styles)) {
        throw new RuntimeException('Writer-authored base styles.xml is not well-formed XML.');
    }

    $xpath = xpath($stylesDom);
    if ($xpath->query('//style:master-page/style:header')->length === 0) {
        throw new RuntimeException(
            'Writer-authored base fixture must contain a real style:header in a master page.'
        );
    }
}

function mutateEntry(string $odtPath, string $entry, callable $mutator): void
{
    $zip = new ZipArchive();
    if ($zip->open($odtPath) !== true) {
        throw new RuntimeException('Unable to open ODT: ' . $odtPath);
    }

    $xml = $zip->getFromName($entry);
    if (!is_string($xml)) {
        $zip->close();
        throw new RuntimeException('Entry not found: ' . $entry);
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = true;
    if (!$dom->loadXML($xml)) {
        $zip->close();
        throw new RuntimeException('Unable to parse ' . $entry);
    }

    $mutator($dom);

    $zip->addFromString($entry, $dom->saveXML() ?: '');
    $zip->close();
}

function headerFrame(DOMDocument $dom): ?DOMElement
{
    $xpath = xpath($dom);
    $node = $xpath->query(
        '//style:master-page[@style:name="Standard"]/style:header//draw:frame'
    )->item(0);

    return $node instanceof DOMElement ? $node : null;
}

function imageElementStyleDefinition(string $odtPath): ?array
{
    $styles = entry($odtPath, 'styles.xml');
    $dom = new DOMDocument();
    if (!$dom->loadXML($styles)) {
        return null;
    }

    $frame = headerFrame($dom);
    if (!$frame instanceof DOMElement) {
        return null;
    }

    $name = $frame->getAttributeNS(DRAW_NS, 'style-name');
    if ($name === '') {
        $name = $frame->getAttribute('draw:style-name');
    }
    if ($name === '') {
        return null;
    }

    $xpath = xpath($dom);
    $style = $xpath->query(
        '//style:style[@style:name="' . $name . '" and @style:family="graphic"]'
    )->item(0);
    if (!$style instanceof DOMElement || !$style->parentNode instanceof DOMElement) {
        return null;
    }

    return [
        $name,
        $styles,
        $style->parentNode->localName,
    ];
}

function frameXml(string $odtPath, string $entryName, bool $header): string
{
    $xml = entry($odtPath, $entryName);
    $dom = new DOMDocument();
    if (!$dom->loadXML($xml)) {
        throw new RuntimeException('Unable to parse ' . $entryName);
    }

    $xpath = xpath($dom);
    $query = $header
        ? '//style:master-page[@style:name="Standard"]/style:header//draw:frame'
        : '//office:body//draw:frame';
    $frame = $xpath->query($query)->item(0);

    if (!$frame instanceof DOMElement) {
        throw new RuntimeException('Frame not found for comparison in ' . $odtPath);
    }

    return $frame->C14N() ?: '';
}

function entry(string $odtPath, string $entryName): string
{
    $zip = new ZipArchive();
    if ($zip->open($odtPath) !== true) {
        throw new RuntimeException('Unable to open ODT: ' . $odtPath);
    }

    try {
        $value = $zip->getFromName($entryName);
        if (!is_string($value)) {
            throw new RuntimeException('Entry not found: ' . $entryName);
        }

        return $value;
    } finally {
        $zip->close();
    }
}

function drawImageCount(string $odtPath, string $entryName): int
{
    $xml = entry($odtPath, $entryName);
    $dom = new DOMDocument();
    if (!$dom->loadXML($xml)) {
        throw new RuntimeException('Unable to parse ' . $entryName);
    }

    $xpath = xpath($dom);

    return $xpath->query('//draw:image')->length;
}

function headerDrawImageCount(string $odtPath): int
{
    $xml = entry($odtPath, 'styles.xml');
    $dom = new DOMDocument();
    if (!$dom->loadXML($xml)) {
        throw new RuntimeException('Unable to parse styles.xml');
    }

    $xpath = xpath($dom);

    return $xpath->query('//style:master-page/style:header//draw:image')->length;
}

function xpath(DOMDocument $dom): DOMXPath
{
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('office', OFFICE_NS);
    $xpath->registerNamespace('style', STYLE_NS);
    $xpath->registerNamespace('text', TEXT_NS);
    $xpath->registerNamespace('draw', DRAW_NS);
    $xpath->registerNamespace('xlink', XLINK_NS);

    return $xpath;
}
