<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$projectRoot = dirname(__DIR__, 2);
$sampleDir = $projectRoot . '/samples';
$templateDir = $sampleDir . '/templates';
$sampleFiles = array_values(array_filter(
    glob($sampleDir . '/sample_*.php') ?: [],
    static function (string $sampleFile): bool {
        if (preg_match('/^sample_(\d{2})_/', basename($sampleFile), $matches) !== 1) {
            return false;
        }

        $sampleNumber = (int) $matches[1];

        return $sampleNumber >= 1 && $sampleNumber <= 21;
    }
));

/**
 * Convert a sample filename into a human-readable title.
 */
function sampleTitle(string $sampleName): string
{
    $title = preg_replace('/^sample_[0-9]+[a-z]?_?/i', '', $sampleName) ?? $sampleName;
    $title = preg_replace('/([a-z])([A-Z])/', '$1 $2', $title) ?? $title;
    $title = str_replace(['_', '-'], ' ', $title);

    return ucwords(trim($title));
}

/**
 * Assign a presentation category based on the feature demonstrated by a sample.
 *
 * @return array{slug: string, label: string}
 */
function sampleCategory(string $sampleName): array
{
    $name = strtolower($sampleName);

    if (str_contains($name, 'table')) {
        return ['slug' => 'tables', 'label' => 'Tables'];
    }

    if (str_contains($name, 'image') || str_contains($name, 'picture')) {
        return ['slug' => 'images', 'label' => 'Images'];
    }

    if (str_contains($name, 'html')) {
        return ['slug' => 'html', 'label' => 'HTML Import'];
    }

    if (
        str_contains($name, 'richtext')
        || str_contains($name, 'paragraph')
        || str_contains($name, 'list')
        || str_contains($name, 'tab')
    ) {
        return ['slug' => 'rich-content', 'label' => 'Rich Content'];
    }

    if (str_contains($name, 'metadata')) {
        return ['slug' => 'metadata', 'label' => 'Metadata'];
    }

    return ['slug' => 'core', 'label' => 'Core'];
}

/**
 * Provide concise showcase copy without coupling the demo to sample internals.
 */
function sampleDescription(string $sampleName): string
{
    $name = strtolower($sampleName);

    return match (true) {
        str_contains($name, 'variable') => 'Replace template placeholders and repeat structured data inside a real ODT document.',
        str_contains($name, 'filter') => 'Transform values with built-in template filters before they are written to the document.',
        str_contains($name, 'logic') || str_contains($name, 'smart') => 'Use conditions and template logic to control which document content is rendered.',
        str_contains($name, 'metadata') => 'Write document metadata such as title, author, description, dates and custom values.',
        str_contains($name, 'image') => 'Insert or replace images with sizing, anchoring and wrapping options.',
        str_contains($name, 'html') && str_contains($name, 'table') => 'Import structured HTML table content and render it as native ODT table markup.',
        str_contains($name, 'html') => 'Convert HTML fragments into native ODT rich text, paragraphs, lists and styles.',
        str_contains($name, 'richtext') => 'Compose styled text runs, paragraphs and mixed rich content programmatically.',
        str_contains($name, 'table') => 'Build native ODT tables with styled cells, headers, spans and layout options.',
        str_contains($name, 'list') => 'Create numbered and bulleted lists, including nested list structures.',
        str_contains($name, 'tab') => 'Control paragraph tab stops and precise text alignment inside ODT content.',
        str_contains($name, 'contact') => 'Combine structured data and rich document elements in a practical document example.',
        default => 'Explore this feature using a real template, inspect the PHP source and generate the resulting ODT file.',
    };
}

$categories = [];
foreach ($sampleFiles as $sampleFile) {
    $sampleName = basename($sampleFile, '.php');
    $category = sampleCategory($sampleName);
    $categories[$category['slug']] = $category['label'];
}
asort($categories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Interactive examples for the PHP ODT Template Engine: variables, tables, images, rich text, lists, metadata and HTML import.">
    <title>ODT Template Engine · Interactive Samples</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="site-header">
    <nav class="nav" aria-label="Main navigation">
        <a class="brand" href="./" aria-label="ODT Template Engine home">
            <span class="brand-mark">ODT</span>
            <span>ODT Template Engine</span>
        </a>
        <div class="nav-links">
            <a href="#samples">Samples</a>
            <a href="#projects">Projects</a>
            <a href="#support">Support</a>
            <a href="https://github.com/WaltDietzney/odt-template-engine" target="_blank" rel="noreferrer">GitHub</a>
        </div>
    </nav>

    <section class="hero">
        <div>
            <span class="eyebrow">PHP 8.2+ · OpenDocument Text</span>
            <h1>Build real ODT documents from PHP.</h1>
            <p class="hero-copy">
                An open-source template engine for generating editable OpenDocument files with variables,
                conditions, loops, images, rich text, lists, tables, styles and metadata.
            </p>
            <div class="hero-actions">
                <a class="button button-primary" href="#samples">Explore the samples</a>
                <a class="button button-secondary" href="https://github.com/WaltDietzney/odt-template-engine" target="_blank" rel="noreferrer">View source on GitHub</a>
            </div>
        </div>

        <aside class="hero-panel" aria-label="Project overview">
            <p class="hero-panel-title">Live repository showcase</p>
            <div class="stat-grid">
                <div class="stat">
                    <strong><?= count($sampleFiles) ?></strong>
                    <span>interactive samples</span>
                </div>
                <div class="stat">
                    <strong><?= count($categories) ?></strong>
                    <span>feature areas</span>
                </div>
                <div class="stat">
                    <strong>ODT</strong>
                    <span>editable output</span>
                </div>
                <div class="stat">
                    <strong>MIT</strong>
                    <span>open-source license</span>
                </div>
            </div>
        </aside>
    </section>
</header>

<main class="main" id="samples">
    <section class="intro">
        <div>
            <h2>Interactive feature samples</h2>
            <p>
                Inspect template variables and PHP source, then generate the actual ODT file.
                The examples use the same engine code and templates that ship with the repository.
            </p>
        </div>
        <div><strong id="resultCount"><?= count($sampleFiles) ?></strong> samples shown</div>
    </section>

    <section class="toolbar" aria-label="Sample filters">
        <label class="search-wrap" for="searchInput">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input class="search-input" id="searchInput" type="search" placeholder="Search variables, tables, images, HTML…" autocomplete="off">
        </label>

        <div class="filters" role="group" aria-label="Filter samples by category">
            <button class="filter-button is-active" type="button" data-filter="all">All</button>
            <?php foreach ($categories as $slug => $label): ?>
                <button class="filter-button" type="button" data-filter="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="sample-grid" id="sampleList">
        <?php foreach ($sampleFiles as $sampleFile): ?>
            <?php
            $sampleName = basename($sampleFile, '.php');
            $templateFile = $templateDir . '/' . str_replace('sample_', 'template_', $sampleName) . '.odt';
            $category = sampleCategory($sampleName);
            $variables = null;
            $templateAvailable = is_file($templateFile);

            if ($templateAvailable) {
                try {
                    $template = new OdtTemplate($templateFile);
                    $template->load();
                    $variables = $template->extractTemplateVariables();
                } catch (Throwable) {
                    $variables = null;
                }
            }

            $searchText = strtolower(implode(' ', [
                $sampleName,
                sampleTitle($sampleName),
                $category['label'],
                sampleDescription($sampleName),
            ]));
            ?>
            <article
                class="sample-card"
                data-category="<?= htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8') ?>"
                data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
            >
                <div class="card-main">
                    <div class="card-topline">
                        <span class="category"><?= htmlspecialchars($category['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="sample-id"><?= htmlspecialchars($sampleName, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h3><?= htmlspecialchars(sampleTitle($sampleName), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="description"><?= htmlspecialchars(sampleDescription($sampleName), ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="meta-row">
                        <span class="meta-pill <?= $templateAvailable ? 'ok' : '' ?>">
                            <?= $templateAvailable ? '✓ Template available' : 'Template not matched' ?>
                        </span>
                        <?php if (is_array($variables)): ?>
                            <span class="meta-pill"><?= count($variables) ?> template entries</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-details">
                    <details>
                        <summary>Template variables</summary>
                        <div class="detail-content">
                            <?php if (is_array($variables)): ?>
                                <pre><?= htmlspecialchars(print_r($variables, true), ENT_QUOTES, 'UTF-8') ?></pre>
                            <?php elseif ($templateAvailable): ?>
                                <p>Template variables could not be extracted.</p>
                            <?php else: ?>
                                <p>No matching template file was found for this sample.</p>
                            <?php endif; ?>
                        </div>
                    </details>
                    <details>
                        <summary>View PHP source</summary>
                        <div class="detail-content">
                            <pre><code><?= htmlspecialchars((string) file_get_contents($sampleFile), ENT_QUOTES, 'UTF-8') ?></code></pre>
                        </div>
                    </details>
                </div>

                <div class="card-actions">
                    <button class="button generate-button" type="button" data-sample="<?= htmlspecialchars($sampleName, ENT_QUOTES, 'UTF-8') ?>">
                        Generate &amp; download ODT
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="empty-state" id="emptyState">
        <strong>No matching samples.</strong><br>
        Try another search term or select a different feature category.
    </div>

    <section class="showcase-section" id="projects" aria-labelledby="projects-title">
        <div class="section-heading">
            <span class="section-kicker">Real-world usage</span>
            <h2 id="projects-title">Built with ODT Template Engine</h2>
            <p>The engine is not only a collection of samples. It powers document generation in real application projects.</p>
        </div>

        <div class="project-grid">
            <a class="project-card" href="https://bewerbungstools.de/" target="_blank" rel="noreferrer">
                <div class="project-icon">BT</div>
                <div>
                    <div class="project-status"><span></span> Live project</div>
                    <h3>Bewerbungstools.de</h3>
                    <p>Tools for creating and processing application documents, using native ODT generation as part of the document workflow.</p>
                    <span class="project-link">Visit project →</span>
                </div>
            </a>

            <a class="project-card" href="https://bewerbungstools.de/" target="_blank" rel="noreferrer">
                <div class="project-icon project-icon-alt">CV</div>
                <div>
                    <div class="project-status"><span></span> Product integration</div>
                    <h3>CV Generator</h3>
                    <p>A practical consumer of the engine for generating editable CV documents with structured sections, layouts, rich text and styles.</p>
                    <span class="project-link">Part of the application tools ecosystem →</span>
                </div>
            </a>
        </div>
    </section>

    <section class="support-section" id="support" aria-labelledby="support-title">
        <div class="support-copy">
            <span class="section-kicker section-kicker-light">Open source · MIT licensed</span>
            <h2 id="support-title">Support further development</h2>
            <p>
                ODT Template Engine is free and open source. If the library saves you time or you simply like the project,
                you can help by starring the repository or, in the future, supporting development directly.
            </p>
        </div>

        <div class="support-actions">
            <a class="support-button support-button-github" href="https://github.com/WaltDietzney/odt-template-engine" target="_blank" rel="noreferrer">
                <strong>★ Star on GitHub</strong>
                <span>Visibility helps the project grow</span>
            </a>
            <div class="support-button support-button-disabled" aria-label="PayPal support coming soon">
                <strong>PayPal</strong>
                <span>Support link coming soon</span>
            </div>
            <div class="support-button support-button-lightning" aria-label="Bitcoin Lightning support coming soon">
                <strong>⚡ Bitcoin Lightning</strong>
                <span>Lightning support coming soon</span>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <span>ODT Template Engine · PHP library for native OpenDocument generation</span>
        <div class="footer-links">
            <a href="#projects">Projects</a>
            <a href="#support">Support</a>
            <a href="https://github.com/WaltDietzney/odt-template-engine" target="_blank" rel="noreferrer">GitHub repository →</a>
        </div>
    </div>
</footer>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="app.js"></script>
</body>
</html>
