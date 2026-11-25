<?php

declare(strict_types=1);

require __DIR__ . '/../native/bootstrap.php';

$t = new OdtTemplateEngine\OdtTemplate(__DIR__ . '/long.odt');
$t->setUserField('city', 'Bad Oeynhausen mit weiterem Ortsteil und langem Zusatz');
$t->setUserField('email', 'sehrlangerkontaktnameohnemanuelletrennzeichen@example.invalid');
$t->render();
$t->save(__DIR__ . '/rebound.odt');
$t->render();
$t->save(__DIR__ . '/repeat/rebound.odt');
$t->cleanup();
$specs = json_decode(file_get_contents(__DIR__ . '/specs.json'), true);
$specs['rebound'] = $specs['long'];
$specs['rebound']['data']['city'] = 'Bad Oeynhausen mit weiterem Ortsteil und langem Zusatz';
$specs['rebound']['data']['email'] = 'sehrlangerkontaktnameohnemanuelletrennzeichen@example.invalid';
file_put_contents(__DIR__ . '/specs.json', json_encode($specs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
file_put_contents(__DIR__ . '/outputs.json', json_encode(array_keys($specs), JSON_PRETTY_PRINT) . "\n");
