<!DOCTYPE html>
<html>

<head>
    <title>Walt Dietzneys ODT Template Engine - Samples</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <!-- In den <head> Bereich einfügen -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>

    <meta charset="UTF-8">
    <style>
        .sample-card {
            margin-bottom: 20px;
        }

        .script-block,
        .variables-block {
            background: #f1f1f1;
            padding: 10px;
            border-radius: 6px;
        }

        .download-button {
            margin-top: 10px;
        }
    </style>
</head>

<body class="w3-light-grey">

    <header class="w3-container w3-teal w3-padding">
        <img src="assets/WaltDietzney.png" alt="Logo" style="height:50px; vertical-align: middle;">
        <span class="w3-xlarge" style="margin-left: 10px;">Walt Dietzneys ODT Template Engine - Sample Explorer</span>
    </header>

    <div class="w3-container w3-padding">
        <input class="w3-input w3-border w3-margin-bottom" id="searchInput" type="text" placeholder="Search samples..."
            onkeyup="filterSamples()">

        <div id="sampleList">
            <?php
            require __DIR__ . '/vendor/autoload.php';

            use OdtTemplateEngine\OdtTemplate;

            $sampleDir = __DIR__ . '/samples';
            $outputDir = __DIR__ . '/samples/output';
            $templateDir = __DIR__ . '/samples/templates';

            foreach (glob("$sampleDir/sample_*.php") as $sampleFile) {
                $sampleName = basename($sampleFile, '.php');
                $templateFile = $templateDir . '/' . str_replace('sample_', 'template_', basename($sampleFile, '.php')) . '.odt';

                echo '<div class="w3-card-4 w3-white sample-card" data-sample="' . htmlspecialchars($sampleName) . '">';
                echo '<div class="w3-container">';
                echo '<h3>' . htmlspecialchars($sampleName) . '</h3>';

                if (file_exists($templateFile)) {
                    try {
                        $template = new OdtTemplate($templateFile);
                        $template->load();
                        $variables = $template->extractTemplateVariables();
                        $metadata = $template->getMeta();

                        // Display metadata
                        echo '<h4>Excerpt of the Document Metadata of <b>' . basename($templateFile) . '</b> </h2>';
                        echo '<ul>';
                        foreach ($metadata as $key => $value) {
                           if ($key == 'title' || $key == 'description'  || $key == 'subject' || $key == 'keywords') {
                                echo '<li><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</li>';
                            }
                        }
                        echo '</ul>';

                        echo '<button onclick="toggleVisibility(\'vars-' . $sampleName . '\')" class="w3-button w3-small w3-teal w3-margin">Show/Hide Variables</button>';
                        echo '<div id="vars-' . $sampleName . '" class="w3-hide variables-block">';
                        foreach ($variables as $type => $vars) {
                            echo '<h4>' . ucfirst(str_replace('_', ' ', $type)) . '</h4>';
                            if (is_array($vars)) {
                                echo '<ul class="">';
                                foreach ($vars as $var => $opts) {
                                    if (is_array($opts)) {
                                        echo '<li><strong>' . htmlspecialchars($var) . ':</strong> ' . htmlspecialchars(implode(", ", $opts)) . '</li>';
                                    } else {
                                        echo '<li>' . htmlspecialchars($opts) . '</li>';
                                    }
                                }
                                echo '</ul>';
                            }
                        }
                        echo '</div>';
                    } catch (Exception $e) {
                        echo '<div class="w3-pale-yellow w3-padding">Template could not be loaded.</div>';
                    }
                } else {
                    echo '<div class="w3-pale-yellow w3-padding">No template available for this sample.</div>';
                }

                echo '<button onclick="toggleVisibility(\'script-' . $sampleName . '\')" class="w3-button w3-small w3-teal w3-margin">Show/Hide Script</button>';
                echo '<div id="script-' . $sampleName . '" class="w3-hide script-block" style="position: relative;">';

                // Der neue Copy-Button
                echo '<button onclick="copyToClipboard(\'code-' . $sampleName . '\', this)" class="w3-button w3-small w3-light-grey w3-border" style="position: absolute; top: 8px; right: 8px;">📋 Copy</button>';


                // Dein Code-Block bleibt so
                echo '<pre style="margin-top: 40px;"><code id="code-' . $sampleName . '">' . htmlspecialchars(file_get_contents($sampleFile)) . '</code></pre>';

                echo '</div>';

                echo '<button class="w3-button w3-green w3-margin download-button" onclick="generateSample(\'' . $sampleName . '\')">Generate & Download ODT</button>';
                echo '</div>';
                echo '</div>';

            }
            ?>
        </div>
    </div>

    <script>
        function toggleVisibility(id) {
            var x = document.getElementById(id);
            if (x.classList.contains('w3-hide')) {
                x.classList.remove('w3-hide');
            } else {
                x.classList.add('w3-hide');
            }
        }

        function filterSamples() {
            var input, filter, cards, card, i;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            cards = document.getElementById("sampleList").getElementsByClassName("sample-card");

            for (i = 0; i < cards.length; i++) {
                card = cards[i];
                if (card.getAttribute('data-sample').toUpperCase().indexOf(filter) > -1) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            }
        }

        function generateSample(sampleName) {
            console.log("Generating sample:", sampleName);

            fetch('generate.php?sample=' + sampleName)
                .then(response => response.json())
                .then(data => {
                    console.log("Response received:", data);
                    if (data.status === 'success') {
                        // NEU: Gehe auf download.php und übergebe die URL
                        window.location.href = 'download.php?file=' + encodeURIComponent(data.url);
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch failed:', error);
                    alert("An error occurred while generating the file.");
                });
        }

        function copyToClipboard(elementId, button) {
            const codeBlock = document.getElementById(elementId);
            if (!codeBlock) return;

            navigator.clipboard.writeText(codeBlock.innerText).then(() => {
                const originalText = button.innerHTML;
                button.innerHTML = "✅ Copied!";
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 1500);
            }).catch(err => {
                console.error('Failed to copy text:', err);
            });
        }


    </script>

</body>

</html>