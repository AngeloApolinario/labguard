<x-guest-layout>
    <div class="min-h-screen bg-slate-900 flex flex-col justify-center items-center py-12 sm:px-6 lg:px-8 relative overflow-hidden font-sans">
        {{-- Background Glow Accent Effects --}}
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <x-authentication-card class="w-full max-w-md bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 shadow-2xl rounded-3xl p-8 relative z-10">
            <x-slot name="logo">
                <div class="flex flex-col items-center justify-center space-y-3 mb-2">
                    <div class="size-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/30 border border-white/20">
                        <x-heroicon-o-shield-check class="size-8 text-white" />
                    </div>
                    <div class="text-center">
                        <h2 class="text-2xl font-black tracking-tight text-white">LabGuard <span class="text-blue-500">2FA</span></h2>
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mt-0.5">Security Verification</p>
                    </div>
                </div>
            </x-slot>

            <div x-data="{ recovery: false }" class="mt-4">
                {{-- Helper Text --}}
                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-700/50 mb-6">
                    <div class="flex items-start space-x-3">
                        <x-heroicon-o-key class="size-5 text-blue-400 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-xs font-medium text-slate-300 leading-relaxed" x-show="! recovery">
                                {{ __('Confirm system access by entering the code from your authenticator app.') }}
                            </p>
                            <p class="text-xs font-medium text-slate-300 leading-relaxed" x-cloak x-show="recovery">
                                {{ __('Confirm system access by entering one of your emergency recovery codes.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <x-validation-errors class="mb-4" />

                <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
                    @csrf

                    {{-- Authenticator Code Input --}}
                    <div x-show="! recovery" class="space-y-2">
                        <x-label for="code" value="{{ __('Authentication Code') }}" class="text-xs font-bold uppercase tracking-wider text-slate-300" />
                        <div class="relative">
                            <x-input id="code"
                                class="block w-full px-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-white font-mono text-center tracking-[0.5em] text-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition placeholder-slate-600"
                                type="text"
                                inputmode="numeric"
                                name="code"
                                autofocus
                                x-ref="code"
                                autocomplete="one-time-code"
                                placeholder="••••••" />
                        </div>
                    </div>

                    {{-- Recovery Code Input --}}
                    <div x-cloak x-show="recovery" class="space-y-2">
                        <x-label for="recovery_code" value="{{ __('Emergency Recovery Code') }}" class="text-xs font-bold uppercase tracking-wider text-slate-300" />
                        <div class="relative">
                            <x-input id="recovery_code"
                                class="block w-full px-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-white font-mono text-center tracking-widest text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition placeholder-slate-600"
                                type="text"
                                name="recovery_code"
                                x-ref="recovery_code"
                                autocomplete="one-time-code"
                                placeholder="xxxx-xxxx-xxxx" />
                        </div>
                    </div>

                    {{-- Action Controls --}}
                    <div class="pt-2 space-y-4">
                        <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-600/30 hover:shadow-blue-500/50 transition-all duration-200 active:scale-[0.98] flex items-center justify-center space-x-2">
                            <span>{{ __('Authorize & Log In') }}</span>
                            <x-heroicon-o-arrow-right class="size-4" />
                        </button>

                        <div class="text-center">
                            <button type="button"
                                class="text-xs font-semibold text-slate-400 hover:text-blue-400 transition cursor-pointer inline-flex items-center space-x-1"
                                x-show="! recovery"
                                x-on:click="
                                    recovery = true;
                                    $nextTick(() => { $refs.recovery_code.focus() })
                                ">
                                <x-heroicon-o-arrow-path class="size-3.5 mr-1" />
                                <span>{{ __('Use an emergency recovery code instead') }}</span>
                            </button>

                            <button type="button"
                                class="text-xs font-semibold text-slate-400 hover:text-blue-400 transition cursor-pointer inline-flex items-center space-x-1"
                                x-cloak
                                x-show="recovery"
                                x-on:click="
                                    recovery = false;
                                    $nextTick(() => { $refs.code.focus() })
                                ">
                                <x-heroicon-o-device-phone-mobile class="size-3.5 mr-1" />
                                <span>{{ __('Use an authenticator app code instead') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </x-authentication-card>
    </div>
</x-guest-layout>