<?php
require_once '../vendor/autoload.php'; // oder wo dein Autoloader ist

use OdtTemplateEngine\OdtTemplate;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = $_POST['note'] ?? '';

    try {
        $template = new OdtTemplate('templates/test_nl2br_template.odt');
        $template->assign([
            'note' => $note
        ]);
        $template->render();
        $template->save('output/test_nl2br_result.odt');
        $message = '✅ Dokument erfolgreich erzeugt: <a href="output/test_nl2br_result.odt" download>Download</a>';
    } catch (Exception $e) {
        $message = '❌ Fehler: ' . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>OdtTemplateEngine - nl2br Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        textarea { width: 100%; height: 150px; }
        .message { margin-top: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ccc; }
    </style>
</head>
<body>

<h1>nl2br-Test mit deinem OdtTemplateEngine 🚀</h1>

<form method="post">
    <label for="note">Text eingeben:</label><br>
    <textarea name="note" id="note"><?php echo isset($_POST['note']) ? htmlspecialchars($_POST['note']) : ''; ?></textarea><br><br>
    <button type="submit">ODT erzeugen</button>
</form>

<?php if ($message): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

</body>
</html>
