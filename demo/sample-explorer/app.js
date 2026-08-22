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

    updateVisibleCards();
})();
