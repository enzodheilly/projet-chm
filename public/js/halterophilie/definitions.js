/* Lightweight interactivity: accordion, search, modal, example video injection */
document.addEventListener('DOMContentLoaded', function () {

    // Accordion toggle for each card
    document.querySelectorAll('.def-card').forEach(card => {
        const btn = card.querySelector('.chevron');
        const body = card.querySelector('.card-body');

        // keyboard accessible: toggle on Enter/Space when card focused
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
        });

        btn.addEventListener('click', toggle);

        function toggle() {
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', String(!expanded));
            if (expanded) {
                body.hidden = true;
            } else {
                body.hidden = false;
                // smooth scroll into view for smaller screens
                setTimeout(() => { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 80);
            }
        }
    });

    // Live search filter
    const search = document.getElementById('search-def');
    search.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('#definitions-list .def-card').forEach(card => {
            const terms = (card.dataset.term || '').toLowerCase();
            const title = card.querySelector('h3').innerText.toLowerCase();
            if (!q || title.includes(q) || terms.includes(q)) {
                card.style.display = '';
                card.style.opacity = '1';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Modal: inject an iframe-only on demand (no autoplay)
    const modal = document.getElementById('video-modal');
    const videoWrap = modal.querySelector('.video-wrap');
    const openVideoBtn = document.getElementById('open-video');
    const closeBtns = modal.querySelectorAll('.modal-close, .modal-backdrop');

    openVideoBtn.addEventListener('click', openModal);
    closeBtns.forEach(n => n.addEventListener('click', closeModal));
    modal.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    function openModal() {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        // inject iframe (example video) — remplace l'URL par ta vidéo club
        if (!videoWrap.innerHTML.trim()) {
            const iframe = document.createElement('iframe');
            iframe.width = '100%';
            iframe.height = '480';
            iframe.src = 'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0';
            iframe.title = 'Démonstration arraché';
            iframe.frameBorder = '0';
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;
            videoWrap.appendChild(iframe);
        }
        // focus trap - focus close button
        modal.querySelector('.modal-close').focus();
    }
    function closeModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }

    // Buttons that open examples (data-video)
    document.querySelectorAll('.card-body .btn[data-video]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const url = btn.dataset.video;
            if (!url) return;
            // replace iframe src with url (simple handling: transform youtube link to embed)
            let embed = url;
            if (url.includes('youtube.com/watch')) {
                const id = url.split('v=')[1];
                embed = 'https://www.youtube.com/embed/' + id.split('&')[0] + '?rel=0';
            }
            // set iframe src and open modal
            const iframe = document.createElement('iframe');
            iframe.width = '100%';
            iframe.height = '480';
            iframe.src = embed;
            iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;
            videoWrap.innerHTML = '';
            videoWrap.appendChild(iframe);
            openModal();
        });
    });

});
