<?php

declare(strict_types=1);

require __DIR__ . '/../../v04/native/bootstrap.php';

$labels = json_decode(file_get_contents(__DIR__ . '/labels.json'), true);
$t = new OdtTemplateEngine\OdtTemplate(__DIR__ . '/semantic-template.odt');
$t->assign($labels);
$t->render();
$t->save(__DIR__ . '/semantic-headings.odt');
$t->render();
$t->save(__DIR__ . '/semantic-headings-repeat.odt');
$t->cleanup();
$t = new OdtTemplateEngine\OdtTemplate(__DIR__ . '/semantic-headings.odt');
$t->save(__DIR__ . '/semantic-headings-reloaded.odt');
$t->cleanup();
