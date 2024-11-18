// File: C:\xampp\htdocs\AcadMeter\public\assets\js\toast.js

class Toast {
    constructor() {
        this.container = document.getElementById('toast-container');
    }

    show(message, type = 'success', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span>${message}</span>
            <button class="toast-close" aria-label="Close">&times;</button>
        `;

        this.container.appendChild(toast);

        // Trigger reflow to enable transition
        toast.offsetHeight;

        toast.classList.add('show');

        const closeButton = toast.querySelector('.toast-close');
        closeButton.addEventListener('click', () => this.close(toast));

        if (duration > 0) {
        }}}