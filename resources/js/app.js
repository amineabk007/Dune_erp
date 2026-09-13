import './bootstrap';
import * as bootstrap from 'bootstrap';
import { initSearchableSelects } from './searchable-select';

window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', initSearchableSelects);

// Registers the service worker that lets Chrome/Edge offer "Install app" —
// required for the app to open in its own window without browser chrome
// when added to a tablet's home screen or a PC's desktop/taskbar.
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js');
}
