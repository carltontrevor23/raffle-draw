/**
 * Customer Appreciation Month Raffle Draw System
 * Client-side validation and raffle draw presentation.
 */

document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.getElementById('navbar');
    const mobileMenuButton = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const mobileMenuClose = document.getElementById('mobileMenuClose');

    if (navbar) {
        const updateNavbar = () => navbar.classList.toggle('scrolled', window.scrollY > 50);
        updateNavbar();
        window.addEventListener('scroll', updateNavbar, { passive: true });
    }

    if (mobileMenuButton && mobileMenu && mobileMenuOverlay && mobileMenuClose) {
        const mobileMenuLinks = mobileMenu.querySelectorAll('a');
        const setMobileMenuOpen = (isOpen) => {
            mobileMenu.classList.toggle('open', isOpen);
            mobileMenuOverlay.classList.toggle('open', isOpen);
            mobileMenuButton.classList.toggle('active', isOpen);
            mobileMenuButton.setAttribute('aria-expanded', String(isOpen));
            mobileMenu.setAttribute('aria-hidden', String(!isOpen));
            document.body.classList.toggle('menu-open', isOpen);
        };

        mobileMenuButton.addEventListener('click', () => setMobileMenuOpen(true));
        mobileMenuClose.addEventListener('click', () => {
            setMobileMenuOpen(false);
            mobileMenuButton.focus();
        });
        mobileMenuOverlay.addEventListener('click', () => setMobileMenuOpen(false));
        mobileMenuLinks.forEach((link) => {
            link.addEventListener('click', () => setMobileMenuOpen(false));
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && mobileMenu.classList.contains('open')) {
                setMobileMenuOpen(false);
                mobileMenuButton.focus();
            }
        });
    }

    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', (event) => {
            const target = document.querySelector(anchor.getAttribute('href'));
            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    const jsStatusElement = document.getElementById('js-status-badge');
    if (jsStatusElement) {
        jsStatusElement.textContent = 'Active & Ready';
        jsStatusElement.className = 'badge badge-success';
    }

    const pingButton = document.getElementById('btn-test-js');
    if (pingButton) {
        pingButton.addEventListener('click', () => {
            alert('Vanilla JavaScript is connected and working.');
        });
    }

    const registrationForm = document.getElementById('raffle-registration-form');
    if (registrationForm) {
        registrationForm.addEventListener('submit', (event) => {
            const fields = [
                {
                    input: document.getElementById('name'),
                    error: document.getElementById('error-name'),
                    valid: (value) => value.length >= 2
                },
                {
                    input: document.getElementById('phone'),
                    error: document.getElementById('error-phone'),
                    valid: (value) => value.length >= 7
                },
                {
                    input: document.getElementById('email'),
                    error: document.getElementById('error-email'),
                    valid: (value, input) => value !== '' && input.validity.valid
                }
            ];
            let firstInvalidInput = null;

            fields.forEach(({ input, error, valid }) => {
                const value = input.value.trim();
                const fieldIsValid = valid(value, input);
                error.classList.toggle('visible', !fieldIsValid);
                input.setAttribute('aria-invalid', String(!fieldIsValid));

                if (!fieldIsValid && !firstInvalidInput) {
                    firstInvalidInput = input;
                }
            });

            if (firstInvalidInput) {
                event.preventDefault();
                firstInvalidInput.focus();
            }
        });
    }

    const drawButton = document.getElementById('btn-draw-winner');
    const drawAnimation = document.getElementById('draw-animation');
    if (!drawButton || !drawAnimation) {
        return;
    }

    const drawResult = document.getElementById('draw-winner-result');
    const drawError = document.getElementById('draw-error');
    const progressFill = document.getElementById('draw-progress-fill');
    const progressBar = progressFill.parentElement;
    const animationMessage = document.getElementById('draw-animation-message');
    const animationName = document.getElementById('draw-animation-name');
    const animationBadge = document.getElementById('draw-animation-badge');
    const animationIcon = document.getElementById('draw-animation-icon');
    const numberFormat = new Intl.NumberFormat();

    const setText = (id, value) => {
        document.getElementById(id).textContent = value;
    };

    const appendTableCell = (row, value, bold) => {
        const cell = document.createElement('td');
        const text = document.createElement(bold ? 'strong' : 'span');
        text.textContent = value;
        cell.appendChild(text);
        row.appendChild(cell);
    };

    const addWinnerToTable = (winner) => {
        const tableBody = document.getElementById('winners-table-body');
        const emptyState = tableBody.querySelector('.empty-state');
        if (emptyState) {
            emptyState.closest('tr').remove();
        }

        const row = document.createElement('tr');
        row.className = 'new-winner-row';
        appendTableCell(row, `#${winner.winner_id}`, true);

        const ticketCell = document.createElement('td');
        const ticketBadge = document.createElement('span');
        ticketBadge.className = 'badge badge-info';
        ticketBadge.style.fontFamily = 'monospace';
        ticketBadge.textContent = winner.ticket_code;
        ticketCell.appendChild(ticketBadge);
        row.appendChild(ticketCell);

        appendTableCell(row, winner.name, true);
        appendTableCell(row, winner.phone, false);
        appendTableCell(row, winner.email, false);
        appendTableCell(row, winner.draw_date, false);
        tableBody.prepend(row);
    };

    const pause = (duration) => new Promise((resolve) => setTimeout(resolve, duration));

    drawButton.addEventListener('click', async () => {
        drawButton.disabled = true;
        drawButton.textContent = 'DRAW IN PROGRESS...';
        drawResult.style.display = 'none';
        drawError.style.display = 'none';
        drawAnimation.style.display = 'block';
        progressFill.style.width = '0%';
        progressBar.setAttribute('aria-valuenow', '0');
        animationMessage.textContent = 'Contacting the server to select an eligible entry.';
        animationName.textContent = 'Selecting winner...';
        animationBadge.textContent = 'Drawing in progress';
        animationIcon.style.animation = '';

        try {
            const response = await fetch('draw_api.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            let payload;
            try {
                payload = await response.json();
            } catch {
                throw new Error('The server returned an unreadable response. Please try again.');
            }

            if (!response.ok || !payload || typeof payload !== 'object' || !payload.success || !payload.winner || !payload.stats) {
                const message = payload && typeof payload.message === 'string'
                    ? payload.message
                    : 'The winner could not be selected.';
                throw new Error(message);
            }

            animationMessage.textContent = 'The server selected a valid entry. Revealing the result...';
            requestAnimationFrame(() => {
                progressFill.style.width = '100%';
                progressBar.setAttribute('aria-valuenow', '100');
            });
            await pause(1400);

            drawAnimation.style.display = 'none';
            drawResult.style.display = 'block';
            setText('draw-winner-ticket', payload.winner.ticket_code);
            setText('draw-winner-name', payload.winner.name);
            setText(
                'draw-winner-details',
                `${payload.winner.phone} | ${payload.winner.email} | ${payload.winner.draw_date}`
            );
            addWinnerToTable(payload.winner);

            setText('stat-total-entries', numberFormat.format(payload.stats.total));
            setText('stat-eligible-entries', numberFormat.format(payload.stats.eligible));
            setText('stat-total-winners', numberFormat.format(payload.stats.winners));
            setText('stat-hero-eligible', numberFormat.format(payload.stats.eligible));
            setText('draw-eligible-count', numberFormat.format(payload.stats.eligible));
            setText('recorded-winner-count', `${numberFormat.format(payload.stats.winners)} Recorded Winner(s)`);

            const poolStatus = document.getElementById('draw-pool-status');
            const heroPoolStatus = document.getElementById('hero-draw-pool-status');
            if (payload.stats.eligible > 0) {
                poolStatus.textContent = `Ready (${payload.stats.eligible})`;
                poolStatus.className = 'badge badge-success';
                heroPoolStatus.textContent = 'Ready';
                drawButton.textContent = 'DRAW ANOTHER WINNER';
                drawButton.disabled = false;
            } else {
                poolStatus.textContent = 'Empty Pool';
                poolStatus.className = 'badge badge-danger';
                heroPoolStatus.textContent = 'Pool empty';
                drawButton.textContent = 'DRAW COMPLETE';
            }
        } catch (error) {
            drawAnimation.style.display = 'none';
            drawError.textContent = error instanceof Error
                ? error.message
                : 'The draw could not be completed. Please try again.';
            drawError.style.display = 'flex';
            drawButton.textContent = '✨ DRAW WINNER';
            drawButton.disabled = false;
        }
    });
});
