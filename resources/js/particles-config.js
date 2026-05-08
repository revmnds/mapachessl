/**
 * Particles.js Configuration
 * Twinkling stars effect for MapacheSSL
 */

export function initParticles() {
    if (typeof particlesJS === 'undefined') {
        return;
    }

    particlesJS('particles-js', {
        particles: {
            number: {
                value: 60,
                density: {
                    enable: true,
                    value_area: 1000
                }
            },
            color: {
                value: ['#374151', '#4b5563', '#1f2937', '#6b7280']
            },
            shape: {
                type: 'circle'
            },
            opacity: {
                value: 0.8,
                random: true,
                anim: {
                    enable: true,
                    speed: 0.4,
                    opacity_min: 0.2,
                    sync: false
                }
            },
            size: {
                value: 2.5,
                random: true,
                anim: {
                    enable: true,
                    speed: 0.8,
                    size_min: 0.8,
                    sync: false
                }
            },
            line_linked: {
                enable: false
            },
            move: {
                enable: true,
                speed: 0.1,
                direction: 'none',
                random: true,
                straight: false,
                out_mode: 'out',
                bounce: false,
                attract: {
                    enable: false
                }
            }
        },
        interactivity: {
            detect_on: 'canvas',
            events: {
                onhover: {
                    enable: false
                },
                onclick: {
                    enable: false
                },
                resize: true
            }
        },
        retina_detect: true
    });
}
