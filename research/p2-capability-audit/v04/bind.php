<?php
declare(strict_types=1);
$root = dirname(__DIR__, 3);
$loader = require '/tmp/odt-h04-analysis/vendor/autoload.php';
$loader->setPsr4('OdtTemplateEngine\\', $root . '/src');
foreach ([
    'full' => ['address'=>'Example Street 12, 12345 Example City','phone'=>'+49 123 456789','email'=>'ada@example.invalid','website'=>'https://example.invalid'],
    'minimal' => ['address'=>'','phone'=>'','email'=>'ada@example.invalid','website'=>''],
    'long' => ['address'=>str_repeat('Long Example Street and District, ', 8).'12345 Example City','phone'=>'','email'=>str_repeat('longcontact', 8).'@example.invalid','website'=>''],
] as $name => $data) {
    $t = new OdtTemplateEngine\OdtTemplate(__DIR__.'/contact-template.odt');
    $t->setUserField('customer', 'Ada Example');
    foreach ($data as $key => $value) { $data["show_" . $key] = trim($value) !== ""; }
    $t->assign($data);
    $t->render();
    $t->save(__DIR__.'/contact-'.$name.'.odt');
    $t->cleanup();
}
