(() => {
    const showcaseStyles = document.createElement('link');
    showcaseStyles.rel = 'stylesheet';
    showcaseStyles.href = 'showcase.css';
    document.head.appendChild(showcaseStyles);

    const cvSampleName = 'sample_21_cvProfile';
    const cvCard = document.querySelector(`[data-sample="${cvSampleName}"]`)?.closest('.sample-card');

    if (cvCard) {
        cvCard.dataset.category = 'showcases';
        cvCard.dataset.search += ' showcase cv profile resume page layout editable odt real world';

        const category = cvCard.querySelector('.category');
        const title = cvCard.querySelector('h3');
        const description = cvCard.querySelector('.description');

        if (category) {
            category.textContent = 'Showcases';
        }

        if (title) {
            title.textContent = 'CV Profile';
        }

        if (description) {
            description.textContent = 'Generate a complete two-column CV with page layout control, rich text, lists, images and editable ODT output.';
        }

        const filters = document.querySelector('.filters');
        if (filters && !filters.querySelector('[data-filter="showcases"]')) {
            const showcaseFilter = document.createElement('button');
            showcaseFilter.className = 'filter-button';
            showcaseFilter.type = 'button';
            showcaseFilter.dataset.filter = 'showcases';
            showcaseFilter.textContent = 'Showcases';
            filters.appendChild(showcaseFilter);
        }
    }

    const howItWorks = document.getElementById('how-it-works');
    if (howItWorks && cvCard) {
        const showcase = document.createElement('section');
        showcase.className = 'cv-showcase landing-section';
        showcase.id = 'cv-showcase';
        showcase.setAttribute('aria-labelledby', 'cv-showcase-title');
        showcase.innerHTML = `
            <div class="cv-showcase-copy">
                <span class="section-kicker">Real-world ODT showcase</span>
                <h2 id="cv-showcase-title">See what the engine can build.</h2>
                <p>
                    Sample 21 generates a complete two-column CV as a native, editable OpenDocument file.
                    It combines page layout control, rich text, images, native lists, reusable styles and structured PHP data in one document.
                </p>
                <div class="cv-showcase-features" aria-label="CV showcase features">
                    <span>Two-column layout</span>
                    <span>Editable ODT</span>
                    <span>Images &amp; lists</span>
                    <span>Page layout API</span>
                </div>
                <pre class="cv-showcase-code"><code>$template = new PageLayoutOdtTemplate('cv-template.odt');
$template->setPageMargins('0cm', '0.8cm', '0cm', '0cm');
$template->setElement('cv_sidebar', $sidebar);
$template->setElement('cv_content', $content);
$template->save('cv.odt');</code></pre>
                <div class="cv-showcase-actions">
                    <a class="button button-primary" href="#sample-21-showcase">Try the CV showcase</a>
                    <a class="text-link" href="https://github.com/WaltDietzney/odt-template-engine/blob/master/samples/sample_21_cvProfile.php" target="_blank" rel="noreferrer">View full PHP sample →</a>
                </div>
            </div>
            <div class="cv-document-preview" aria-label="Stylized preview of the generated CV">
                <div class="cv-preview-sidebar">
                    <strong>Max Mustermann</strong>
                    <div class="cv-preview-photo">ODT</div>
                    <small>KONTAKT</small>
                    <i></i><i></i><i class="short"></i>
                    <small>PLUSPUNKTE</small>
                    <i></i><i class="short"></i>
                    <small>SOFT SKILLS</small>
                    <i></i><i class="short"></i>
                </div>
                <div class="cv-preview-content">
                    <strong>PROFIL</strong><b></b>
                    <i></i><i></i><i class="short"></i>
                    <strong>BERUFSERFAHRUNG</strong><b></b>
                    <em>2022 – heute</em><h3>Senior Softwareentwickler</h3>
                    <i></i><i></i>
                    <em>2018 – 2022</em><h3>Softwareentwickler</h3>
                    <i></i><i class="short"></i>
                    <strong>AUSBILDUNG</strong><b></b>
                    <em>2014 – 2018</em><h3>B.Sc. Informatik</h3>
                </div>
                <span class="cv-preview-badge">Generated as .odt</span>
            </div>
        `;
        howItWorks.parentNode.insertBefore(showcase, howItWorks);

        cvCard.id = 'sample-21-showcase';
    }

    const heroActions = document.querySelector('.hero-actions');
    if (heroActions && cvCard && !heroActions.querySelector('[href="#cv-showcase"]')) {
        const showcaseLink = document.createElement('a');
        showcaseLink.className = 'button button-secondary';
        showcaseLink.href = '#cv-showcase';
        showcaseLink.textContent = 'See CV showcase';
        heroActions.appendChild(showcaseLink);
    }

    const searchInput = document.getElementById('searchInput');
    const filterButtons = Array.from(document.querySelectorAll('[data-filter]'));
    const cards = Array.from(document.querySelectorAll('.sample-card'));
    const emptyState = document.getElementById('emptyState');
    const resultCount = document.getElementById('resultCount');
    const toast = document.getElementById('toast');

    let activeFilter = 'all';

    const updateVisibleCards = () => {
        const term = (searchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;

        cards.forEach((card) => {
            const matchesSearch = term === '' || card.dataset.search.includes(term);
            const matchesFilter = activeFilter === 'all' || card.dataset.category === activeFilter;
            const visible = matchesSearch && matchesFilter;

            card.hidden = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        if (resultCount) {
            resultCount.textContent = String(visibleCount);
        }

        emptyState?.classList.toggle('is-visible', visibleCount === 0);
    };

    searchInput?.addEventListener('input', updateVisibleCards);

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.filter || 'all';
            filterButtons.forEach((candidate) => {
                candidate.classList.toggle('is-active', candidate === button);
            });
            updateVisibleCards();
        });
    });

    const showToast = (message) => {
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.add('is-visible');
        window.setTimeout(() => toast.classList.remove('is-visible'), 3500);
    };

    document.querySelectorAll('[data-sample]').forEach((button) => {
        button.addEventListener('click', async () => {
            const sample = button.dataset.sample;
            const originalLabel = button.textContent;

            button.disabled = true;
            button.textContent = 'Generating ODT…';

            try {
                const response = await fetch('generate.php?sample=' + encodeURIComponent(sample));
                const data = await response.json();

                if (!response.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Sample generation failed.');
                }

                showToast('ODT generated successfully. Download starting…');
                window.location.href = 'download.php?file=' + encodeURIComponent(data.file);
            } catch (error) {
                showToast(error instanceof Error ? error.message : 'Sample generation failed.');
            } finally {
                button.disabled = false;
                button.textContent = originalLabel;
            }
        });
    });

    const projectCards = Array.from(document.querySelectorAll('.project-card'));
    projectCards.forEach((projectCard) => {
        const title = projectCard.querySelector('h3')?.textContent?.trim();

        if (title === 'Bewerbungstools.de') {
            projectCard.href = 'https://www.bewerbungstools.de/';
        }

        if (title === 'CV Generator') {
            projectCard.href = 'https://www.bewerbungstools.de/lebenslauf-erstellen';
            const projectLink = projectCard.querySelector('.project-link');
            if (projectLink) {
                projectLink.textContent = 'Try the CV Generator →';
            }
        }
    });

    const githubButton = document.querySelector('.support-button-github');
    if (githubButton) {
        githubButton.innerHTML = '<strong>★ Star on GitHub</strong>';
    }

    const paypalPlaceholder = document.querySelector('.support-button-disabled');
    if (paypalPlaceholder) {
        const paypalLink = document.createElement('a');
        paypalLink.className = 'support-button';
        paypalLink.href = 'https://www.paypal.com/donate/?hosted_button_id=RVFJUELPFMXQW';
        paypalLink.target = '_blank';
        paypalLink.rel = 'noreferrer';
        paypalLink.setAttribute('aria-label', 'Support ODT Template Engine via PayPal');
        paypalLink.innerHTML = `
            <strong>PayPal</strong>
            <span>Support via PayPal →</span>
            <img src="assets/paypal-qr.svg" width="112" height="112" alt="QR code for PayPal support" style="padding: 6px; border-radius: 10px; background: #fff;">
        `;
        paypalPlaceholder.replaceWith(paypalLink);
    }

    const lightningPlaceholder = document.querySelector('.support-button-lightning');
    if (lightningPlaceholder) {
        const lightningUrl = 'lightning:lnurl1dp68gurn8ghj7ampd3kx2ar0veekzar0wd5xjtnrdakj7tnhv4kxctttdehhwm30d3h82unvwqhkv6t5w3jkgetvd35hqum9x5cnqq24e0d';
        const lightningLink = document.createElement('a');
        lightningLink.className = 'support-button support-button-lightning';
        lightningLink.href = lightningUrl;
        lightningLink.setAttribute('aria-label', 'Support ODT Template Engine via Bitcoin Lightning');
        lightningLink.innerHTML = `
            <strong>⚡ Bitcoin Lightning</strong>
            <span>Support via Lightning →</span>
            <img src="assets/lightning-qr.svg" width="112" height="112" alt="QR code for Bitcoin Lightning support" style="padding: 6px; border-radius: 10px; background: #fff;">
        `;
        lightningPlaceholder.replaceWith(lightningLink);
    }

    const supportCopy = document.querySelector('.support-copy p');
    if (supportCopy) {
        supportCopy.textContent = 'ODT Template Engine is free and open source. If the library saves you time or helps with your project, you can support its continued development via PayPal or Bitcoin Lightning. Thank you!';
    }

    const footerLinks = document.querySelector('.footer-links');
    if (footerLinks && !footerLinks.querySelector('[data-legal-link]')) {
        const legalLink = document.createElement('a');
        legalLink.href = 'impressum.php';
        legalLink.textContent = 'Impressum';
        legalLink.dataset.legalLink = 'true';

        const privacyLink = document.createElement('a');
        privacyLink.href = 'datenschutz.php';
        privacyLink.textContent = 'Datenschutz';
        privacyLink.dataset.legalLink = 'true';

        footerLinks.prepend(privacyLink);
        footerLinks.prepend(legalLink);
    }

    updateVisibleCards();
})();
