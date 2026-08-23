<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Impressum der offiziellen ODT Template Engine Projekt- und Demoseite.">
    <title>Impressum · ODT Template Engine</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .legal-page {
            max-width: 820px;
            margin: 0 auto;
            padding: 72px 24px 96px;
        }

        .legal-page h1 {
            margin-bottom: 32px;
            font-size: clamp(2.4rem, 6vw, 4rem);
            letter-spacing: -0.04em;
        }

        .legal-page h2 {
            margin-top: 36px;
            margin-bottom: 10px;
        }

        .legal-page p {
            color: var(--muted);
            line-height: 1.75;
        }

        .legal-page a {
            color: var(--brand);
        }

        .legal-back {
            display: inline-block;
            margin-bottom: 30px;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>
<body>
<main class="legal-page">
    <a class="legal-back" href="./">← ODT Template Engine</a>

    <h1>Impressum</h1>

    <h2>Angaben gemäß § 5 DDG</h2>
    <p>
        Walter Dietz<br>
        Löhner Straße 112<br>
        32584 Löhne<br>
        Deutschland
    </p>

    <h2>Kontakt</h2>
    <p>
        E-Mail:
        <a href="mailto:walter-dietz@online.de">walter-dietz@online.de</a>
    </p>

    <h2>Verantwortlich für den Inhalt</h2>
    <p>
        Walter Dietz<br>
        Anschrift wie oben
    </p>

    <h2>Haftung für Inhalte</h2>
    <p>
        Die Inhalte dieser Website wurden mit größter Sorgfalt erstellt. Für die Richtigkeit,
        Vollständigkeit und Aktualität der Inhalte kann jedoch keine Gewähr übernommen werden.
    </p>

    <h2>Haftung für Links</h2>
    <p>
        Diese Website enthält Links zu externen Websites Dritter, auf deren Inhalte kein Einfluss
        besteht. Für diese fremden Inhalte wird daher keine Gewähr übernommen.
    </p>

    <h2>Urheberrecht und Open Source</h2>
    <p>
        ODT Template Engine wird als Open-Source-Software unter der MIT-Lizenz veröffentlicht.
        Für Quellcode und Projektdateien gelten die im Repository angegebenen Lizenzbedingungen.
        Sonstige auf dieser Website veröffentlichte Inhalte unterliegen den jeweils anwendbaren
        urheberrechtlichen Bestimmungen.
    </p>
</main>
</body>
</html>
