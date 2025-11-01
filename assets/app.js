import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import '@hotwired/turbo';

// Import styles
import './styles/app.css';

// Turbo configuration (will be available after Turbo loads)
if (typeof Turbo !== 'undefined') {
    Turbo.session.drive = true;
}

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
