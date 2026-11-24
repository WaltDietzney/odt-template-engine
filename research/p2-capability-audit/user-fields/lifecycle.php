<?php

declare(strict_types=1);

require __DIR__ . '/../v04/native/bootstrap.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\UserFieldBindingException;

$results = [];
foreach (['metadata', 'field', 'both'] as $mode) {
    $template = new OdtTemplate(__DIR__ . '/control.odt');
    try {
        if ($mode !== 'field') {
            $template->setMeta(['custom' => ['contact.email' => 'metadata-updated@example.invalid']]);
        }
        if ($mode !== 'metadata') {
            $template->setUserField('contact.email', 'field-updated@example.invalid');
        }
        $template->save(__DIR__ . '/' . $mode . '.odt');
        $template->save(__DIR__ . '/' . $mode . '-repeat.odt');
        $results[$mode] = $template->getMeta()['custom'];
    } finally {
        $template->cleanup();
    }
    $reopened = new OdtTemplate(__DIR__ . '/' . $mode . '.odt');
    try {
        $results[$mode . '_reopened'] = $reopened->getMeta()['custom'];
    } finally {
        $reopened->cleanup();
    }
}
// Metadata alone must not be treated as an existing Writer User Field.
$zip = new ZipArchive();
$zip->open(__DIR__ . '/control.odt');
$content = $zip->getFromName('content.xml');
$zip->close();
$dom = new DOMDocument();
$dom->loadXML($content);
foreach (['user-field-decls', 'user-field-get'] as $name) {
    $nodes = iterator_to_array($dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', $name));
    foreach ($nodes as $node) {
        $node->parentNode->removeChild($node);
    }
}
copy(__DIR__ . '/control.odt', __DIR__ . '/metadata-only.odt');
$zip->open(__DIR__ . '/metadata-only.odt');
$zip->addFromString('content.xml', $dom->saveXML());
$zip->close();
$template = new OdtTemplate(__DIR__ . '/metadata-only.odt');
try {
    try {
        $template->setUserField('contact.email', 'unexpected');
        throw new RuntimeException('Metadata was incorrectly treated as a Writer User Field.');
    } catch (UserFieldBindingException $exception) {
        $results['metadata_only_binding_reason'] = $exception->reason();
        if ($exception->reason() !== UserFieldBindingException::NOT_FOUND) {
            throw $exception;
        }
    }
} finally {
    $template->cleanup();
}
file_put_contents(__DIR__ . '/lifecycle.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
