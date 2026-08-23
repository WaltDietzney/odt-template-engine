(() => {
    const showcaseStyles = document.createElement('link');
    showcaseStyles.rel = 'stylesheet';
    showcaseStyles.href = 'showcase.css';
    document.head.appendChild(showcaseStyles);

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

    updateVisibleCards();
})();
