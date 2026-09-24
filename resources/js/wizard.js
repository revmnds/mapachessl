/**
 * MapacheSSL Wizard Component
 * Alpine.js component for SSL certificate generation wizard
 */

export function wizard() {
    return {
        step: null,
        visibleStep: null,
        transitioning: false,
        loading: false,
        errors: {},
        data: {
            domain: '',
            is_wildcard: false,
            challenge_type: 'http',
            challenge_token: '',
            challenge_filename: '',
            status: '',
            error_message: '',
            expires_at: ''
        },

        async init() {
            const embedded = window.__wizardSession;

            this.listenForReconnect();

            if (embedded && embedded.has_session && embedded.data) {
                // Session found — restore correct step synchronously (no flash)
                this.data = { ...this.data, ...embedded.data };
                this.restoreStep(embedded.data);
                return;
            }

            // No session — show welcome immediately
            this.step = 'welcome';
            this.visibleStep = 'welcome';

            this.clearLegacyUrlToken();
        },

        // Old links carried the session token as ?s=; the session now lives only in the cookie
        clearLegacyUrlToken() {
            const url = new URL(window.location);
            if (!url.searchParams.has('s')) return;
            url.searchParams.delete('s');
            window.history.replaceState({}, '', url);
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        /**
         * POST to the API. If the Laravel session expired (tab left open for hours),
         * fetch a fresh CSRF token and retry once instead of failing silently.
         */
        async post(url, body = {}) {
            const send = () => fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify(body),
            });

            let response = await send();
            if (response.status === 419) {
                const fresh = await fetch('/api/wizard/csrf', { headers: { 'Accept': 'application/json' } });
                const { token } = await fresh.json();
                document.querySelector('meta[name="csrf-token"]').content = token;
                response = await send();
            }
            return response;
        },

        errorFromResponse(response, result) {
            if (response.status === 401) {
                return { server: window.translations?.error_session_expired || 'Session expired. Please start over.' };
            }
            if (response.status === 429) {
                return result.errors || { rate_limit: window.messages?.rate_limit?.generic || 'Rate limit exceeded. Try again later.' };
            }
            return result.errors || { server: result.error || window.messages?.errors?.server || 'Error' };
        },

        async fetchFullSessionData() {
            try {
                const response = await fetch('/api/wizard/status');
                const result = await response.json();
                if (result.has_session && result.data) {
                    this.data = { ...this.data, ...result.data };
                }
            } catch (e) {
                // PEM data unavailable for copy — download still works via server
            }
        },

        restoreStep(data) {
            this.clearLegacyUrlToken();

            if (data.status === 'completed') {
                this.goToStep(4, true);
                this.fetchFullSessionData();
                return;
            }
            if (data.status === 'failed') {
                this.goToStep(4, true);
                return;
            }

            // in_progress with active generation — resume polling at step 3
            if (data.is_generating || data.challenge_token) {
                this.waitingForDns = true;
                this.generating = true;
                this.goToStep(3, true);
                this.startStatusPhrases(data.challenge_token ? 'verification' : 'token');
                this.startTokenPolling();
                return;
            }

            // Otherwise go to the saved current step
            this.goToStep(data.current_step || 1, true);
        },

        goToStep(newStep, instant) {
            if (this.transitioning) return;

            if (instant) {
                this.step = newStep;
                this.visibleStep = newStep;
                this.updateStepIndicators(newStep);
                return;
            }

            this.transitioning = true;
            this.visibleStep = null;

            setTimeout(() => {
                this.step = newStep;
                this.visibleStep = newStep;
                this.transitioning = false;
                this.updateStepIndicators(newStep);
            }, 50);
        },

        updateStepIndicators(step) {
            const container = document.getElementById('step-indicators');
            const dots = container.querySelectorAll('.step-dot');

            if (typeof step === 'number' && step >= 1 && step <= 3) {
                container.classList.remove('hidden');
                dots.forEach((dot, index) => {
                    const dotStep = index + 1;
                    dot.classList.remove('bg-gray-900', 'bg-gray-400', 'bg-gray-300');

                    if (dotStep === step) {
                        dot.classList.add('bg-gray-900');
                    } else if (dotStep < step) {
                        dot.classList.add('bg-gray-400');
                    } else {
                        dot.classList.add('bg-gray-300');
                    }
                });
            } else {
                container.classList.add('hidden');
            }
        },

        pendingDiscard: null,

        async start() {
            this.loading = true;
            this.errors = {};

            try {
                await this.pendingDiscard;
                const response = await this.post('/api/wizard/start');
                const result = await response.json().catch(() => ({}));

                if (response.ok && result.success) {
                    this.goToStep(1);
                } else {
                    this.errors = this.errorFromResponse(response, result);
                }
            } catch (e) {
                this.errors = { server: window.translations?.error_connection_failed || 'Connection error.' };
            }

            this.loading = false;
        },

        async startFresh() {
            this.errors = {};
            this.waitingForDns = false;
            this.generating = false;
            this.stopTokenPolling();

            this.goToStep('welcome');
            this.resetData();

            // Drops the current request (a running job notices and stops) and clears the cookie.
            // start() waits for it so a late cookie deletion can't wipe the new session.
            this.pendingDiscard = this.post('/api/wizard/discard').catch(() => {});
        },

        resetData() {
            this.data = {
                domain: '',
                is_wildcard: false,
                challenge_type: 'http',
                challenge_token: '',
                challenge_filename: '',
                status: '',
                error_message: '',
                expires_at: ''
            };
        },

        async saveStep(stepNum) {
            this.loading = true;
            this.errors = {};

            const payload = stepNum === 1
                ? { domain: this.data.domain, is_wildcard: this.data.is_wildcard }
                : { challenge_type: this.data.challenge_type };

            try {
                const response = await this.post(`/api/wizard/step/${stepNum}`, payload);
                const result = await response.json().catch(() => ({}));

                if (response.ok && result.success) {
                    if (result.data) {
                        this.data = { ...this.data, ...result.data };
                    }
                    this.goToStep(stepNum + 1);
                } else {
                    this.errors = this.errorFromResponse(response, result);
                }
            } catch (e) {
                this.errors = { server: window.translations?.error_connection_failed || 'Connection error.' };
            }

            this.loading = false;
        },

        pollingTimer: null,
        polling: false,
        reconnecting: false,
        waitingForDns: false,
        generating: false,

        // Rotating status phrases
        statusPhraseIndex: 0,
        statusPhraseInterval: null,
        statusPhrases: [],
        verificationStartTime: null,

        getTokenPhrases() {
            return [
                window.translations?.status_contacting_acme || 'Contacting Let\'s Encrypt...',
                window.translations?.status_requesting_challenge || 'Requesting challenge tokens...',
                window.translations?.status_preparing_validation || 'Preparing domain validation...',
                window.translations?.status_generating_keys || 'Generating cryptographic keys...',
                window.translations?.status_securing_channel || 'Securing communication channel...',
                window.translations?.status_registering_domain || 'Registering your domain...',
                window.translations?.status_almost_ready || 'Almost ready...',
            ];
        },

        getVerificationPhrases() {
            const t = window.translations || {};
            return [
                t.status_still_checking || 'Still checking...',
                t.status_patience_virtue || 'Patience is a virtue...',
                t.status_still_on_it || 'Still on it...',
                t.status_dns_takes_time || 'DNS can be slow, hang tight...',
                t.status_keep_waiting || 'We keep validating...',
                t.status_no_worries || 'No worries, this is normal...',
                t.status_doing_fine || 'Everything\'s fine, just waiting...',
                t.status_still_here || 'Still here, still working...',
                t.status_any_moment || 'Could be any moment now...',
                t.status_propagation_slow || 'Propagation can take a while...',
                t.status_grab_coffee || 'Good time for a coffee...',
                t.status_not_stuck || 'Not stuck, just waiting...',
                t.status_servers_thinking || 'The servers are thinking...',
                t.status_almost_there || 'Almost there...',
                t.status_stretch_legs || 'Meanwhile, have you stretched your legs?',
                t.status_bits_rhythm || 'Bits travel at their own pace...',
                t.status_take_a_breath || 'Take a breather, we\'re watching...',
                t.status_internet_magic || 'The internet is doing its magic...',
                t.status_watching_plants || 'Like watching a plant grow, but faster...',
                t.status_relax || 'Relax, we\'re on it...',
                t.status_coffee_good || 'A little coffee never hurt anyone...',
                t.status_tech_needs_time || 'Technology needs its time too...',
                t.status_breathe_deep || 'Breathe deep, almost there...',
                t.status_closer_than_think || 'Closer than you think...',
                t.status_not_easy || 'If it were easy, it wouldn\'t be fun...',
                t.status_meditating || 'No, it didn\'t freeze. It\'s just meditating...',
                t.status_internet_elves || 'The internet elves are hard at work...',
                t.status_no_f5 || 'Don\'t hit F5, you\'ll make us dizzy...',
                t.status_stare_slower || 'Staring at the screen makes it slower. It\'s science.',
                t.status_not_mining || 'We promise we\'re not mining bitcoin...',
                t.status_reboot_patience || 'Have you tried turning your patience off and on again?',
                t.status_dns_speed || 'Verifying at DNS speed... which isn\'t much...',
                t.status_dns_fast_joke || 'If DNS were fast, you wouldn\'t need this message...',
                t.status_convincing_servers || 'We\'re convincing the DNS servers...',
                t.status_hamsters || 'Our hamsters are pedaling as fast as they can...',
                t.status_plot_twist || 'Plot twist: DNS really does take this long...',
                t.status_spoiler || 'Spoiler: it\'s going to work. Just give it time...',
                t.status_not_a_bug || 'This is not a bug, it\'s a patience feature...',
                t.status_dns_meaning || 'Fun fact: DNS stands for Domain Name System...',
                t.status_reggaeton || 'Random fact: an SSL cert has more lines than a pop song...',
                t.status_tacos || 'You could technically go grab lunch and come back...',
                t.status_optimism || 'Nothing yet, but optimism is free...',
                t.status_mindfulness || 'Think of this as a mindfulness exercise...',
                t.status_waiting_room || 'We\'re in the internet\'s waiting room...',
                t.status_no_tracking || 'DNS packets are on the way. No tracking number though...',
                t.status_inhale_security || 'Breathe. Inhale security, exhale HTTP...',
                t.status_good_things_take || 'Good things take time. Your cert will be great...',
                t.status_billions || 'Did you know Let\'s Encrypt has issued billions of certificates?',
                t.status_polite_cert || 'Your certificate is in line. It\'s polite and waits its turn...',
                t.status_dns_rules || 'We\'d love to go faster, but DNS makes the rules...',
                t.status_who_waits || 'Good things come to those who encrypt...',
                t.status_like_chrome || 'Processing... like your computer when you open Chrome...',
                t.status_all_night || 'We\'ve got all night. Well, 30 minutes...',
                t.status_deliberate || 'It\'s not slow, it\'s... deliberate...',
                t.status_thorough || 'Let\'s Encrypt is verifying. They\'re very thorough...',
                t.status_https_1994 || 'Fun fact: HTTPS was invented by Netscape in 1994...',
                t.status_each_second || 'Every second of waiting is another second of security...',
                t.status_electrons || 'Electrons are circling the globe to verify your domain...',
                t.status_faith || 'Nothing yet, but we haven\'t lost faith...',
                t.status_future_you || 'Future you with HTTPS will thank you...',
            ];
        },

        getDoubtPhrases() {
            const t = window.translations || {};
            return [
                t.status_doubt_check_dns || 'Are you sure you configured the DNS correctly?',
                t.status_doubt_recheck || 'Maybe double-check, just in case?',
                t.status_doubt_two_records || 'Hey... did you add both TXT records?',
                t.status_doubt_spaces || 'Did you copy them right? Sometimes an extra space...',
                t.status_doubt_panel || 'Might be worth checking your DNS panel...',
                t.status_doubt_together || 'Still nothing... shall we check together?',
                t.status_doubt_longer || 'Hmm, this is taking longer than usual...',
                t.status_doubt_ttl || 'Could your DNS TTL be set too high?',
                t.status_doubt_configured || 'No pressure, but... did you configure them yet?',
                t.status_doubt_provider || 'Maybe your DNS provider fell asleep...',
                t.status_doubt_glance || 'Just saying... a quick look at your DNS panel couldn\'t hurt...',
                t.status_doubt_a_while || 'Not to nag, but it\'s been a while...',
                t.status_doubt_correct_domain || 'Did you add the records to the right domain?',
                t.status_doubt_trailing_space || 'Check for spaces at the start or end of the TXT value...',
                t.status_doubt_slow_provider || 'Is your DNS provider one of the slow ones? Honest question...',
            ];
        },

        shuffleArray(arr) {
            const shuffled = [...arr];
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
            return shuffled;
        },

        get currentStatusPhrase() {
            if (!this.statusPhrases.length) return '';
            return this.statusPhrases[this.statusPhraseIndex % this.statusPhrases.length];
        },

        // Typewriter effect state
        typewriterText: '',
        typewriterTimeout: null,

        get displayStatusPhrase() {
            return this.typewriterText;
        },

        typewritePhrase(text) {
            if (this.typewriterTimeout) {
                clearTimeout(this.typewriterTimeout);
            }
            this.typewriterText = '';
            let i = 0;
            const type = () => {
                if (i <= text.length) {
                    this.typewriterText = text.slice(0, i);
                    i++;
                    this.typewriterTimeout = setTimeout(type, 35);
                }
            };
            type();
        },

        startStatusPhrases(type) {
            this.stopStatusPhrases();

            if (type === 'token') {
                this.statusPhrases = this.getTokenPhrases();
                this.statusPhraseIndex = 0;
                this.statusUseTypewriter = false;
                this.statusPhraseInterval = setInterval(() => {
                    this.statusPhraseIndex++;
                }, 3000);
                return;
            }

            // Verification mode: fixed first message, then shuffled phrases
            this.statusUseTypewriter = true;
            this.verificationStartTime = Date.now();

            const normalPhrases = this.shuffleArray(this.getVerificationPhrases());
            const doubtPhrases = this.shuffleArray(this.getDoubtPhrases());

            // Fixed first phrase
            const firstPhrase = window.translations?.verification_waiting_title || 'Waiting for verification...';
            this.statusPhrases = [firstPhrase, ...normalPhrases];
            this._doubtPhrases = doubtPhrases;
            this._doubtIndex = 0;
            this._normalCycleCount = 0;
            this.statusPhraseIndex = 0;

            this.typewritePhrase(this.currentStatusPhrase);

            this.statusPhraseInterval = setInterval(() => {
                this.statusPhraseIndex++;
                const elapsed = (Date.now() - this.verificationStartTime) / 1000;

                // After 10 minutes, mix in doubt phrases every 3rd message
                if (elapsed > 600 && this.statusPhraseIndex > 1 && this.statusPhraseIndex % 3 === 0) {
                    if (this._doubtIndex < this._doubtPhrases.length) {
                        this.typewritePhrase(this._doubtPhrases[this._doubtIndex]);
                        this._doubtIndex++;
                        return;
                    }
                }

                // When we've gone through all normal phrases, reshuffle
                if (this.statusPhraseIndex >= this.statusPhrases.length) {
                    this._normalCycleCount++;
                    const reshuffled = this.shuffleArray(this.getVerificationPhrases());
                    this.statusPhrases = reshuffled;
                    this.statusPhraseIndex = 0;
                    // Also reshuffle doubt phrases if exhausted
                    if (this._doubtIndex >= this._doubtPhrases.length) {
                        this._doubtPhrases = this.shuffleArray(this.getDoubtPhrases());
                        this._doubtIndex = 0;
                    }
                }

                this.typewritePhrase(this.currentStatusPhrase);
            }, 8000);
        },

        stopStatusPhrases() {
            if (this.statusPhraseInterval) {
                clearInterval(this.statusPhraseInterval);
                this.statusPhraseInterval = null;
            }
            if (this.typewriterTimeout) {
                clearTimeout(this.typewriterTimeout);
                this.typewriterTimeout = null;
            }
            this.statusPhrases = [];
            this.statusPhraseIndex = 0;
            this.typewriterText = '';
            this.statusUseTypewriter = false;
            this.verificationStartTime = null;
        },

        async generate() {
            if (this.generating) return;
            this.generating = true;
            this.loading = true;
            this.errors = {};
            this.waitingForDns = true;
            this.startStatusPhrases('token');

            try {
                const response = await this.post('/api/wizard/generate');
                const result = await response.json().catch(() => ({}));

                if (response.ok && result.success) {
                    if (result.data) {
                        this.data = { ...this.data, ...result.data };
                    }
                    this.startTokenPolling();
                } else if (response.status === 409) {
                    // A generation for this request is already running (other tab, or polling had stopped): follow it
                    this.startTokenPolling();
                } else {
                    this.errors = this.errorFromResponse(response, result);
                    this.waitingForDns = false;
                    this.generating = false;
                    this.stopStatusPhrases();
                }
            } catch (e) {
                // The request may have reached the server; polling tells us whether a job is running
                this.startTokenPolling();
            }

            this.loading = false;
        },

        pollDelay: 2000,

        startTokenPolling() {
            this.stopTokenPolling();
            this.polling = true;
            this.pollDelay = 2000;
            this.pollOnce();
        },

        scheduleNextPoll() {
            if (!this.polling) return;
            this.pollingTimer = setTimeout(() => this.pollOnce(), this.pollDelay);
        },

        // Network drops (laptop sleep, wifi switch) never end the wait: back off and keep trying
        pollFailed() {
            this.reconnecting = true;
            this.pollDelay = Math.min(this.pollDelay * 2, 15000);
            this.scheduleNextPoll();
        },

        async pollOnce() {
            clearTimeout(this.pollingTimer);
            if (!this.polling) return;

            let response;
            try {
                response = await fetch('/api/wizard/poll-tokens', { headers: { 'Accept': 'application/json' } });
            } catch (e) {
                return this.pollFailed();
            }

            if (!this.polling) return;

            if (response.status === 401) {
                this.finishWaiting();
                this.errors = { server: window.translations?.error_session_expired || 'Session expired. Please start over.' };
                return;
            }

            if (!response.ok) {
                return this.pollFailed();
            }

            const result = await response.json().catch(() => null);
            if (!result) return this.pollFailed();

            this.reconnecting = false;
            this.pollDelay = 2000;

            if (result.success && result.data) {
                // Switch to verification phrases when tokens arrive
                if (result.data.challenge_token && !this.data.challenge_token) {
                    this.startStatusPhrases('verification');
                }

                this.data = { ...this.data, ...result.data };

                if (result.data.status === 'completed' || result.data.status === 'failed') {
                    this.finishWaiting();
                    this.goToStep(4);
                    return;
                }

                // No job behind this request (e.g. the generate call never reached the server)
                if (!result.data.is_generating && !result.data.challenge_token) {
                    this.finishWaiting();
                    this.errors = { server: window.translations?.error_connection_failed || 'Connection error.' };
                    return;
                }
            }

            this.scheduleNextPoll();
        },

        finishWaiting() {
            this.stopTokenPolling();
            this.stopStatusPhrases();
            this.waitingForDns = false;
            this.generating = false;
        },

        stopTokenPolling() {
            this.polling = false;
            this.reconnecting = false;
            clearTimeout(this.pollingTimer);
            this.pollingTimer = null;
        },

        // Poll right away when the tab regains focus or the network comes back
        listenForReconnect() {
            const wake = () => {
                if (this.polling && document.visibilityState === 'visible') this.pollOnce();
            };
            window.addEventListener('online', wake);
            document.addEventListener('visibilitychange', wake);
        },

        goBack() {
            if (this.step > 1) {
                this.errors = {};
                this.goToStep(this.step - 1);
            }
        },

        retryGeneration() {
            this.generating = false;
            this.data.challenge_token = '';
            this.data.challenge_filename = '';
            this.data.status = '';
            this.data.error_message = '';
            this.data.certificate_pem = '';
            this.data.private_key_pem = '';
            this.data.chain_pem = '';
            this.data.fullchain_pem = '';
            this.data.expires_at = '';
            this.errors = {};
            this.goToStep(3);
        },

        cancelGeneration() {
            this.loading = false;
            this.startFresh();
        },

        copiedField: null,
        certTab: 'fullchain', // Tab activa para vista de certificados

        copy(text, fieldId = null) {
            navigator.clipboard.writeText(text);
            this.copiedField = fieldId || text;
            this.showCopyToast();

            setTimeout(() => {
                this.copiedField = null;
            }, 1500);
        },

        // Obtener contenido del certificado según la tab activa
        getCertContent() {
            switch (this.certTab) {
                case 'fullchain':
                    return this.data.fullchain_pem || '';
                case 'certificate':
                    return this.data.certificate_pem || '';
                case 'private_key':
                    return this.data.private_key_pem || '';
                case 'chain':
                    return this.data.chain_pem || '';
                default:
                    return '';
            }
        },

        // Copiar el certificado de la tab activa
        copyCert() {
            const content = this.getCertContent();
            if (content) {
                this.copy(content, 'cert-' + this.certTab);
            }
        },

        showCopyToast() {
            let toast = document.getElementById('copy-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'copy-toast';
                toast.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-lg transform transition-all duration-200 opacity-0 translate-y-2 z-50';
                toast.textContent = window.translations?.toast_copied || 'Copied';
                document.body.appendChild(toast);
            }

            requestAnimationFrame(() => {
                toast.classList.remove('opacity-0', 'translate-y-2');
                toast.classList.add('opacity-100', 'translate-y-0');
            });

            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-2');
            }, 1200);
        },

        celebrate() {
            if (typeof confetti === 'undefined') return;

            const duration = 3000;
            const animationEnd = Date.now() + duration;
            const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

            const randomInRange = (min, max) => Math.random() * (max - min) + min;

            const interval = setInterval(() => {
                const timeLeft = animationEnd - Date.now();

                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }

                const particleCount = 50 * (timeLeft / duration);

                confetti({
                    ...defaults,
                    particleCount,
                    origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
                    colors: ['#10b981', '#34d399', '#6ee7b7', '#fbbf24', '#f59e0b'],
                });
                confetti({
                    ...defaults,
                    particleCount,
                    origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
                    colors: ['#10b981', '#34d399', '#6ee7b7', '#fbbf24', '#f59e0b'],
                });
            }, 250);
        },

        inLine() {
            return Number.isInteger(this.data.queue_position);
        },

        queueText() {
            const t = window.translations || {};
            const n = this.data.queue_position;
            if (n === 0) return t.queue_next || "You're next in line.";
            if (n === 1) return t.queue_ahead_one || 'There is 1 person ahead of you in line.';
            return (t.queue_ahead_many || 'There are :count people ahead of you in line.').replace(':count', n);
        },

        getDnsTokens() {
            if (!this.data.challenge_token) return [];
            return this.data.challenge_token.split('\n').filter(t => t.trim());
        }
    };
}
