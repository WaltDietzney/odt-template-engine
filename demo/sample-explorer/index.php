<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$projectRoot = dirname(__DIR__, 2);
$sampleDir = $projectRoot . '/samples';
$templateDir = $sampleDir . '/templates';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ODT Template Engine - Sample Explorer</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
</head>
<body class="w3-light-grey" style="font-family: Arial, Helvetica, sans-serif;">
<header class="w3-container w3-indigo w3-padding w3-center">
    <span class="w3-xlarge">ODT Template Engine - Sample Explorer</span>
</header>

<main class="w3-content w3-padding" style="max-width:1200px;">
    <input class="w3-input w3-border w3-margin-bottom" id="searchInput" type="search" placeholder="Search samples...">

    <div id="sampleList">
        <?php foreach (glob($sampleDir . '/sample_*.php') ?: [] as $sampleFile): ?>
            <?php
            $sampleName = basename($sampleFile, '.php');
            $templateFile = $templateDir . '/' . str_replace('sample_', 'template_', $sampleName) . '.odt';
            $searchText = $sampleName;
            ?>
            <section class="w3-card-4 w3-white w3-margin-bottom w3-padding sample-card" data-search="<?= htmlspecialchars(strtolower($searchText), ENT_QUOTES, 'UTF-8') ?>">
                <h3 class="w3-text-indigo"><?= htmlspecialchars($sampleName, ENT_QUOTES, 'UTF-8') ?></h3>

                <?php if (is_file($templateFile)): ?>
                    <?php try { ?>
                        <?php
                        $template = new OdtTemplate($templateFile);
                        $template->load();
                        $variables = $template->extractTemplateVariables();
                        ?>
                        <details>
                            <summary>Template variables</summary>
                            <pre><?= htmlspecialchars(print_r($variables, true), ENT_QUOTES, 'UTF-8') ?></pre>
                        </details>
                    <?php } catch (Throwable $exception) { ?>
                        <p class="w3-pale-yellow w3-padding">Template could not be loaded.</p>
                    <?php } ?>
                <?php else: ?>
                    <p class="w3-pale-yellow w3-padding">No matching template available.</p>
                <?php endif; ?>

                <details>
                    <summary>Sample source</summary>
                    <pre><code class="language-php"><?= htmlspecialchars((string) file_get_contents($sampleFile), ENT_QUOTES, 'UTF-8') ?></code></pre>
                </details>

                <button class="w3-button w3-light-blue w3-margin-top" type="button" data-sample="<?= htmlspecialchars($sampleName, ENT_QUOTES, 'UTF-8') ?>">
                    Generate &amp; Download ODT
                </button>
            </section>
        <?php endforeach; ?>
    </div>
</main>

<script>
    hljs.highlightAll();

    document.getElementById('searchInput').addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('.sample-card').forEach((card) => {
            card.hidden = term !== '' && !card.textContent.toLowerCase().includes(term);
        });
    });

    document.querySelectorAll('[data-sample]').forEach((button) => {
        if (button.tagName !== 'BUTTON') {
            return;
        }

        button.addEventListener('click', async () => {
            const sample = button.dataset.sample;
            const response = await fetch('generate.php?sample=' + encodeURIComponent(sample));
            const data = await response.json();

            if (!response.ok || data.status !== 'success') {
                alert(data.message || 'Sample generation failed.');
                return;
            }

            window.location.href = 'download.php?file=' + encodeURIComponent(data.file);
        });
    });
</script>
</body>
</html>
