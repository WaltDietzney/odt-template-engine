<?php
error_reporting(0);
require 'vendor/autoload.php';


use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;

header('Content-Type: text/html; charset=utf-8');

$error = '';
$success = '';
$outputFile = '';

try {

// Lade Template
$template = new OdtTemplate('samples/templates/template_21_cvProfile.odt');
$template->load();

// ==== Beispiel-Daten ====
$contact = [
    'Vorname' => $_POST['vorname'],
    'Nachname' => $_POST['nachname'],
    'strasse' => $_POST['strasse'],
    'ort' => $_POST['ort'],
    'mail' => $_POST['email'],
    'telefon' => $_POST['telefon']
];

$nachname = $_POST['nachname'];
$vorname = $_POST['vornname'];
$telefon = $_POST['telefon'];
$mail = $_POST['email'];
$adresse = $_POST['strasse'] . ' ' . $_POST['ort'];

$vcard = rawurlencode(<<<EOT
BEGIN:VCARD
VERSION:3.0
N:$nachname;$vorname;;;
FN:$vorname $nachname
TEL:$telefon
EMAIL:$email
ADR;TYPE=home:;;$adresse;;;;
END:VCARD
EOT);

// Google Chart API URL
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?data=$vcard&size=[150]x[150]";
$image = 'qrcode/images/image' . uniqId() . '.png';

$imageData = file_get_contents($qrUrl);
if ($imageData !== false) {
    file_put_contents($image, $imageData);
}

$template->setImage('qrCode', $image,['align'=>'right','anchor'=>'paragraph','width' => '1.5cm']);

unlink($image);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = 'qrcode/images/';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['photo']['tmp_name'];
        $originalName = basename($_FILES['photo']['name']);
        $targetPath = $uploadDir . uniqid() . '_' . $originalName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            $success =  "Datei erfolgreich hochgeladen: $targetPath";
            if (file_exists($targetPath)) {
                $template->setImage('foto', $targetPath, [
                    'width' => '3.5cm',
                    'anchor' => 'paragraph',
                    'align' => 'left'
                ]);
            }
        } else {
            $error = "Fehler beim Verschieben der Datei.";
        }
    } else {
        $error = "Kein Bild hochgeladen oder ein Fehler ist aufgetreten.";
    }
}


//unlink($targetPath );

function reArrangePost($arName, $arLevel, $identifier)
{
    $dataIt = [];
    for ($j = 0; $j < count($arName); $j++) {
        $dataIt[$j] = [$identifier[0] => $arName[$j], $identifier[1] => $arLevel[$j]];
    }
    return $dataIt;
}


$data = [
    'softskills' => $_POST['softskills'],
    'certs' => $_POST['certs'],
    'languages' => (isset($_POST['languages']['name'])) ? reArrangePost($_POST['languages']['name'], $_POST['languages']['level'], ['name', 'level']) : '',
    'it' => (isset($_POST['it']['name'])) ? reArrangePost($_POST['it']['name'], $_POST['it']['level'], ['name', 'level']) : '',
];


$data['career'] = [
    'highlights' => $_POST['highlights']['title'],
    'berufserfahrung' => (isset($_POST['jobs']['title'])) ? reArrangePost($_POST['jobs']['title'], $_POST['jobs']['desc'], ['title', 'desc']) : '',
    'studium' => (isset($_POST['studium']['title'])) ? reArrangePost($_POST['studium']['title'], $_POST['studium']['desc'], ['title', 'desc']) : '',
    'ausbildung' => (isset($_POST['ausbildung']['title'])) ? reArrangePost($_POST['ausbildung']['title'], $_POST['ausbildung']['desc'], ['title', 'desc']) : '',
    'qualifikationen' => (isset($_POST['quali']['title'])) ? reArrangePost($_POST['quali']['title'], $_POST['quali']['desc'], ['title', 'desc']) : '',
];

$addr = new RichText();
$addrPar = new Paragraph('RightPara');
$addrPar->addText("🗓️ Geburtsdatum: ")
    ->addLineBreak()
    ->addText("🏠 Adresse: " . $contact['strasse'])
    ->addLineBreak()
    ->addText($contact['ort'])
    ->addLineBreak()
    ->addText('☎️ ', ['color' => '#CCCCCC'])->addText("Telefon: " . $contact['telefon'])
    ->addLineBreak()
    ->addText("✉️ Mail: " . $contact['mail']);

$addr->addParagraph($addrPar);

$template->setElement('address', $addr);

function addBullet(array $data, string $replace, $element)
{
    $rich = new RichText();
    $rich->addBulletList($data);
    $element->setElement($replace, $rich);
}

$template->assign($contact);

isset($data['softskills'])?addBullet($data['softskills'], 'softskills', $template):'';
isset($data['certs'])?addBullet($data['certs'], 'certs', $template):'';
// === Softskills (Bullet-Liste) ===


function addSkillsValues($data, $replace, $element)
{
    $rtIT = new RichText();
    $par = new Paragraph();

    foreach ($data as $skill) {
        $level = (int) $skill['level'];
        $filled = str_repeat('◘', $level);
        $empty = str_repeat('○', 10 - $level);
        $tabStops = [
            ['position' => 0.2, 'alignment' => 'left', 'text' => $skill['name'], 'style' => ['bold' => true]],
            ['position' => 8.0, 'alignment' => 'right', 'text' => $filled . $empty, 'style' => ['color' => '#00B050']],
        ];

        $par->addTabsWithTexts($tabStops);
    }
    $rtIT->addParagraph($par);
    $element->setElement($replace, $rtIT);

}

addSkillsValues($data['languages'], 'languages', $template);
addSkillsValues($data['it'], 'it-skills', $template);


$rtCareer = new RichText();
$opt = ['background-color' => '#f0f8ff', 'margin-top' => '0.5cm', 'margin-bottom' => '0.5cm', 'padding' => '0.2cm'];
// === HIGHLIGHTS
if (!empty($data['career']['highlights'])) {
    $rtCareer->addParagraph((new Paragraph('standard', $opt))->addText('✨ Highlights', ['bold' => true]));
    $rtCareer->addBulletList($data['career']['highlights'], ['color' => '#007700']);
    $rtCareer->addParagraphBreak();
}

// Helper-Funktion für Abschnitt (Titel + Liste von [title, desc])
function addSection($symbol, RichText $rt, string $heading, array $entries)
{
    $opt = ['background-color' => '#f0f8ff', 'margin-top' => '0.5cm', 'margin-bottom' => '0.5cm', 'padding' => '0.2cm'];
    if (empty($entries))
        return;
    $rt->addParagraph((new Paragraph('standard', $opt))->addText("$symbol {$heading}", ['bold' => true]));
    $par = new Paragraph();
    $c = count($entries);
    $z = 0;
    foreach ($entries as $item) {
        $par->addText($item['title'], ['bold' => true])
            ->addLineBreak()
            ->addText($item['desc'], ['font-size' => 'small']);
        ($z < $c) ? $par->addLineBreak() : '';
        $z++;
    }
    $rt->addParagraph($par);
    // $rt->addParagraphBreak();
}

// === Weitere Abschnitte

$data['career']['berufserfahrung'] ? addSection('💼', $rtCareer, 'Berufserfahrung', $data['career']['berufserfahrung']) : '';
$data['career']['studium'] ? addSection('🎓', $rtCareer, 'Studium', $data['career']['studium']) : '';
$data['career']['ausbildung'] ? addSection('🏫 ', $rtCareer, 'Ausbildung', $data['career']['ausbildung']) : '';
$data['career']['qualifikationen'] ? addSection('📜', $rtCareer, 'Qualifikationen', $data['career']['qualifikationen']) : '';

// === Ins Template einfügen
$template->setElement('berufserfahrungen', $rtCareer);

 $success .= "<br>Profil erfolgreich erstellt!";

$outputFile = 'output/output_' . uniqId() . '_cvProfile.odt';
// ==== Generieren ====
$template->render();
$template->save($outputFile );

}

catch (Exception $e) {
    $error = 'Fehler: ' . htmlspecialchars($e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Profil-Erstellung</title>
    <style>
        body { font-family: sans-serif; background: #f9f9f9; padding: 20px; }
        .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }
        .download { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .download:hover { background: #0056b3; }
    </style>
</head>
<body>

<h1>Profil-Erstellung</h1>

<?php if ($error): ?>
    <div class="message error"> <?= $error ?> </div>
<?php elseif ($success): ?>
    <div class="message success"> <?= $success ?> </div>
    <a class="download" href="<?= $outputFile ?>" download>📥 Profil herunterladen</a>
<?php endif; ?>

</body>
</html>


