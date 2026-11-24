<?php

declare(strict_types=1);

use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\OdtTemplate;
require dirname(__DIR__) . '/vendor/autoload.php';

$outputDirectory = dirname(__DIR__) . '/tmp/h03-shape-catalog-acceptance';
if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
    throw new RuntimeException('Unable to create acceptance output directory.');
}

$overviewTemplate = $outputDirectory . '/.overview-template.odt';
$individualTemplate = $outputDirectory . '/.individual-template.odt';
$layoutTemplate = $outputDirectory . '/.layout-template.odt';
writeCleanTemplate($overviewTemplate, array_map(static fn (int $index): string => 'shape_' . $index, range(0, 19)));
writeCleanTemplate($individualTemplate, ['shape']);
writeCleanTemplate($layoutTemplate, ['layout_0', 'layout_1', 'layout_2']);

$presets = [
    'rectangle',
    'ellipse',
    'diamond',
    'arrow-contour',
    'round-rectangle',
    'triangle',
    'isosceles-triangle',
    'hexagon',
    'pentagon',
    'chevron',
    'left-arrow',
    'up-arrow',
    'down-arrow',
    'star5',
    'cross',
    'speech-bubble',
    'ring',
    'flowchart-stored-data',
    'flowchart-off-page-connector',
    'flowchart-punched-tape',
];

$colors = ['#2365B5', '#D97706', '#15803D', '#7C3AED', '#BE123C'];

generateOverview($overviewTemplate, $outputDirectory, $presets, $colors);
foreach ($presets as $index => $preset) {
    generateIndividual($individualTemplate, $outputDirectory, $preset, $colors[$index % count($colors)]);
}
generateLayoutTest($layoutTemplate, $outputDirectory);

unlink($overviewTemplate);
unlink($individualTemplate);
unlink($layoutTemplate);
writeManualChecklist($outputDirectory . '/MANUAL-ACCEPTANCE.md');
packageAcceptance($outputDirectory);

function generateOverview(string $templatePath, string $directory, array $presets, array $colors): void
{
    $template = new OdtTemplate($templatePath);
    foreach ($presets as $index => $preset) {
        $template->setElement('shape_' . $index, new ShapeElement(
            ShapeGeometry::preset($preset),
            ShapeAppearance::solid($colors[$index % count($colors)], 'solid'),
            DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '3.2cm', 'height' => '2.4cm'])
        ));
    }
    $template->save($directory . '/Shape-Catalog-Overview.odt');
    $template->cleanup();
}

function generateIndividual(string $templatePath, string $directory, string $preset, string $color): void
{
    $template = new OdtTemplate($templatePath);
    $template->setElement('shape', new ShapeElement(
        ShapeGeometry::preset($preset),
        ShapeAppearance::solid($color, 'solid'),
        DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '8cm', 'height' => '6cm'])
    ));
    $template->save($directory . '/Shape-' . fileName($preset) . '.odt');
    $template->cleanup();
}

function generateLayoutTest(string $templatePath, string $directory): void
{
    $template = new OdtTemplate($templatePath);
    $shapes = [
        ['anchor' => 'paragraph', 'width' => '5cm', 'height' => '2cm', 'horizontal' => ['offset' => '0cm', 'relative-to' => 'paragraph-content'], 'vertical' => ['offset' => '0cm', 'relative-to' => 'paragraph'], 'wrap' => 'parallel'],
        ['anchor' => 'paragraph', 'width' => '4cm', 'height' => '4cm', 'horizontal' => ['offset' => '5.4cm', 'relative-to' => 'paragraph-content'], 'vertical' => ['offset' => '0.4cm', 'relative-to' => 'paragraph'], 'wrap' => 'none'],
        ['anchor' => 'paragraph', 'width' => '3cm', 'height' => '2cm', 'horizontal' => ['offset' => '2cm', 'relative-to' => 'paragraph-content'], 'vertical' => ['offset' => '5cm', 'relative-to' => 'paragraph'], 'wrap' => 'run-through'],
    ];
    foreach ($shapes as $index => $layout) {
        $template->setElement('layout_' . $index, new ShapeElement(
            ShapeGeometry::preset(['rectangle', 'star5', 'speech-bubble'][$index]),
            ShapeAppearance::solid(['#D1D5DB', '#2365B5', '#D97706'][$index], 'solid'),
            DrawingLayout::fromArray($layout)
        ));
    }
    $template->save($directory . '/Shape-Layout-Test.odt');
    $template->cleanup();
}

function writeCleanTemplate(string $path, array $keys): void
{
    $paragraphs = [];
    foreach ($keys as $key) {
        $paragraphs[] = '<text:p text:style-name="Standard">{{' . $key . '}}</text:p>';
    }
    $content = '<?xml version="1.0" encoding="UTF-8"?>' .
        '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:styleooo="http://openoffice.org/2009/office" xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0" office:version="1.3"><office:automatic-styles><style:style style:name="Standard" style:family="paragraph"/></office:automatic-styles><office:body><office:text>' . implode('', $paragraphs) . '</office:text></office:body></office:document-content>';
    $styles = '<?xml version="1.0" encoding="UTF-8"?><office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0" office:version="1.3"><office:styles><style:default-style style:family="paragraph"><style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0.2cm"/></style:default-style></office:styles><office:automatic-styles/></office:document-styles>';
    $manifest = '<?xml version="1.0" encoding="UTF-8"?><manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.3"><manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/><manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="settings.xml" manifest:media-type="text/xml"/></manifest:manifest>';
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Unable to create clean ODT template.');
    }
    $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
    $zip->addFromString('content.xml', $content);
    $zip->addFromString('styles.xml', $styles);
    $zip->addFromString('meta.xml', '<?xml version="1.0" encoding="UTF-8"?><office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" office:version="1.3"><office:meta/></office:document-meta>');
    $zip->addFromString('settings.xml', '<?xml version="1.0" encoding="UTF-8"?><office:document-settings xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" office:version="1.3"><office:settings/></office:document-settings>');
    $zip->addFromString('META-INF/manifest.xml', $manifest);
    $zip->close();
}

function fileName(string $preset): string
{
    return ucwords(str_replace('-', '_', $preset));
}

function writeManualChecklist(string $path): void
{
    file_put_contents($path, <<<'MARKDOWN'
# H03 Shape Catalog — manual Writer acceptance

This package is technical evidence only. Headless LibreOffice roundtrip and PDF/PNG rendering do not prove GUI editability.

For `Shape-Catalog-Overview.odt`, each individual `Shape-*.odt`, and `Shape-Layout-Test.odt`:

- [ ] Open in LibreOffice Writer without repair warnings.
- [ ] Select every shape as a native draw object, not an image or frame-only raster.
- [ ] Resize and reposition the selected shape; confirm the contour remains native.
- [ ] Change fill color and outline/stroke in the GUI.
- [ ] For adjustable shapes, locate and move the yellow handle where available: round rectangle, isosceles triangle, ring, and left arrow.
- [ ] Save, close, reopen, and repeat selection/edit checks.
- [ ] In `Shape-Layout-Test.odt`, verify paragraph anchors, sizes, wrapping, offsets, and the three-shape paint order.
- [ ] Record any repair prompt, lost handle, flattened contour, or changed wrapping below.

## Reviewer notes

Date: ____________________  LibreOffice version: ____________________

Notes: __________________________________________________________________

________________________________________________________________________
MARKDOWN
    );
}

function packageAcceptance(string $directory): void
{
    $zipPath = $directory . '/H03-SLICE2-SHAPE-CATALOG-ACCEPTANCE.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Unable to create acceptance ZIP.');
    }
    foreach (glob($directory . '/*') ?: [] as $file) {
        if (is_file($file) && basename($file) !== basename($zipPath)) {
            $zip->addFile($file, basename($file));
        }
    }
    $visualDirectory = dirname(__DIR__) . '/tmp/visual-regression';
    foreach (['pdf', 'images'] as $subdirectory) {
        foreach (glob($visualDirectory . '/' . $subdirectory . '/*Shape-*') ?: [] as $file) {
            if (is_file($file)) {
                $zip->addFile($file, 'visual-regression/' . $subdirectory . '/' . basename($file));
            }
        }
    }
    $generator = __FILE__;
    $zip->addFile($generator, 'tools/h03-generate-shape-catalog-acceptance.php');
    $zip->close();
}
