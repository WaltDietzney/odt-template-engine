<?php

declare(strict_types=1);

// Research-only executable, loading the current worktree without a copied vendor tree.
$root = dirname(__DIR__, 4);
spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'OdtTemplateEngine\\';
    if (str_starts_with($class, $prefix)) {
        $path = $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});
$evidence = [];
$evidence['runtime'] = PHP_VERSION;
$evidence['getimagesize_svg'] = getimagesize(__DIR__ . '/assets/lucide-mail.svg');
$validator = new OdtTemplateEngine\Mapping\FrameImageReplacementPreflightValidator();
$method = new ReflectionMethod($validator, 'isSupportedImage');
$evidence['preflight_svg_supported'] = $method->invoke($validator, __DIR__ . '/assets/lucide-mail.svg');
set_error_handler(static function (int $severity, string $message) use (&$evidence): bool {
    $evidence['php_warnings'][] = $message;
    return true;
});
foreach ([
    'explicit' => ['width' => '0.45cm', 'height' => '0.45cm'],
    'width-only' => ['width' => '0.45cm'],
    'default' => [],
] as $case => $options) {
    try {
        $image = new OdtTemplateEngine\Elements\ImageElement(__DIR__ . '/assets/lucide-mail.svg', $options);
        $dom = new DOMDocument();
        $evidence['image_element'][$case] = $dom->saveXML($image->toDomNode($dom));
    } catch (Throwable $error) {
        $evidence['image_element'][$case] = get_class($error) . ': ' . $error->getMessage();
    }
}
foreach (['contact-classic', 'contact-sidebar', 'contact-header-sidebar'] as $case) {
    $template = new OdtTemplateEngine\OdtTemplate(__DIR__ . '/' . $case . '.odt');
    $template->setUserField('name', 'BINDING HEADER AND BODY');
    $template->setUserField('email', 'updated.binding@example.invalid');
    $template->render();
    $template->save(__DIR__ . '/' . $case . '-bound.odt');
    $template->render();
    $template->save(__DIR__ . '/' . $case . '-bound-repeat.odt');
    $template->cleanup();
    $reopened = new OdtTemplateEngine\OdtTemplate(__DIR__ . '/' . $case . '-bound-repeat.odt');
    $reopened->setUserField('email', 'reloaded.binding@example.invalid');
    $reopened->save(__DIR__ . '/' . $case . '-reloaded.odt');
    try {
        $reopened->setUserField('new-absent-field', 'not constructible by binding');
    } catch (Throwable $error) {
        $evidence['missing_field'][$case] = get_class($error) . ': ' . $error->getMessage();
    }
    $reopened->cleanup();
}
$template = new OdtTemplateEngine\OdtTemplate(__DIR__ . '/contact-resources.odt');
$template->replaceImageByName('ContactIcon1', __DIR__ . '/assets/tabler-phone.svg', [
    'width' => '0.45cm', 'height' => '0.45cm',
]);
$template->save(__DIR__ . '/contact-resources-replaced.odt');
$template->save(__DIR__ . '/contact-resources-replaced-repeat.odt');
$template->cleanup();
file_put_contents(__DIR__ . '/engine-probe.json', json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

$template = new OdtTemplateEngine\OdtTemplate(__DIR__ . '/contact-sidebar.odt');
$template->setUserField('email', '');
$template->save(__DIR__ . '/contact-sidebar-blank-bound.odt');
$template->cleanup();
$names = json_decode(file_get_contents(__DIR__ . '/outputs.json'), true);
foreach (glob(__DIR__ . '/*-bound*.odt') as $path) { $names[] = basename($path, '.odt'); }
foreach (glob(__DIR__ . '/*-reloaded.odt') as $path) { $names[] = basename($path, '.odt'); }
foreach (glob(__DIR__ . '/*-replaced*.odt') as $path) { $names[] = basename($path, '.odt'); }
file_put_contents(__DIR__ . '/outputs.json', json_encode(array_values(array_unique($names)), JSON_PRETTY_PRINT) . "\n");
