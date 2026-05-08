<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('wizard.app_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js" integrity="sha384-9Ax3MmS9AClxJyd5/zafcXXjxmwFhZCdsT6HJoJjarvCaAkJlk5QDzjLJm+Wdx5F" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js" integrity="sha384-Rv68Y7adOjMMJc1/xFMcdNvXre/HF51to4GZjBALmXr7ABnVl5V4UajJwBu7zbhN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js" integrity="sha384-oHYQNeDBTZNj6KnIfJMAzcEn2OTbeMKKXFeEwU6T+pH0oS1yTIzEBaW6BXmCtvs2" crossorigin="anonymous"></script>
    <script>
        window.translations = @json(__('wizard'));
        window.messages = @json(__('messages'));
        window.__wizardSession = @json($wizardSession ?? null);
    </script>
</head>
<body class="min-h-screen no-scrollbar bg-white">
    {{-- Particles background --}}
    <div id="particles-js"></div>

    {{-- Moon background - bottom left corner --}}
    <div id="moon-bg">
        <img src="{{ asset('images/moon.png') }}" alt="">
    </div>
    <div class="min-h-screen flex flex-col px-6 py-12 relative z-10"
         x-data="wizard()"
         x-cloak
         @if($wizardSession)
         x-init="
             const s = window.__wizardSession;
             if (s && s.has_session && s.data) {
                 Object.assign(data, s.data);
                 if (s.data.is_generating || s.data.challenge_token) {
                     waitingForDns = true;
                     generating = true;
                 }
                 const st = (s.data.status === 'completed' || s.data.status === 'failed') ? 5
                     : (s.data.is_generating || s.data.challenge_token) ? 4
                     : (s.data.current_step || 1);
                 step = st;
                 visibleStep = st;
             }
         "
         @endif>

        {{-- Top spacer --}}
        <div class="flex-1"></div>

        {{-- Main content - centered --}}
        <div class="w-full max-w-md mx-auto">
            {{-- Welcome --}}
            <template x-if="visibleStep === 'welcome'">
                <form @submit.prevent="start()" class="space-y-8 text-center animate-step">
                    <div class="space-y-3">
                        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">{{ __('wizard.app_name') }}</h1>
                        <p class="text-gray-500">{{ __('wizard.tagline') }}</p>
                    </div>
                    {{-- Rate limit error --}}
                    <div x-show="errors.rate_limit" class="bg-gray-100 border border-gray-200 rounded-lg p-3 text-sm text-gray-600" x-text="errors.rate_limit"></div>
                    <div x-show="errors.server" class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600" x-text="errors.server"></div>
                    <button type="submit"
                            :disabled="loading"
                            class="w-full bg-gray-900 text-white py-3 px-6 rounded-lg font-medium
                                   hover:bg-gray-800 transition-colors cursor-pointer disabled:opacity-50">
                        <span x-show="!loading">{{ __('wizard.btn_start') }}</span>
                        <span x-show="loading">{{ __('wizard.btn_starting') }}</span>
                    </button>
                    <p class="text-xs text-gray-400">{{ __('wizard.slogan') }}</p>
                </form>
            </template>

            {{-- Step 1: Domain --}}
            <template x-if="visibleStep === 1">
                <div class="space-y-6 animate-step" x-init="$nextTick(() => $refs.domainInput.focus())">
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold text-gray-900 tracking-tight">{{ __('wizard.step1_title') }}</h2>
                        <p class="text-sm text-gray-500">{{ __('wizard.step1_subtitle') }}</p>
                    </div>
                    <form @submit.prevent="saveStep(1)" class="space-y-6">
                        <div class="space-y-3">
                            <div class="space-y-2">
                                <input type="text"
                                       x-ref="domainInput"
                                       x-model="data.domain"
                                       placeholder="{{ __('wizard.step1_placeholder') }}"
                                       autocomplete="off"
                                       class="w-full border border-gray-200 rounded-lg px-4 py-3 text-gray-900
                                              placeholder-gray-400 focus:border-gray-900 focus:outline-none transition-colors"
                                       :class="errors.domain ? 'border-gray-500' : ''">
                                <p x-show="errors.domain" x-text="errors.domain" class="text-gray-500 text-sm"></p>
                                <p x-show="errors.server" x-text="errors.server" class="text-red-500 text-sm"></p>
                            </div>
                            {{-- Wildcard toggle --}}
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" x-model="data.is_wildcard" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 rounded-full transition-colors peer-checked:bg-gray-900"></div>
                                    <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                                </div>
                                <div class="flex-1">
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">{{ __('wizard.step1_wildcard_label') }}</span>
                                    <span class="text-xs text-gray-400 block" x-show="data.is_wildcard" x-text="'*.' + (data.domain || '{{ __('wizard.step1_placeholder') }}') + ' {{ __('wizard.step1_wildcard_hint') }}'"></span>
                                </div>
                            </label>
                        </div>
                        <button type="submit"
                                :disabled="loading"
                                class="w-full bg-gray-900 text-white py-3 px-6 rounded-lg font-medium
                                       hover:bg-gray-800 transition-colors cursor-pointer disabled:opacity-50">
                            <span x-show="!loading">{{ __('wizard.btn_continue') }}</span>
                            <span x-show="loading">{{ __('wizard.btn_saving') }}</span>
                        </button>
                    </form>
                </div>
            </template>

            {{-- Step 2: Email --}}
            <template x-if="visibleStep === 2">
                <div class="space-y-6 animate-step" x-init="$nextTick(() => $refs.emailInput.focus())">
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold text-gray-900 tracking-tight">{{ __('wizard.step2_title') }}</h2>
                        <p class="text-sm text-gray-500">{{ __('wizard.step2_subtitle') }}</p>
                    </div>
                    <form @submit.prevent="saveStep(2)" class="space-y-6">
                        <div class="space-y-2">
                            <input type="email"
                                   x-ref="emailInput"
                                   x-model="data.email"
                                   placeholder="{{ __('wizard.step2_placeholder') }}"
                                   autocomplete="email"
                                   class="w-full border border-gray-200 rounded-lg px-4 py-3 text-gray-900
                                          placeholder-gray-400 focus:border-gray-900 focus:outline-none transition-colors"
                                   :class="errors.email ? 'border-gray-500' : ''">
                            <p x-show="errors.email" x-text="errors.email" class="text-gray-500 text-sm"></p>
                            <p x-show="errors.server" x-text="errors.server" class="text-red-500 text-sm"></p>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="goBack()"
                                    :disabled="loading"
                                    class="flex-1 border border-gray-200 text-gray-600 py-3 px-6 rounded-lg font-medium
                                           hover:bg-gray-50 transition-colors cursor-pointer disabled:opacity-50">
                                {{ __('wizard.btn_back') }}
                            </button>
                            <button type="submit"
                                    :disabled="loading"
                                    class="flex-1 bg-gray-900 text-white py-3 px-6 rounded-lg font-medium
                                           hover:bg-gray-800 transition-colors cursor-pointer disabled:opacity-50">
                                <span x-show="!loading">{{ __('wizard.btn_continue') }}</span>
                                <span x-show="loading">{{ __('wizard.btn_saving') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>

            {{-- Step 3: Method --}}
            <template x-if="visibleStep === 3">
                <div class="space-y-6 animate-step" x-init="$nextTick(() => { if (data.is_wildcard) { data.challenge_type = 'dns'; } $refs.dnsOption?.focus() || $refs.httpOption?.focus(); })">
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold text-gray-900 tracking-tight">{{ __('wizard.step3_title') }}</h2>
                        <p class="text-sm text-gray-500" x-text="data.is_wildcard ? '{{ __('wizard.step3_subtitle_wildcard') }}' : '{{ __('wizard.step3_subtitle_normal') }}'"></p>
                    </div>
                    <form @submit.prevent="saveStep(3)" class="space-y-6">
                        <div class="space-y-3" role="radiogroup" @keydown.arrow-down.prevent="if (!data.is_wildcard) { data.challenge_type = 'dns'; $refs.dnsOption.focus(); }" @keydown.arrow-up.prevent="if (!data.is_wildcard) { data.challenge_type = 'http'; $refs.httpOption.focus(); }">
                            {{-- HTTP option - hidden for wildcard --}}
                            <label class="block cursor-pointer" :class="data.is_wildcard ? 'hidden' : ''">
                                <input type="radio" x-model="data.challenge_type" value="http" x-ref="httpOption" class="sr-only peer" :disabled="data.is_wildcard">
                                <div class="border border-gray-200 rounded-lg p-4 transition-all
                                            peer-checked:border-gray-900 peer-checked:bg-gray-50
                                            peer-focus:ring-2 peer-focus:ring-gray-900 peer-focus:ring-offset-2
                                            hover:border-gray-300">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ __('wizard.step3_http_title') }}</p>
                                            <p class="text-sm text-gray-500">{{ __('wizard.step3_http_desc') }}</p>
                                        </div>
                                        <div class="w-4 h-4 rounded-full border-2 transition-all"
                                             :class="data.challenge_type === 'http' ? 'border-gray-900 bg-gray-900' : 'border-gray-300'">
                                        </div>
                                    </div>
                                </div>
                            </label>
                            {{-- DNS option --}}
                            <label class="block cursor-pointer">
                                <input type="radio" x-model="data.challenge_type" value="dns" x-ref="dnsOption" class="sr-only peer">
                                <div class="border rounded-lg p-4 transition-all"
                                     :class="data.is_wildcard ? 'border-gray-900 bg-gray-50' : (data.challenge_type === 'dns' ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:border-gray-300')">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ __('wizard.step3_dns_title') }}</p>
                                            <p class="text-sm text-gray-500">{{ __('wizard.step3_dns_desc') }}</p>
                                        </div>
                                        <div class="w-4 h-4 rounded-full border-2 transition-all"
                                             :class="data.challenge_type === 'dns' || data.is_wildcard ? 'border-gray-900 bg-gray-900' : 'border-gray-300'">
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <p x-show="errors.challenge_type" x-text="errors.challenge_type" class="text-gray-500 text-sm"></p>
                        <p x-show="errors.server" x-text="errors.server" class="text-red-500 text-sm"></p>
                        <div class="flex gap-3">
                            <button type="button" @click="goBack()"
                                    :disabled="loading"
                                    class="flex-1 border border-gray-200 text-gray-600 py-3 px-6 rounded-lg font-medium
                                           hover:bg-gray-50 transition-colors cursor-pointer disabled:opacity-50">
                                {{ __('wizard.btn_back') }}
                            </button>
                            <button type="submit"
                                    :disabled="loading"
                                    class="flex-1 bg-gray-900 text-white py-3 px-6 rounded-lg font-medium
                                           hover:bg-gray-800 transition-colors cursor-pointer disabled:opacity-50">
                                <span x-show="!loading">{{ __('wizard.btn_continue') }}</span>
                                <span x-show="loading">{{ __('wizard.btn_saving') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>

            {{-- Step 4: Verification --}}
            <template x-if="visibleStep === 4">
                <div class="space-y-6 animate-step" x-init="$nextTick(() => $refs.generateBtn?.focus())">
                    {{-- Before generating: show instructions --}}
                    <template x-if="!waitingForDns && !data.challenge_token">
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <h2 class="text-xl font-semibold text-gray-900 tracking-tight">{{ __('wizard.step4_title') }}</h2>
                                <p class="text-sm text-gray-500">{{ __('wizard.step4_subtitle') }}</p>
                            </div>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-600">
                                <p class="font-medium text-gray-700">{{ __('wizard.step4_how_title') }}</p>
                                <ol class="mt-2 space-y-1 list-decimal list-inside">
                                    <li>{{ __('wizard.step4_how_step1') }}</li>
                                    <li x-text="data.challenge_type === 'dns' ? '{{ __('wizard.step4_how_step2_dns') }}' : '{{ __('wizard.step4_how_step2_http') }}'"></li>
                                    <li>{{ __('wizard.step4_how_step3') }}</li>
                                    <li>{{ __('wizard.step4_how_step4') }}</li>
                                </ol>
                            </div>
                        </div>
                    </template>

                    {{-- While waiting: show tokens and status --}}
                    <template x-if="waitingForDns || data.challenge_token">
                        <div class="space-y-2">
                            <h2 class="text-xl font-semibold text-gray-900 tracking-tight">{{ __('wizard.step4_config_title') }}</h2>
                            <p class="text-sm text-gray-500" x-text="data.challenge_type === 'http' ? '{{ __('wizard.step4_config_http_subtitle') }}' : '{{ __('wizard.step4_config_dns_subtitle') }}'"></p>
                        </div>
                    </template>

                    {{-- Waiting for tokens indicator --}}
                    <template x-if="waitingForDns && !data.challenge_token">
                        <div class="flex items-center justify-center py-8">
                            <div class="text-center space-y-3">
                                <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-sm text-gray-500 transition-opacity duration-300" x-text="currentStatusPhrase || '{{ __('wizard.step4_getting_tokens') }}'"></p>
                                <p class="text-xs text-gray-400">
                                    <strong>{{ __('wizard.no_refresh_warning') }}</strong> {{ __('wizard.no_refresh_hint') }}
                                </p>
                            </div>
                        </div>
                    </template>

                    {{-- HTTP Instructions --}}
                    <template x-if="data.challenge_type === 'http' && data.challenge_token">
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-xs text-gray-400 uppercase tracking-wider">{{ __('wizard.label_path') }}</label>
                                <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm transition-colors duration-300"
                                     :class="{ 'bg-gray-200': copiedField === 'http-path' }">
                                    <input type="text" readonly value=".well-known/acme-challenge/" @focus="$el.select()" class="flex-1 text-gray-900 bg-transparent outline-none p-0 w-full cursor-text" />
                                    <button type="button" @click="copy('.well-known/acme-challenge/', 'http-path')"
                                            class="text-gray-400 hover:text-gray-600 cursor-pointer transition-all duration-200"
                                            :class="{ 'text-gray-900 scale-110': copiedField === 'http-path' }">
                                        <svg x-show="copiedField !== 'http-path'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        <svg x-show="copiedField === 'http-path'" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-gray-400 uppercase tracking-wider">{{ __('wizard.label_file') }}</label>
                                <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm transition-colors duration-300"
                                     :class="{ 'bg-gray-200': copiedField === 'http-filename' }">
                                    <input type="text" readonly :value="data.challenge_filename" @focus="$el.select()" class="flex-1 text-gray-900 bg-transparent outline-none p-0 w-full cursor-text" />
                                    <button type="button" @click="copy(data.challenge_filename, 'http-filename')"
                                            class="text-gray-400 hover:text-gray-600 cursor-pointer transition-all duration-200"
                                            :class="{ 'text-gray-900 scale-110': copiedField === 'http-filename' }">
                                        <svg x-show="copiedField !== 'http-filename'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        <svg x-show="copiedField === 'http-filename'" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-gray-400 uppercase tracking-wider">{{ __('wizard.label_content') }}</label>
                                <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm transition-colors duration-300"
                                     :class="{ 'bg-gray-200': copiedField === 'http-content' }">
                                    <input type="text" readonly :value="data.challenge_token" @focus="$el.select()" class="flex-1 text-gray-900 bg-transparent outline-none p-0 w-full cursor-text" />
                                    <button type="button" @click="copy(data.challenge_token, 'http-content')"
                                            class="text-gray-400 hover:text-gray-600 cursor-pointer transition-all duration-200"
                                            :class="{ 'text-gray-900 scale-110': copiedField === 'http-content' }">
                                        <svg x-show="copiedField !== 'http-content'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        <svg x-show="copiedField === 'http-content'" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- DNS Instructions --}}
                    <template x-if="data.challenge_type === 'dns' && data.challenge_token">
                        <div class="space-y-4">
                            {{-- Wildcard notice — always show for wildcard --}}
                            <template x-if="data.is_wildcard">
                                <div class="bg-gray-100 border border-gray-300 rounded-lg p-3 text-sm text-gray-600">
                                    <p class="font-medium text-gray-700">{{ __('wizard.wildcard_title') }}</p>
                                    <template x-if="getDnsTokens().length < 2">
                                        <p class="mt-1">{{ __('wizard.wildcard_sequential') }}</p>
                                    </template>
                                    <template x-if="getDnsTokens().length >= 2">
                                        <p class="mt-1">{{ __('wizard.wildcard_notice') }} <strong x-text="getDnsTokens().length"></strong> {{ __('wizard.wildcard_records') }}</p>
                                    </template>
                                </div>
                            </template>

                            <div class="space-y-1.5">
                                <label class="text-xs text-gray-400 uppercase tracking-wider">{{ __('wizard.label_host') }}</label>
                                <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm transition-colors duration-300"
                                     :class="{ 'bg-gray-200': copiedField === 'dns-host' }">
                                    <input type="text" readonly :value="'_acme-challenge.' + data.domain" @focus="$el.select()" class="flex-1 text-gray-900 bg-transparent outline-none p-0 w-full cursor-text" />
                                    <button type="button" @click="copy('_acme-challenge.' + data.domain, 'dns-host')"
                                            class="text-gray-400 hover:text-gray-600 cursor-pointer transition-all duration-200"
                                            :class="{ 'text-gray-900 scale-110': copiedField === 'dns-host' }">
                                        <svg x-show="copiedField !== 'dns-host'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        <svg x-show="copiedField === 'dns-host'" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <label class="text-xs text-gray-400 uppercase tracking-wider">{{ __('wizard.label_type') }}</label>
                                    <div class="bg-gray-50 rounded-lg p-3 font-mono text-sm text-gray-900">TXT</div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs text-gray-400 uppercase tracking-wider">{{ __('wizard.label_ttl') }}</label>
                                    <div class="bg-gray-50 rounded-lg p-3 font-mono text-sm text-gray-900">300</div>
                                </div>
                            </div>

                            {{-- Single token (non-wildcard) --}}
                            <template x-if="getDnsTokens().length === 1">
                                <div class="space-y-1.5">
                                    <label class="text-xs text-gray-400 uppercase tracking-wider">{{ __('wizard.label_value') }}</label>
                                    <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm transition-colors duration-300"
                                         :class="{ 'bg-gray-200': copiedField === 'dns-value-0' }">
                                        <input type="text" readonly :value="getDnsTokens()[0]" @focus="$el.select()" class="flex-1 text-gray-900 bg-transparent outline-none p-0 w-full cursor-text" />
                                        <button type="button" @click="copy(getDnsTokens()[0], 'dns-value-0')"
                                                class="text-gray-400 hover:text-gray-600 cursor-pointer transition-all duration-200"
                                                :class="{ 'text-gray-900 scale-110': copiedField === 'dns-value-0' }">
                                            <svg x-show="copiedField !== 'dns-value-0'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                            <svg x-show="copiedField === 'dns-value-0'" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Multiple tokens (wildcard) --}}
                            <template x-if="getDnsTokens().length > 1">
                                <div class="space-y-3">
                                    <template x-for="(token, index) in getDnsTokens()" :key="index">
                                        <div class="space-y-1.5">
                                            <label class="text-xs text-gray-400 uppercase tracking-wider" x-text="'{{ __('wizard.label_value') }} ' + (index + 1)"></label>
                                            <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm transition-colors duration-300"
                                                 :class="{ 'bg-gray-200': copiedField === 'dns-value-' + index }">
                                                <input type="text" readonly :value="token" @focus="$el.select()" class="flex-1 text-gray-900 bg-transparent outline-none p-0 w-full cursor-text" />
                                                <button type="button" @click="copy(token, 'dns-value-' + index)"
                                                        class="text-gray-400 hover:text-gray-600 cursor-pointer transition-all duration-200"
                                                        :class="{ 'text-gray-900 scale-110': copiedField === 'dns-value-' + index }">
                                                    <svg x-show="copiedField !== 'dns-value-' + index" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                    <svg x-show="copiedField === 'dns-value-' + index" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <div x-show="errors.verification" class="rounded-lg p-3 text-sm bg-gray-800 border border-gray-700 text-gray-200">
                        <p class="font-medium text-white">{{ __('wizard.verification_pending_title') }}</p>
                        <p class="mt-1" x-text="Array.isArray(errors.verification) ? errors.verification[0] : errors.verification"></p>
                    </div>

                    {{-- Rate limit error --}}
                    <div x-show="errors.rate_limit" class="rounded-lg p-3 text-sm bg-gray-100 border border-gray-200 text-gray-600">
                        <p class="font-medium text-gray-700">{{ __('messages.rate_limit.title') }}</p>
                        <p class="mt-1" x-text="errors.rate_limit"></p>
                    </div>

                    {{-- Status indicator while waiting for DNS verification --}}
                    <template x-if="waitingForDns && data.challenge_token">
                        <div class="rounded-lg p-4 text-sm bg-gray-100 border border-gray-300 text-gray-600">
                            <div class="flex items-center gap-3">
                                <svg class="animate-spin h-5 w-5 text-gray-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-700">
                                        <span x-text="displayStatusPhrase || '{{ __('wizard.verification_waiting_title') }}'"></span><span class="typewriter-cursor">|</span>
                                    </p>
                                    <p class="mt-1" x-text="data.challenge_type === 'dns' ? '{{ __('wizard.verification_waiting_dns') }}' : '{{ __('wizard.verification_waiting_http') }}'"></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="flex gap-3">
                        {{-- Botón Atrás - visible cuando NO está esperando --}}
                        <button type="button" @click="goBack()"
                                :disabled="loading"
                                x-show="!waitingForDns"
                                class="flex-1 border border-gray-200 text-gray-600 py-3 px-6 rounded-lg font-medium
                                       hover:bg-gray-50 transition-colors cursor-pointer disabled:opacity-50">
                            {{ __('wizard.btn_back') }}
                        </button>
                        {{-- Botón Cancelar - visible cuando SÍ está esperando --}}
                        <button type="button" @click="cancelGeneration()"
                                x-show="waitingForDns"
                                class="flex-1 border border-gray-200 text-gray-600 py-3 px-6 rounded-lg font-medium
                                       hover:bg-gray-50 transition-colors cursor-pointer">
                            {{ __('wizard.btn_cancel') }}
                        </button>
                        <button type="button" @click="generate()"
                                :disabled="loading || generating"
                                x-show="!waitingForDns"
                                x-ref="generateBtn"
                                class="flex-1 bg-gray-900 text-white py-3 px-6 rounded-lg font-medium
                                       hover:bg-gray-800 transition-colors cursor-pointer disabled:opacity-50">
                            {{ __('wizard.btn_generate') }}
                        </button>
                    </div>
                </div>
            </template>

            {{-- Step 5: Result - Success --}}
            <template x-if="visibleStep === 5 && data.status === 'completed'">
                <div class="space-y-6 animate-step" x-init="$nextTick(() => { celebrate(); certTab = 'fullchain'; })">
                    <div class="text-center space-y-2">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 tracking-tight">{{ __('wizard.step5_success_title') }}</h2>
                        <p class="text-sm text-gray-500" x-text="data.display_domain || data.domain"></p>
                    </div>

                    {{-- Info summary --}}
                    <div class="space-y-2 text-sm">
                        <template x-if="data.expires_at">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">{{ __('wizard.step5_expires_label') }}</span>
                                <span class="text-gray-900" x-text="data.expires_at"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Certificate tabs --}}
                    <div class="space-y-3">
                        <div class="flex gap-1 bg-gray-100 p-1 rounded-lg">
                            <button type="button" @click="certTab = 'fullchain'"
                                    :class="certTab === 'fullchain' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                    class="flex-1 py-1.5 px-2 text-xs font-medium rounded-md transition-all cursor-pointer">
                                {{ __('wizard.cert_tab_fullchain') }}
                            </button>
                            <button type="button" @click="certTab = 'certificate'"
                                    :class="certTab === 'certificate' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                    class="flex-1 py-1.5 px-2 text-xs font-medium rounded-md transition-all cursor-pointer">
                                {{ __('wizard.cert_tab_certificate') }}
                            </button>
                            <button type="button" @click="certTab = 'private_key'"
                                    :class="certTab === 'private_key' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                    class="flex-1 py-1.5 px-2 text-xs font-medium rounded-md transition-all cursor-pointer">
                                {{ __('wizard.cert_tab_private_key') }}
                            </button>
                            <button type="button" @click="certTab = 'chain'" x-show="data.chain_pem"
                                    :class="certTab === 'chain' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                    class="flex-1 py-1.5 px-2 text-xs font-medium rounded-md transition-all cursor-pointer">
                                {{ __('wizard.cert_tab_chain') }}
                            </button>
                        </div>

                        {{-- Private key warning --}}
                        <div x-show="certTab === 'private_key'" class="flex items-center gap-2 text-xs text-gray-600 bg-gray-100 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>{{ __('wizard.cert_warning_private_key') }}</span>
                        </div>

                        {{-- Certificate content --}}
                        <div class="relative">
                            <pre class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs font-mono text-gray-700 overflow-x-auto max-h-40 whitespace-pre-wrap break-all select-all cursor-text" tabindex="0" x-text="getCertContent()"></pre>
                            <button type="button" @click="copyCert()"
                                    class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 cursor-pointer transition-all duration-200"
                                    :class="{ 'text-gray-900 scale-110': copiedField === 'cert-' + certTab }">
                                <svg x-show="copiedField !== 'cert-' + certTab" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <svg x-show="copiedField === 'cert-' + certTab" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div class="flex gap-3">
                        <a :href="'/download' + (data.session_token ? '?s=' + data.session_token : '')"
                           class="flex-1 bg-gray-900 text-white py-3 px-6 rounded-lg font-medium text-center
                                  hover:bg-gray-800 transition-colors">
                            {{ __('wizard.btn_download_zip') }}
                        </a>
                    </div>

                    <button @click="startFresh()"
                            class="w-full text-center text-gray-500 hover:text-gray-700 text-sm cursor-pointer">
                        {{ __('wizard.btn_new_certificate') }}
                    </button>
                </div>
            </template>

            {{-- Step 5: Result - Error --}}
            <template x-if="visibleStep === 5 && data.status === 'failed'">
                <div class="space-y-6 animate-step">
                    <div class="text-center space-y-2">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 tracking-tight">{{ __('wizard.step5_error_title') }}</h2>
                        <p class="text-sm text-gray-500">{{ __('wizard.step5_error_subtitle') }}</p>
                    </div>
                    <div x-show="data.error_message" class="bg-gray-100 border border-gray-200 rounded-lg p-3 text-sm text-gray-600" x-text="data.error_message"></div>
                    <div class="flex gap-3">
                        <button @click="retryGeneration()"
                                class="flex-1 bg-gray-900 text-white py-3 px-6 rounded-lg font-medium
                                       hover:bg-gray-800 transition-colors cursor-pointer">
                            {{ __('wizard.btn_retry') }}
                        </button>
                        <button @click="startFresh()"
                                class="flex-1 border border-gray-200 text-gray-600 py-3 px-6 rounded-lg font-medium
                                       hover:bg-gray-50 transition-colors cursor-pointer">
                            {{ __('wizard.btn_new_certificate') }}
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Bottom spacer with step indicators --}}
        <div class="flex-1 flex flex-col justify-end">
            <div id="step-indicators" class="flex justify-center pt-12 hidden">
                <div class="flex flex-col items-center gap-3">
                    <button @click="startFresh()"
                            class="text-sm text-gray-500 hover:text-gray-700 transition-colors cursor-pointer">
                        {{ __('wizard.btn_new_certificate') }}
                    </button>
                    <div class="flex items-center gap-2">
                        <div class="step-dot w-1.5 h-1.5 rounded-full bg-gray-300 transition-all duration-300"></div>
                        <div class="step-dot w-1.5 h-1.5 rounded-full bg-gray-300 transition-all duration-300"></div>
                        <div class="step-dot w-1.5 h-1.5 rounded-full bg-gray-300 transition-all duration-300"></div>
                        <div class="step-dot w-1.5 h-1.5 rounded-full bg-gray-300 transition-all duration-300"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Top-right bar: Language selector + Buy Me a Coffee --}}
    <div id="top-bar" x-data="{ open: false }" @click.away="open = false">
        {{-- Language selector --}}
        <div class="top-bar-lang">
            <button @click="open = !open" class="top-bar-lang-btn">
                <span>{{ strtoupper(app()->getLocale()) }}</span>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="top-bar-lang-dropdown">
                @foreach(['es' => 'ES', 'en' => 'EN', 'nl' => 'NL'] as $locale => $label)
                    @if(app()->getLocale() !== $locale)
                        <form method="POST" action="{{ route('locale.switch') }}">
                            @csrf
                            <input type="hidden" name="locale" value="{{ $locale }}">
                            <button type="submit" class="top-bar-lang-option">
                                {{ $label }}
                            </button>
                        </form>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Buy Me a Coffee --}}
        <a id="bmc-button" href="https://www.buymeacoffee.com/mapache" target="_blank" rel="noopener noreferrer">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M20.216 6.415l-.132-.666c-.119-.598-.388-1.163-1.001-1.379-.197-.069-.42-.098-.57-.241-.152-.143-.196-.366-.231-.572-.065-.378-.125-.756-.192-1.133-.057-.325-.102-.69-.25-.987-.195-.4-.597-.634-.996-.788a5.723 5.723 0 00-.626-.194c-1-.263-2.05-.36-3.077-.416a25.834 25.834 0 00-3.7.062c-.915.083-1.88.184-2.75.5-.318.116-.646.256-.888.501-.297.302-.393.77-.177 1.146.154.267.415.456.692.58.36.162.737.284 1.123.366 1.075.238 2.189.331 3.287.37 1.218.05 2.437.01 3.65-.118.299-.033.598-.073.896-.119.352-.054.578-.513.474-.834-.124-.383-.457-.531-.834-.473-.466.074-.96.108-1.382.146-1.177.08-2.358.082-3.536.006a22.228 22.228 0 01-1.157-.107c-.086-.01-.18-.025-.258-.036-.243-.036-.484-.08-.724-.13-.111-.027-.111-.185 0-.212h.005c.277-.06.557-.108.838-.147h.002c.131-.009.263-.032.394-.048a25.076 25.076 0 013.426-.12c.674.019 1.347.067 2.017.144l.228.031c.267.04.533.088.798.145.392.085.895.113 1.07.542.055.137.08.288.111.431l.319 1.484a.237.237 0 01-.199.284h-.003c-.037.006-.075.01-.112.015a36.704 36.704 0 01-4.743.295 37.059 37.059 0 01-4.699-.304c-.14-.017-.293-.042-.417-.06-.326-.048-.649-.108-.973-.161-.393-.065-.768-.032-1.123.161-.29.16-.527.404-.675.701-.154.316-.199.66-.267 1-.069.34-.176.707-.135 1.056.087.753.613 1.365 1.37 1.502a39.69 39.69 0 0011.343.376.483.483 0 01.535.53l-.071.697-1.018 9.907c-.041.41-.047.832-.125 1.237-.122.637-.553 1.028-1.182 1.171-.577.131-1.165.2-1.756.205-.656.004-1.31-.025-1.966-.022-.699.004-1.556-.06-2.095-.58-.475-.458-.54-1.174-.605-1.793l-.731-7.013-.322-3.094c-.037-.351-.286-.695-.678-.678-.336.015-.718.3-.678.679l.228 2.185.949 9.112c.147 1.344 1.174 2.068 2.446 2.272.742.12 1.503.144 2.257.156.966.016 1.942.053 2.892-.122 1.408-.258 2.465-1.198 2.616-2.657.34-3.332.683-6.663 1.024-9.995l.215-2.087a.484.484 0 01.39-.426c.402-.078.787-.212 1.074-.518.455-.488.546-1.124.385-1.766zm-1.478.772c-.145.137-.363.201-.578.233-2.416.359-4.866.54-7.308.46-1.748-.06-3.477-.254-5.207-.498-.17-.024-.353-.055-.47-.18-.22-.236-.111-.71-.054-.995.052-.26.152-.609.463-.646.484-.057 1.046.148 1.526.22.577.088 1.156.159 1.737.212 2.48.226 5.002.19 7.472-.14.45-.06.899-.13 1.345-.21.399-.072.84-.206 1.08.206.166.281.188.657.162.974a.544.544 0 01-.169.364zm-6.159 3.9c-.862.37-1.84.788-3.109.788a5.884 5.884 0 01-1.569-.217l.877 9.004c.065.78.717 1.38 1.5 1.38 0 0 1.243.065 1.658.065.447 0 1.786-.065 1.786-.065.783 0 1.434-.6 1.499-1.38l.94-9.95a3.996 3.996 0 00-1.322-.238c-.826 0-1.491.284-2.26.613z"/>
            </svg>
            <span>Buy me a coffee</span>
        </a>
    </div>
    <style>
        #top-bar {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .top-bar-lang {
            position: relative;
        }
        .top-bar-lang-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 8px 12px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .top-bar-lang-btn:hover {
            color: #374151;
            border-color: #d1d5db;
            background: #f9fafb;
        }
        .top-bar-lang-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 4px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            overflow: hidden;
            min-width: 60px;
        }
        .top-bar-lang-option {
            display: block;
            width: 100%;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 500;
            color: #6b7280;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            transition: all 0.15s;
        }
        .top-bar-lang-option:hover {
            color: #374151;
            background: #f9fafb;
        }
        #bmc-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        #bmc-button:hover {
            color: #374151;
            border-color: #d1d5db;
            background: #f9fafb;
        }
        #bmc-button svg {
            width: 16px;
            height: 16px;
        }
    </style>

</body>
</html>
