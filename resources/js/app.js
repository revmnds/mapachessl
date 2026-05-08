import './bootstrap';
import { wizard } from './wizard';
import { initParticles } from './particles-config';

// Expose wizard to global scope for Alpine.js
window.wizard = wizard;

// Initialize particles when DOM is ready
document.addEventListener('DOMContentLoaded', initParticles);
