<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$projectRoot = dirname(__DIR__, 2);
$sampleDir = $projectRoot . '/samples';
$sampleRegistry = require $sampleDir . '/sample-registry.php';
$sampleEntries = $sampleRegistry['samples'];
$roleLabels = [
    'learn' => 'Learn',
    'capability' => 'Capabilities',
    'builder' => 'Builders',
    'showcase' => 'Showcases',
];

$categories = [];
foreach ($sampleEntries as $sampleEntry) {
    $categories[$sampleEntry['role']] = $roleLabels[$sampleEntry['role']];
}
asort($categories);

$canonicalUrl = 'https://odt.walter-dietz.de/';
$githubUrl = 'https://github.com/WaltDietzney/odt-template-engine';
$packagistUrl = 'https://packagist.org/packages/waltdietzney/odt-template-engine';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP ODT Template Engine – Generate Editable OpenDocument Files</title>
    <meta name="description" content="Generate real, editable ODT files from PHP using LibreOffice templates. Try live PHP samples, inspect the source and download the generated OpenDocument files.">
    <link rel="canonical" href="<?= $canonicalUrl ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="PHP ODT Template Engine – Generate Editable OpenDocument Files">
    <meta property="og:description" content="Use LibreOffice ODT templates from PHP. Inspect real sample code and generate downloadable ODT documents directly in your browser.">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:site_name" content="ODT Template Engine">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="PHP ODT Template Engine">
    <meta name="twitter:description" content="Generate real editable OpenDocument Text files from PHP and try the engine online before installing it.">
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareSourceCode',
        'name' => 'ODT Template Engine',
        'description' => 'A PHP template engine for generating editable OpenDocument Text files from ODT templates.',
        'codeRepository' => $githubUrl,
        'programmingLanguage' => 'PHP',
        'runtimePlatform' => 'PHP 8.2+',
        'license' => 'https://opensource.org/license/mit',
        'url' => $canonicalUrl,
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <link rel="stylesheet" href="styles.css">
    <style>
        .install-strip { width:min(1180px,calc(100% - 40px)); margin:-32px auto 52px; position:relative; z-index:2; display:grid; grid-template-columns:1fr auto; gap:24px; align-items:center; padding:22px 26px; border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:var(--shadow); }
        .install-strip p { margin:3px 0 0; color:var(--muted); }
        .install-command { display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:12px; background:#111827; color:#dbe4f3; font:0.84rem/1.4 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; white-space:nowrap; }
        .landing-section { margin:0 0 56px; }
        .landing-section h2 { margin:0 0 10px; font-size:clamp(1.65rem,3vw,2.25rem); letter-spacing:-0.035em; }
        .landing-section > p { max-width:780px; color:var(--muted); }
        .steps-grid,.feature-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-top:24px; }
        .step-card,.feature-summary > div { padding:22px; border:1px solid var(--line); border-radius:16px; background:var(--surface); box-shadow:0 8px 24px rgba(31,45,78,.04); }
        .step-number { display:inline-grid; width:30px; height:30px; place-items:center; margin-bottom:12px; border-radius:9px; background:#eef2ff; color:#4158b7; font-weight:800; }
        .step-card h3,.feature-summary h3 { margin:0 0 8px; }
        .step-card p,.feature-summary p { margin:0; color:var(--muted); font-size:.92rem; }
        .mini-code { margin-top:14px; padding:12px; border-radius:10px; background:#111827; color:#dbe4f3; font:.76rem/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; overflow:auto; }
        .why-odt { display:grid; grid-template-columns:1.1fr .9fr; gap:28px; align-items:start; padding:30px; border:1px solid var(--line); border-radius:20px; background:#fff; }
        .why-odt ul { margin:0; padding-left:20px; color:var(--muted); }
        .why-odt li + li { margin-top:8px; }
        .link-row { display:flex; flex-wrap:wrap; gap:10px; margin-top:18px; }
        .text-link { font-weight:750; color:var(--brand); text-decoration:none; }
        @media(max-width:900px){.install-strip,.why-odt{grid-template-columns:1fr}.steps-grid,.feature-summary{grid-template-columns:1fr}.install-command{white-space:normal;overflow-wrap:anywhere}}
        @media(max-width:640px){.install-strip{width:min(100% - 28px,1180px);margin-top:-22px}}
    </style>
</head>
<body>
<header class="site-header">
    <nav class="nav" aria-label="Main navigation">
        <a class="brand" href="./" aria-label="ODT Template Engine home">
            <span class="brand-mark">ODT</span>
            <span>ODT Template Engine</span>
        </a>
        <div class="nav-links">
            <a href="#how-it-works">How it works</a>
            <a href="#samples">Live samples</a>
            <a href="#projects">Projects</a>
            <a href="#support">Support</a>
            <a href="/docs/">Documentation</a>
            <a href="<?= $githubUrl ?>" target="_blank" rel="noreferrer">GitHub</a>
        </div>
    </nav>

    <section class="hero">
        <div>
            <span class="eyebrow">PHP 8.2+ · OpenDocument Text · MIT</span>
            <h1>Build real, editable ODT documents from PHP.</h1>
            <p class="hero-copy">
                Design your document in LibreOffice, populate it from PHP and keep the result editable.
                ODT Template Engine supports variables, conditions, loops, images, rich text, lists, tables,
                styles and metadata.
            </p>
            <div class="hero-actions">
                <a class="button button-primary" href="#samples">Try it online</a>
                <a class="button button-secondary" href="<?= $githubUrl ?>" target="_blank" rel="noreferrer">View source on GitHub</a>
            </div>
        </div>

        <aside class="hero-panel" aria-label="Project overview">
            <p class="hero-panel-title">Try before you install</p>
            <div class="stat-grid">
                <div class="stat"><strong><?= count($sampleEntries) ?></strong><span>registered samples</span></div>
                <div class="stat"><strong>ODT</strong><span>editable output</span></div>
                <div class="stat"><strong>PHP</strong><span>8.2 or newer</span></div>
                <div class="stat"><strong>MIT</strong><span>open source</span></div>
            </div>
            <p style="margin:18px 0 0;color:rgba(255,255,255,.68);font-size:.88rem;">
                Inspect the PHP source and template variables, click Generate, then open the actual downloaded .odt file yourself.
            </p>
        </aside>
    </section>
</header>

<section class="install-strip" aria-label="Composer installation">
    <div>
        <strong>Install with Composer</strong>
        <p>Or use the live samples below first — no local installation required.</p>
    </div>
    <div class="install-command">composer require waltdietzney/odt-template-engine</div>
</section>

<main class="main">
    <section class="landing-section" id="how-it-works">
        <span class="section-kicker">Template-first document generation</span>
        <h2>Use the office document as the template.</h2>
        <p>
            Instead of rebuilding an office layout entirely in PHP, create the document in LibreOffice Writer,
            add template placeholders and let PHP populate the ODT package with your application data.
        </p>
        <div class="steps-grid">
            <article class="step-card">
                <span class="step-number">1</span>
                <h3>Create the ODT template</h3>
                <p>Design the document normally in LibreOffice and place variables or control structures where dynamic content belongs.</p>
                <div class="mini-code">Hello {{customer_name}}<br><br>{{#foreach:items}}<br>{{name}} — {{price}}<br>{{#endforeach}}</div>
            </article>
            <article class="step-card">
                <span class="step-number">2</span>
                <h3>Populate it from PHP</h3>
                <p>Load the template, assign application data and render the placeholders and structured content.</p>
                <div class="mini-code">$template->assign([<br>&nbsp;&nbsp;'customer_name' => 'Jane Smith',<br>]);<br>$template->render();</div>
            </article>
            <article class="step-card">
                <span class="step-number">3</span>
                <h3>Save a real ODT file</h3>
                <p>The output remains an OpenDocument Text file that users can continue editing in LibreOffice and compatible applications.</p>
                <div class="mini-code">$template->save(<br>&nbsp;&nbsp;'output/result.odt'<br>);</div>
            </article>
        </div>
    </section>

    <section class="landing-section why-odt" aria-labelledby="why-odt-title">
        <div>
            <span class="section-kicker">Why OpenDocument?</span>
            <h2 id="why-odt-title">When the generated document must stay editable.</h2>
            <p style="color:var(--muted);">
                HTML-to-PDF workflows are useful when the final result should be fixed. ODT Template Engine targets a different workflow:
                generate a native office document from PHP that can still be reviewed, changed and reused after generation.
            </p>
        </div>
        <ul>
            <li>Native <code>.odt</code> output instead of a PDF-only result.</li>
            <li>Templates can be designed visually in LibreOffice Writer.</li>
            <li>Variables, loops and conditions can live directly in the document template.</li>
            <li>Rich content such as tables, lists, images and styled paragraphs can be generated programmatically.</li>
            <li>The engine works with the XML inside real OpenDocument packages.</li>
        </ul>
    </section>

    <section class="landing-section" aria-labelledby="features-title">
        <span class="section-kicker">ODT-aware PHP API</span>
        <h2 id="features-title">From simple placeholders to structured documents.</h2>
        <div class="feature-summary">
            <div><h3>Template logic</h3><p>Variables, filters, repeating blocks, if/elseif/else and ifnot conditions.</p></div>
            <div><h3>Rich document content</h3><p>Paragraphs, formatted text, numbered and bullet lists, images and native ODT tables.</p></div>
            <div><h3>ODF package handling</h3><p>Styles, metadata, HTML import and XML-aware processing of real ODT packages.</p></div>
        </div>
        <div class="link-row">
            <a class="text-link" href="<?= $githubUrl ?>" target="_blank" rel="noreferrer">Read the documentation on GitHub →</a>
            <a class="text-link" href="<?= $packagistUrl ?>" target="_blank" rel="noreferrer">View the package on Packagist →</a>
        </div>
    </section>

    <section id="samples">
        <section class="intro">
            <div>
                <span class="section-kicker">Live Sample Explorer</span>
                <h2>Don't take the feature list on trust. Generate an ODT.</h2>
                <p>
                    Inspect the template variables and the real PHP source behind each example, then generate and download the actual ODT file.
                    The examples use the same engine code and templates that ship with the repository.
                </p>
            </div>
            <div><strong id="resultCount"><?= count($sampleEntries) ?></strong> samples shown</div>
        </section>

        <section class="toolbar" aria-label="Sample filters">
            <label class="search-wrap" for="searchInput">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input class="search-input" id="searchInput" type="search" placeholder="Search variables, tables, images, HTML…" autocomplete="off">
            </label>
            <div class="filters" role="group" aria-label="Filter samples by category">
                <button class="filter-button is-active" type="button" data-filter="all">All</button>
                <?php foreach ($categories as $slug => $label): ?>
                    <button class="filter-button" type="button" data-filter="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="sample-grid" id="sampleList">
            <?php foreach ($sampleEntries as $sampleEntry): ?>
                <?php
                $sampleFile = $projectRoot . '/' . $sampleEntry['entry_point'];
                $templateFile = $sampleEntry['template_path'] === null
                    ? null
                    : $projectRoot . '/' . $sampleEntry['template_path'];
                $category = $sampleEntry['role'];
                $categoryLabel = $roleLabels[$category];
                $variables = null;
                $templateAvailable = is_string($templateFile) && is_file($templateFile);
                $canGenerate = $sampleEntry['distribution'] === 'composer'
                    && $sampleEntry['execution_mode'] === 'odt';

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
                    $sampleEntry['id'],
                    $sampleEntry['title'],
                    $categoryLabel,
                    $sampleEntry['purpose'],
                    ...$sampleEntry['migration_targets'],
                ]));
                ?>
                <article class="sample-card" data-sample-id="<?= htmlspecialchars($sampleEntry['id'], ENT_QUOTES, 'UTF-8') ?>" data-category="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="card-main">
                        <div class="card-topline">
                            <span class="category"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="sample-id"><?= htmlspecialchars($sampleEntry['id'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <h3><?= htmlspecialchars($sampleEntry['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="description"><?= htmlspecialchars($sampleEntry['purpose'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="meta-row">
                            <span class="meta-pill"><?= $sampleEntry['status'] === 'canonical' ? 'Canonical sample' : 'Legacy migration entry' ?></span>
                            <?php if ($sampleEntry['migration_targets'] !== []): ?>
                                <span class="meta-pill">Planned: <?= htmlspecialchars(implode(', ', $sampleEntry['migration_targets']), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <span class="meta-pill <?= $templateAvailable ? 'ok' : '' ?>"><?= $templateAvailable ? '✓ Template available' : ($sampleEntry['template_path'] === null ? 'No template required' : 'Template unavailable') ?></span>
                            <?php if (is_array($variables)): ?><span class="meta-pill"><?= count($variables) ?> template entries</span><?php endif; ?>
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
                                <?php elseif ($sampleEntry['template_path'] === null): ?>
                                    <p>This sample has no ODT template; it reports semantic inspection output only.</p>
                                <?php else: ?>
                                    <p>Declared template file is unavailable: <?= htmlspecialchars($sampleEntry['template_path'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </div>
                        </details>
                        <details>
                            <summary>View PHP source</summary>
                            <div class="detail-content"><pre><code><?= htmlspecialchars((string) file_get_contents($sampleFile), ENT_QUOTES, 'UTF-8') ?></code></pre></div>
                        </details>
                    </div>
                    <div class="card-actions">
                        <?php if ($canGenerate): ?>
                            <button class="button generate-button" type="button" data-sample="<?= htmlspecialchars($sampleEntry['id'], ENT_QUOTES, 'UTF-8') ?>">Generate &amp; download ODT</button>
                        <?php elseif ($sampleEntry['execution_mode'] === 'inspection'): ?>
                            <p class="meta-pill">Inspection only · no generated ODT.</p>
                        <?php else: ?>
                            <p class="meta-pill">Repository-only sample; template is excluded from the Composer archive.</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <div class="empty-state" id="emptyState"><strong>No matching samples.</strong><br>Try another search term or select a different feature category.</div>
    </section>

    <section class="showcase-section" id="projects" aria-labelledby="projects-title">
        <div class="section-heading">
            <span class="section-kicker">Real-world usage</span>
            <h2 id="projects-title">Used for real editable document generation.</h2>
            <p>ODT Template Engine is developed alongside production document workflows rather than only as a format experiment.</p>
        </div>
        <div class="project-grid">
            <a class="project-card" href="https://www.bewerbungstools.de/" target="_blank" rel="noreferrer">
                <div class="project-icon">BT</div>
                <div><div class="project-status"><span></span> Live project</div><h3>Bewerbungstools.de</h3><p>Application tooling and document generation using ODT-based workflows.</p><span class="project-link">Visit project →</span></div>
            </a>
            <a class="project-card" href="https://www.bewerbungstools.de/lebenslauf-erstellen" target="_blank" rel="noreferrer">
                <div class="project-icon project-icon-alt">CV</div>
                <div><div class="project-status"><span></span> Product integration</div><h3>CV Generator</h3><p>A real consumer of the engine for editable CVs with structured sections, layouts, rich text, images and styles.</p><span class="project-link">Open the CV Generator →</span></div>
            </a>
        </div>
    </section>

    <section class="support-section" id="support" aria-labelledby="support-title">
        <div class="support-copy">
            <span class="section-kicker section-kicker-light">Open source · MIT licensed</span>
            <h2 id="support-title">Support further development</h2>
            <p>ODT Template Engine is free and open source. If the library saves you time or helps with your project, you can support its continued development via PayPal or Bitcoin Lightning. Thank you!</p>
        </div>
        <div class="support-actions">
            <a class="support-button support-button-github" href="<?= $githubUrl ?>" target="_blank" rel="noreferrer">
                <strong>★ Star on GitHub</strong>
            </a>
            <div class="support-button support-button-disabled" aria-label="PayPal support">
                <strong>PayPal</strong>
                <span>Support via PayPal →</span>
            </div>
            <div class="support-button support-button-lightning" aria-label="Bitcoin Lightning support">
                <strong>⚡ Bitcoin Lightning</strong>
                <span>Support via Lightning →</span>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <span>ODT Template Engine · PHP library for native OpenDocument generation</span>
        <div class="footer-links">
            <a href="impressum.php">Imprint</a>
            <a href="datenschutz.php">Privacy</a>
            <a href="<?= $githubUrl ?>" target="_blank" rel="noreferrer">GitHub repository →</a>
        </div>
    </div>
</footer>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="app.js"></script>
</body>
</html>
