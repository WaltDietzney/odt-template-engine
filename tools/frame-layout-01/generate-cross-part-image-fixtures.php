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

if (!is_file($imagePath)) {
    throw new RuntimeException('Expected image asset not found: ' . $imagePath);
}

if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
    throw new RuntimeException('Unable to create output directory: ' . $outputDir);
}

$base = createBaseFixture($root, $outputDir);

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
[$h2StyleName, $h2StyleXml, $h2Container] = $h2Style;
mutateEntry($h5, 'styles.xml', static function (DOMDocument $dom) use (
    $h2StyleName,
    $h2StyleXml,
    $h2Container
): void {
    $frame = headerFrame($dom);
    if (!$frame instanceof DOMElement) {
        throw new RuntimeException('H5: header frame not found.');
    }
    $frame->setAttributeNS(DRAW_NS, 'draw:style-name', $h2StyleName);

    $sourceDom = new DOMDocument();
    if (!$sourceDom->loadXML($h2StyleXml)) {
        throw new RuntimeException('H5: unable to parse copied ImageElement style.');
    }

    $xpath = xpath($dom);
    $container = $xpath->query('//office:' . $h2Container)->item(0);
    if (!$container instanceof DOMElement) {
        throw new RuntimeException('H5: target style container not found: ' . $h2Container);
    }

    $container->appendChild($dom->importNode($sourceDom->documentElement, true));
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
    '',
    'H1  setImage(), as-char                       known visible baseline',
    'H2  ImageElement, as-char                     known invisible baseline',
    'H3  ImageElement frame with style-name removed',
    'H4  ImageElement frame referencing Graphics style',
    'H5  setImage paragraph/frame structure + ImageElement generated style-name',
    'H6  ImageElement subtree in body',
    'H7  ImageElement subtree in header',
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

function createBaseFixture(string $root, string $outputDir): string
{
    $source = $root . '/samples/templates/template_01_simple_variables.odt';
    if (!is_file($source)) {
        throw new RuntimeException('Base ODT template not found: ' . $source);
    }

    $target = $outputDir . '/_base-cross-part-image-fixture.odt';
    if (!copy($source, $target)) {
        throw new RuntimeException('Unable to copy base ODT fixture.');
    }

    $zip = new ZipArchive();
    if ($zip->open($target) !== true) {
        throw new RuntimeException('Unable to open base fixture.');
    }

    $zip->addFromString('content.xml', contentXml());
    $zip->addFromString('styles.xml', stylesXml());
    $zip->close();

    return $target;
}

function contentXml(): string
{
    return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<office:document-content
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    office:version="1.2">
    <office:automatic-styles/>
    <office:body>
        <office:text>
            <text:p text:style-name="Standard">FRAME-LAYOUT-01 H1-H7 body</text:p>
            <text:p text:style-name="Standard">{{body_logo}}</text:p>
        </office:text>
    </office:body>
</office:document-content>
XML;
}

function stylesXml(): string
{
    return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    office:version="1.2">
    <office:styles>
        <style:style style:name="Standard" style:family="paragraph"/>
        <style:style style:name="Header" style:family="paragraph" style:parent-style-name="Standard"/>
        <style:style style:name="Graphics" style:family="graphic"/>
    </office:styles>
    <office:automatic-styles>
        <style:page-layout style:name="Mpm1">
            <style:page-layout-properties
                fo:page-width="21cm"
                fo:page-height="29.7cm"
                fo:margin="2cm"/>
            <style:header-style/>
        </style:page-layout>
    </office:automatic-styles>
    <office:master-styles>
        <style:master-page style:name="Standard" style:page-layout-name="Mpm1">
            <style:header>
                <text:p text:style-name="Header">HEADER IMAGE: {{header_logo}}</text:p>
            </style:header>
        </style:master-page>
    </office:master-styles>
</office:document-styles>
XML;
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
        $dom->saveXML($style) ?: '',
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
