/**
 * Customer Appreciation Month Raffle Draw System
 * Script: Vanilla JavaScript (No Frameworks or Libraries)
 * 
 * Responsible for client-side interactivity, DOM manipulation,
 * and future raffle animation/draw mechanics.
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('✅ Raffle Draw System: JavaScript loaded successfully.');

    // Quick verification badge / interactive check for Phase 1
    const jsStatusElement = document.getElementById('js-status-badge');
    if (jsStatusElement) {
        jsStatusElement.textContent = 'Active & Ready';
        jsStatusElement.className = 'badge badge-success';
    }

    // Ping check button event listener
    const pingButton = document.getElementById('btn-test-js');
    if (pingButton) {
        pingButton.addEventListener('click', () => {
            alert('🎉 Vanilla JavaScript is properly connected and functioning!');
        });
    }
});
