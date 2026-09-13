import './bootstrap';
import * as bootstrap from 'bootstrap';
import { initSearchableSelects } from './searchable-select';

window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', initSearchableSelects);
