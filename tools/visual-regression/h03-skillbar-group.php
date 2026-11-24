<?php

declare(strict_types=1);

use OdtTemplateEngine\Elements\SkillBarGroup;
use OdtTemplateEngine\OdtTemplate;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php tools/visual-regression/h03-skillbar-group.php <template.odt> <output.odt>\n");
    fwrite(STDERR, "The Writer template must contain {{SkillBars}} in a text paragraph.\n");
    exit(2);
}

$template = new OdtTemplate($argv[1]);

try {
    $skills = (new SkillBarGroup(
        name: 'Skills',
        x: 0.263,
        y: 0.0,
        width: 3.001,
        trackColor: '#DDE2E8',
        fillColor: '#2776B6',
        barHeight: 0.15,
        fontFamily: 'Liberation Sans',
        fontSize: 8,
        fontColor: '#FFFFFF',
        labelGap: 0.10,
        skillGap: 0.25
    ))
        ->addSkill('Teamarbeit', 100)
        ->addSkill('Kommunikation', 72)
        ->addSkill('Organisation', 84);

    $template->setElement('SkillBars', $skills);
    $template->save($argv[2]);
} finally {
    $template->cleanup();
}
