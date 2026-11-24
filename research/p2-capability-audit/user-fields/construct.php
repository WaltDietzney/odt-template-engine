<?php

declare(strict_types=1);

require __DIR__ . '/../v04/native/bootstrap.php';

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\PageLayoutOdtTemplate;

$fixture = dirname(__DIR__, 3) . '/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt';
copy($fixture, __DIR__ . '/construction-seed.odt');
$zip = new ZipArchive();
$zip->open(__DIR__ . '/construction-seed.odt');
$dom = new DOMDocument();
$dom->loadXML($zip->getFromName('content.xml'));
$body = $dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:office:1.0', 'text')->item(0);
while ($body->firstChild !== null) {
    $body->removeChild($body->firstChild);
}
$zip->addFromString('content.xml', $dom->saveXML());
$zip->close();
$t = new PageLayoutOdtTemplate(__DIR__ . '/construction-seed.odt');
try {
    $t->declareUserField('contact.name', 'Ada Beispiel');
    $t->declareUserField('contact.email', 'initial@example.invalid');
    $t->declareUserField('contact.phone', '');
    $t->definePageLayout('FieldLayout', [
        'page-width' => '21cm', 'page-height' => '29.7cm',
        'margin-top' => '1.5cm', 'margin-bottom' => '1.5cm',
        'margin-left' => '1.5cm', 'margin-right' => '1.5cm',
    ]);
    $t->definePageStyle('FieldPage', 'FieldLayout', [
        'header' => (new Paragraph())->addText('Profil: ')->addUserField('contact.name'),
        'footer' => (new Paragraph())->addText('Kontakt: ')->addUserField('contact.email'),
    ]);
    $t->assignPageStyle('FieldBody', 'FieldPage');
    $t->appendBody((new Paragraph('FieldBody'))->addText('Native Feldkonstruktion', ['bold' => true]));
    $t->appendBody((new Paragraph())->addText('E-Mail: ')
        ->addUserField('contact.email', ['color' => '#375E80', 'bold' => true])
        ->addText(' / erneut: ')->addUserField('contact.email'));
    $t->appendBody((new Paragraph())->addText('Telefon: ')->addUserField('contact.phone'));
    $t->setUserField('contact.email', 'aenne@example.invalid');
    $t->setUserField('contact.phone', '+49 5221 123456');
    $t->render();
    $t->save(__DIR__ . '/constructed.odt');
    $t->save(__DIR__ . '/constructed-repeat.odt');
} finally {
    $t->cleanup();
}
$t = new PageLayoutOdtTemplate(__DIR__ . '/constructed.odt');
try {
    $t->setUserField('contact.name', 'Änne & Co');
    $t->save(__DIR__ . '/constructed-reloaded.odt');
} finally {
    $t->cleanup();
}
