<?php

declare(strict_types=1);

use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php tools/visual-regression/h04-table-column-identity.php <output.odt>\n");
    exit(2);
}

$template = new class(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt') extends OdtTemplate {
    public function contentForRegression(): DOMDocument
    {
        return $this->documentContext()->contentDom();
    }
};

try {
    $dom = $template->contentForRegression();
    $styleNs = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    $tableNs = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    $officeNs = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    $textNs = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    $automatic = $dom->getElementsByTagNameNS($officeNs, 'automatic-styles')->item(0);
    $candidate = 'co' . substr(hash('sha256', serialize(['table-column', 'automatic', 'content.xml', ['style:column-width' => '3cm']])), 0, 12);
    foreach (['co0' => '5cm', $candidate => '9cm'] as $name => $width) {
        $style = $dom->createElementNS($styleNs, 'style:style');
        $style->setAttributeNS($styleNs, 'style:name', $name);
        $style->setAttributeNS($styleNs, 'style:family', 'table-column');
        $properties = $dom->createElementNS($styleNs, 'style:table-column-properties');
        $properties->setAttributeNS($styleNs, 'style:column-width', $width);
        $style->appendChild($properties);
        $automatic->appendChild($style);
    }
    $body = $dom->getElementsByTagNameNS($officeNs, 'text')->item(0);
    foreach ($body->getElementsByTagNameNS($textNs, 'p') as $paragraph) {
        if ($paragraph->textContent === '{{tableblock}}') {
            $paragraph->textContent = 'H04 - Table column identity regression';
        }
    }
    $tableStyle = $dom->createElementNS($styleNs, 'style:style');
    $tableStyle->setAttributeNS($styleNs, 'style:name', 'H04AuthoredTable');
    $tableStyle->setAttributeNS($styleNs, 'style:family', 'table');
    $tableProperties = $dom->createElementNS($styleNs, 'style:table-properties');
    $tableProperties->setAttributeNS($styleNs, 'style:width', '5cm');
    $tableProperties->setAttributeNS($tableNs, 'table:align', 'left');
    $tableStyle->appendChild($tableProperties);
    $automatic->appendChild($tableStyle);
    $authored = $dom->createElementNS($tableNs, 'table:table');
    $authored->setAttributeNS($tableNs, 'table:name', 'Authored');
    $authored->setAttributeNS($tableNs, 'table:style-name', 'H04AuthoredTable');
    $column = $dom->createElementNS($tableNs, 'table:table-column');
    $column->setAttributeNS($tableNs, 'table:style-name', 'co0');
    $authored->appendChild($column);
    $row = $dom->createElementNS($tableNs, 'table:table-row');
    $cell = $dom->createElementNS($tableNs, 'table:table-cell');
    $cell->appendChild($dom->createElementNS($textNs, 'text:p', 'Authored co0: 5 cm'));
    $row->appendChild($cell);
    $authored->appendChild($row);
    $body->appendChild($authored);

    foreach ([
        ['AbsoluteA', ['3cm', '9cm'], false],
        ['AbsoluteB', ['8cm', '4cm'], false],
        ['Reversed', ['9cm', '3cm'], false],
        ['EqualReuse', ['3cm', '9cm'], false],
        ['RelativeA', [1, 3], true],
        ['RelativeB', [3, 1], true],
    ] as [$name, $widths, $relative]) {
        $table = (new RichTable())->setTableName($name)
            ->setTableStyle(['width' => '12cm', 'alignment' => 'left']);
        if ($relative) {
            $table->setColumnWidthRatios($widths);
        } else {
            $table->setColumnWidths($widths);
        }
        $table->addRow([
            new RichTableCell($name . ' / ' . $widths[0], ['background' => '#cce5ef', 'padding' => '0.15cm']),
            new RichTableCell((string) $widths[1], ['background' => '#e8dfc5', 'padding' => '0.15cm']),
        ]);
        $template->appendBody($table);
    }
    $template->render();
    $template->save($argv[1]);
    $template->save($argv[1]);
} finally {
    $template->cleanup();
}
