<?php
//require_once 'vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;

// Lade Template
$template = new OdtTemplate('samples/templates/template_21_cvProfile.odt');
$template->load();

// ==== Beispiel-Daten ====
$contact=['Vorname' => 'Max','Nachname' => 'Mustermann', 'strasse'   => 'Musterstr. 122','ort'   => '32456 Musterhausen','mail' => 'Max@Muster.de','telefon' => '01234 5678910'];

$data = [
    'softskills' => ['Teamfähigkeit', 'Kommunikationsstärke', 'Problemlösung', 'Eigeninitiative'],
    'certs' => ['SCRUM Master', 'ITIL Foundation', 'AWS Certified Developer'],
    'languages' => [
        ['name' => 'Deutsch', 'level' => 10],
        ['name' => 'Englisch', 'level' => 9],
        ['name' => 'Französisch', 'level' => 6],
    ],
    'it' => [
        ['name' => 'PHP', 'level' => 9],
        ['name' => 'JavaScript', 'level' => 8],
        ['name' => 'SQL', 'level' => 7],
        ['name' => 'Python', 'level' => 6],
    ],
];

$data['career'] = [
    'highlights' => [
        'Erfolgreiche Einführung von DevOps-Prozessen',
        'Migration auf cloud-native Architektur',
    ],
    'berufserfahrung' => [
        ['title' => 'Senior Developer – Acme GmbH', 'desc' => 'Leitung von Backend-Architektur und API-Design.'],
        ['title' => 'Consultant – Example AG', 'desc' => 'Beratung zur digitalen Transformation.'],
    ],
    'studium' => [
        ['title' => 'B.Sc. Informatik – TU Berlin', 'desc' => 'Schwerpunkt: Software Engineering.'],
    ],
    'ausbildung' => [
        ['title' => 'Fachinformatiker – IHK Berlin', 'desc' => 'Dual bei CodeCorp GmbH.'],
    ],
    'qualifikationen' => [
        ['title' => 'Scrum Advanced Training', 'desc' => 'Zertifiziert nach SCRUM@Scale.'],
    ]
];


function addBullet(array $data, string $replace, $element)
{
    $rich = new RichText();
    $rich->addBulletList($data);
    $element->setElement($replace, $rich);
}

$template->assign($contact);

addBullet($data['softskills'], 'softskills', $template);
addBullet($data['certs'], 'certs', $template);
// === Softskills (Bullet-Liste) ===
// $rtSoftskills = new RichText();
// $rtSoftskills->addBulletList($data['softskills']);
// $template->setElement('softskills', $rtSoftskills);

// === Zertifikate (Bullet-Liste) ===
// $rtCerts = new RichText();
// $rtCerts->addBulletList($data['certs']);
// $template->setElement('certs', $rtCerts);

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

        $par -> addTabsWithTexts($tabStops);

        // $par->addText($skill['name'])
        //     ->addTab()->addTab()->addTab()
        //     ->addText($filled, ['color' => '#00B050'])
        //     ->addText($empty, ['color' => '#CCCCCC'])
        //     ->addLineBreak();
    }
    $rtIT->addParagraph($par);
    $element->setElement($replace, $rtIT);

}

addSkillsValues($data['languages'], 'languages', $template);
addSkillsValues($data['it'], 'it-skills', $template);

// === Sprachen mit Level als Bullet-Liste ===
// $rtLanguages = new RichText();
// $langItems = array_map(fn($lang) => "{$lang['name']} ({$lang['level']}/10)", $data['languages']);
// $rtLanguages->addBulletList($langItems);
// $template->setElement('languages', $rtLanguages);

// // === IT-Kenntnisse mit farbiger Bewertung ===
// $rtIT = new RichText();
// $par = new Paragraph();

// foreach ($data['it'] as $skill) {
//     $level = (int) $skill['level'];
//     $filled = str_repeat('◘', $level);
//     $empty = str_repeat('○', 10 - $level);

//     $par->addText($skill['name'])
//         ->addTab()->addTab()->addTab()
//         ->addText($filled, ['color' => '#00B050'])
//         ->addText($empty, ['color' => '#CCCCCC'])
//         ->addLineBreak();
// }

// $rtIT->addParagraph($par);
// $template->setElement('it-skills', $rtIT);

$rtCareer = new RichText();
$opt = ['background-color' => '#f0f8ff','margin-top' => '0.5cm','margin-bottom' => '0.5cm','padding' => '0.2cm'];
// === HIGHLIGHTS
if (!empty($data['career']['highlights'])) {
    $rtCareer->addParagraph((new Paragraph('standard',$opt))->addText('✨ Highlights', ['bold' => true]));
    $rtCareer->addBulletList($data['career']['highlights'], ['color' => '#007700']);
    $rtCareer->addParagraphBreak();
}

// Helper-Funktion für Abschnitt (Titel + Liste von [title, desc])
function addSection($symbol,RichText $rt, string $heading, array $entries)
{
    $opt = ['background-color' => '#f0f8ff','margin-top' => '0.5cm','margin-bottom' => '0.5cm','padding' => '0.2cm'];
    if (empty($entries))
        return;
    $rt->addParagraph((new Paragraph('standard',$opt))->addText("$symbol {$heading}", ['bold' => true]));
    $par = new Paragraph();
    $c=count($entries);
    $z=0;
    foreach ($entries as $item) {
        $par->addText($item['title'], ['bold' => true])
            ->addLineBreak()
            ->addText($item['desc'],['font-size'=>'small']);
            ($z<$c)?$par->addLineBreak():'';
            $z++;
    }
    $rt->addParagraph($par);
   // $rt->addParagraphBreak();
}

// === Weitere Abschnitte

$data['career']['berufserfahrung']?addSection('💼',$rtCareer, 'Berufserfahrung', $data['career']['berufserfahrung']):'';
$data['career']['berufserfahrung']?addSection('🎓',$rtCareer, 'Studium', $data['career']['studium']):'';
$data['career']['berufserfahrung']?addSection('🏫 ',$rtCareer, 'Ausbildung', $data['career']['ausbildung']):'';
$data['career']['berufserfahrung']?addSection('📜',$rtCareer, 'Qualifikationen', $data['career']['qualifikationen']):'';

// === Ins Template einfügen
$template->setElement('berufserfahrungen', $rtCareer);

$template->setImage('foto', 'assets/Logo-2.png', [
    'width' => '3.5cm',
    'anchor' => 'paragraph',
    'align' => 'left'
]);

// === QR-Code via Google API ===
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


// ==== Generieren ====
$template->render();
$template->save('samples/output/output_21_cvProfile.odt');

echo "Dokument erfolgreich generiert: output/output_21_cvProfile.odt\n";
