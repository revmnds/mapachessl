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

        // Strips what people paste from the address bar: scheme, path, port, trailing dot, case
        normalizeDomain() {
            this.data.domain = (this.data.domain || '')
                .trim()
                .toLowerCase()
                .replace(/^[a-z]+:\/\//, '')
                .replace(/[/?#:].*$/, '')
                .replace(/\.$/, '');
        },

        // A wildcard on www.example.com yields *.www.example.com, which is almost never what's wanted
        get wwwSuggestion() {
            const domain = (this.data.domain || '').trim().toLowerCase();
            if (!this.data.is_wildcard || !domain.startsWith('www.')) return '';
            const bare = domain.slice(4);
            return bare.includes('.') ? bare : '';
        },

        get coverage() {
            const domain = (this.data.domain || '').trim().toLowerCase();
            if (!domain) return [];
            return this.data.is_wildcard ? [domain, '*.' + domain] : [domain];
        },

        applyWwwSuggestion() {
            this.data.domain = this.wwwSuggestion;
            this.$refs.domainInput?.focus();
        },

        async saveStep(stepNum) {
            if (stepNum === 1) this.normalizeDomain();
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
            const common = [
                t.status_still_checking || 'Still checking...',
                t.status_not_stuck || 'Not stuck, just waiting.',
                t.status_tab_open || 'You can leave this tab open and come back later.',
                t.status_grab_coffee || 'Good time for a coffee.',
                t.status_tacos || 'You could technically go grab tacos and be back in time.',
                t.status_who_waits || 'Good things come to those who encrypt.',
                t.status_spoiler || 'Spoiler: it\'s going to work.',
                t.status_stare_slower || 'Staring at the screen makes it slower. It\'s science.',
                t.status_polite_cert || 'Your certificate is waiting in line. Very polite.',
                t.status_deliberate || 'It\'s not slow, it\'s thorough.',
                t.status_faith || 'Nothing yet, but we haven\'t lost faith.',
                t.status_future_you || 'Future you, with HTTPS, says thanks.',
            ];

            if (this.data.challenge_type !== 'dns') {
                return [
                    ...common,
                    t.status_http_fetching || 'Let\'s Encrypt is fetching the file from your server.',
                    t.status_http_vantage || 'Let\'s Encrypt checks your server from several places around the world.',
                ];
            }

            return [
                ...common,
                t.status_dns_propagation || 'DNS can take a few minutes to update.',
                t.status_dns_vantage || 'Let\'s Encrypt checks your DNS from several places around the world. They all have to agree.',
                t.status_no_tracking || 'The TXT records are hopping from server to server. No tracking number.',
                t.status_dns_rules || 'We\'d love to go faster, but DNS makes the rules.',
                t.status_plot_twist || 'Plot twist: this is how long it normally takes.',
                t.status_dns_1983 || 'DNS dates back to 1983. Sometimes it shows.',
            ];
        },

        // Shown after 10 minutes: concrete things to check for the chosen challenge
        getDoubtPhrases() {
            const t = window.translations || {};

            if (this.data.challenge_type !== 'dns') {
                return [
                    t.status_doubt_http_url || 'This is taking longer than usual. Open the file URL in your browser and check that it shows the content.',
                    t.status_doubt_http_port || 'The file has to be reachable on port 80. Check that your firewall isn\'t blocking it.',
                    t.status_doubt_http_extension || 'Check that no extension, like .txt, was added to the file name.',
                    t.status_doubt_http_folder || 'Still nothing. Check that the file is inside .well-known/acme-challenge.',
                ];
            }

            const phrases = [
                t.status_doubt_dns_saved || 'This is taking longer than usual. Check that the TXT record is saved in your DNS panel.',
                t.status_doubt_dns_name || 'Some panels append the domain on their own. If you entered the full name, try just _acme-challenge.',
                t.status_doubt_dns_value || 'Check that the value has no extra spaces or quotes.',
                t.status_doubt_dns_provider || 'If you use Cloudflare or another external DNS, the record goes there, not where you bought the domain.',
                t.status_doubt_dns_recheck || 'Still nothing. Worth another look at your DNS panel.',
            ];
            if (this.data.is_wildcard) {
                phrases.push(t.status_doubt_dns_two_records || 'Remember: it\'s two TXT records with the same name.');
            }
            return phrases;
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
