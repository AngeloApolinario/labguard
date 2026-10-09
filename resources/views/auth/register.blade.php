<x-guest-layout>

    @if ($errors->any())
    @php
    session()->now('toast', [
    'type' => 'danger',
    'title' => 'Registration Error',
    'message' => $errors->first()
    ]);
    @endphp
    @endif

    <x-toast />

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-white p-6">

        <!-- Background Effects -->
        <div class="pointer-events-none absolute inset-0 opacity-20">

            <div class="absolute right-[-10%] top-[-10%] h-[40%] w-[40%] rounded-full bg-[#D4AF37] blur-[120px]"></div>

            <div class="absolute bottom-[-10%] left-[-10%] h-[30%] w-[30%] rounded-full bg-indigo-600 blur-[100px]"></div>

        </div>


        <!-- Main Container -->
        <div class="relative w-full max-w-[500px]">

            <!-- Header -->
            <div class="mb-8 text-center">

                <div class="mb-4 inline-block rounded-2xl border border-black/10 bg-black/5 p-3 shadow-2xl">

                    <x-authentication-card-logo
                        class="h-14 w-14 shadow-[0_0_20px_rgba(212,175,55,0.3)]" />

                </div>

                <h1 class="text-3xl font-black uppercase tracking-tighter text-black">
                    Lab<span class="text-[#D4AF37]">Guard</span>
                </h1>

                <p class="mt-2 text-[10px] font-bold uppercase tracking-[0.4em] text-slate-500">
                    Personnel Registration Portal
                </p>

            </div>


            <!-- Registration Card -->
            <div class="relative overflow-hidden rounded-3xl border border-black/10 bg-white/95 p-8 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] backdrop-blur-xl">

                <!-- Gold Top Line -->
                <div class="absolute left-0 top-0 h-[2px] w-full bg-gradient-to-r from-transparent via-[#D4AF37] to-transparent"></div>


                <form method="POST" action="{{ route('register') }}" class="space-y-5">

                    @csrf


                    <!-- Full Name -->
                    <div class="space-y-1">

                        <label
                            for="name"
                            class="ml-1 text-[10px] font-black uppercase tracking-widest text-[#D4AF37]">
                            Full Name
                        </label>

                        <input
                            id="name"
                            class="block w-full rounded-xl border-black/10 bg-white/5 px-5 py-3.5 text-sm text-black transition-all placeholder:text-slate-600 focus:border-[#D4AF37] focus:ring-0"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="e.g. Juan Dela Cruz"
                            required
                            autofocus
                            autocomplete="name"
                            pattern="[a-zA-ZñÑ\s.\-']+"
                            title="Full name must only contain letters, spaces, periods, hyphens, and apostrophes."
                            oninput="this.value = this.value.replace(/[^a-zA-ZñÑ\s.\-']/g, '')" />

                        @error('name')
                        <p class="mt-1 text-[10px] font-bold text-rose-500">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>


                    <!-- Email -->
                    <div class="space-y-1">

                        <label
                            for="email"
                            class="ml-1 text-[10px] font-black uppercase tracking-widest text-[#D4AF37]">
                            System Email
                        </label>

                        <input
                            id="email"
                            class="block w-full rounded-xl border-black/10 bg-white/5 px-5 py-3.5 text-sm text-black transition-all placeholder:text-slate-600 focus:border-[#D4AF37] focus:ring-0"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Email Address"
                            required
                            autocomplete="username" />

                        @error('email')
                        <p class="mt-1 text-[10px] font-bold text-rose-500">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>


                    <!-- Student ID + Contact -->
                    <div class="grid grid-cols-2 gap-4">

                        <!-- Student ID -->
                        <div class="space-y-1">

                            <label
                                for="student_number"
                                class="ml-1 text-[10px] font-black uppercase tracking-widest text-[#D4AF37]">
                                Student ID
                            </label>

                            <input
                                id="student_number"
                                class="block w-full rounded-xl border-black/10 bg-white/5 px-5 py-3.5 text-sm text-black transition-all placeholder:text-slate-600 focus:border-[#D4AF37] focus:ring-0"
                                type="text"
                                name="student_number"
                                value="{{ old('student_number') }}"
                                placeholder="XX-XXXX-XXXXXX"
                                required />

                            @error('student_number')
                            <p class="mt-1 text-[10px] font-bold text-rose-500">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>


                        <!-- Contact -->
                        <div class="space-y-1">

                            <label
                                for="phone"
                                class="ml-1 text-[10px] font-black uppercase tracking-widest text-[#D4AF37]">
                                Contact No.
                            </label>

                            <input
                                id="phone"
                                class="block w-full rounded-xl border-black/10 bg-white/5 px-5 py-3.5 text-sm text-black transition-all placeholder:text-slate-600 focus:border-[#D4AF37] focus:ring-0"
                                type="text"
                                name="phone"
                                value="{{ old('phone') }}"
                                placeholder="09XXXXXXXXX"
                                required />

                            @error('phone')
                            <p class="mt-1 text-[10px] font-bold text-rose-500">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                    </div>


                    <!-- Password Section -->
                    <div
                        class="space-y-2"
                        x-data="{
                            show: false,
                            password: '',

                            get minLength() {
                                return this.password.length >= 8;
                            },

                            get hasUpper() {
                                return /[A-Z]/.test(this.password);
                            },

                            get hasLower() {
                                return /[a-z]/.test(this.password);
                            },

                            get hasNumber() {
                                return /[0-9]/.test(this.password);
                            },

                            get hasSpecial() {
                                return /[^A-Za-z0-9]/.test(this.password);
                            },

                            get score() {
                                let s = 0;

                                if (this.minLength) s++;
                                if (this.hasUpper && this.hasLower) s++;
                                if (this.hasNumber) s++;
                                if (this.hasSpecial) s++;

                                return s;
                            },

                            get label() {
                                if (!this.password) return '';
                                if (this.score <= 1) return 'Weak';
                                if (this.score === 2) return 'Fair';
                                if (this.score === 3) return 'Good';
                                return 'Strong';
                            },

                            get barColor() {
                                if (this.score <= 1) return 'bg-rose-500/80';
                                if (this.score === 2) return 'bg-amber-400/80';
                                if (this.score === 3) return 'bg-sky-400/80';
                                return 'bg-emerald-400';
                            },

                            get textColor() {
                                if (this.score <= 1) return 'text-rose-400';
                                if (this.score === 2) return 'text-amber-400';
                                if (this.score === 3) return 'text-sky-400';
                                return 'text-emerald-400';
                            }
                        }">

                        <!-- Password + Confirm -->
                        <div class="grid grid-cols-2 gap-4">

                            <!-- Password -->
                            <div class="space-y-1">

                                <label
                                    for="password"
                                    class="ml-1 text-[10px] font-black uppercase tracking-widest text-[#D4AF37]">
                                    Password
                                </label>

                                <div class="group relative">

                                    <input
                                        id="password"
                                        x-model="password"
                                        :type="show ? 'text' : 'password'"
                                        class="block w-full rounded-xl border-black/10 bg-white/5 px-5 py-3.5 pr-11 text-sm text-black transition-all placeholder:text-slate-600 focus:border-[#D4AF37] focus:ring-0"
                                        name="password"
                                        placeholder="••••••••"
                                        required
                                        autocomplete="new-password" />

                                    <!-- Password Toggle -->
                                    <button
                                        type="button"
                                        @click="show = !show"
                                        class="absolute right-0 top-0 flex h-full w-11 items-center justify-center text-slate-500 transition-colors hover:text-[#D4AF37] focus:outline-none"
                                        :aria-label="show ? 'Hide password' : 'Show password'">

                                        <!-- Show Password -->
                                        <svg
                                            x-show="!show"
                                            x-cloak
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                            <circle cx="12" cy="12" r="2.8" />
                                        </svg>


                                        <!-- Hide Password -->
                                        <svg
                                            x-show="show"
                                            x-cloak
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M3 3l18 18" />
                                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                                            <path d="M9.9 5.2A10.6 10.6 0 0 1 12 5c6 0 9.5 7 9.5 7a17.5 17.5 0 0 1-3.2 4.2" />
                                            <path d="M6.2 6.2C3.8 7.8 2.5 12 2.5 12s3.5 6 9.5 6c1.5 0 2.9-.4 4.1-1" />
                                        </svg>

                                    </button>

                                </div>

                                @error('password')
                                <p class="mt-1 text-[10px] font-bold text-rose-500">
                                    {{ $message }}
                                </p>
                                @enderror

                            </div>


                            <!-- Confirm Password -->
                            <div class="space-y-1">

                                <label
                                    for="password_confirmation"
                                    class="ml-1 text-[10px] font-black uppercase tracking-widest text-[#D4AF37]">
                                    Confirm
                                </label>

                                <div class="relative">

                                    <input
                                        id="password_confirmation"
                                        :type="show ? 'text' : 'password'"
                                        class="block w-full rounded-xl border-black/10 bg-white/5 px-5 py-3.5 text-sm text-black transition-all placeholder:text-slate-600 focus:border-[#D4AF37] focus:ring-0"
                                        name="password_confirmation"
                                        placeholder="••••••••"
                                        required
                                        autocomplete="new-password" />

                                </div>

                            </div>

                        </div>


                        <!-- Password Strength -->
                        <div class="mt-3.5 space-y-2 px-1">

                            <!-- Header -->
                            <div class="flex items-center justify-between text-[8px] font-bold uppercase tracking-wider">

                                <span class="text-slate-500">
                                    Security Health
                                </span>

                                <span
                                    x-text="label"
                                    :class="textColor"
                                    class="font-black transition-colors"></span>

                            </div>


                            <!-- Strength Bar -->
                            <div class="grid h-[2px] w-full grid-cols-4 gap-1.5 overflow-hidden rounded-full bg-black/10">

                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="score >= 1 ? barColor : 'bg-transparent'"></div>

                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="score >= 2 ? barColor : 'bg-transparent'"></div>

                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="score >= 3 ? barColor : 'bg-transparent'"></div>

                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="score >= 4 ? barColor : 'bg-transparent'"></div>

                            </div>


                            <!-- Requirements -->
                            <div class="flex items-center justify-between pt-1 text-[5.5px] font-medium">

                                <span
                                    :class="minLength ? 'font-bold text-emerald-400' : 'text-slate-500'"
                                    class="flex items-center gap-1.5 transition-colors">
                                    <span
                                        class="h-1 w-1 shrink-0 rounded-full transition-colors"
                                        :class="minLength ? 'bg-emerald-400' : 'bg-slate-700'"></span>
                                    8+ chars
                                </span>


                                <span
                                    :class="(hasUpper && hasLower) ? 'font-bold text-emerald-400' : 'text-slate-500'"
                                    class="flex items-center gap-1.5 transition-colors">
                                    <span
                                        class="h-1 w-1 shrink-0 rounded-full transition-colors"
                                        :class="(hasUpper && hasLower) ? 'bg-emerald-400' : 'bg-slate-700'"></span>
                                    Aa mixed
                                </span>


                                <span
                                    :class="hasNumber ? 'font-bold text-emerald-400' : 'text-slate-500'"
                                    class="flex items-center gap-1.5 transition-colors">
                                    <span
                                        class="h-1 w-1 shrink-0 rounded-full transition-colors"
                                        :class="hasNumber ? 'bg-emerald-400' : 'bg-slate-700'"></span>
                                    0-9 digit
                                </span>


                                <span
                                    :class="hasSpecial ? 'font-bold text-emerald-400' : 'text-slate-500'"
                                    class="flex items-center gap-1.5 transition-colors">
                                    <span
                                        class="h-1 w-1 shrink-0 rounded-full transition-colors"
                                        :class="hasSpecial ? 'bg-emerald-400' : 'bg-slate-700'"></span>
                                    Symbol
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- Terms and Privacy -->
                    @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())

                    <div class="mt-4">

                        <label
                            for="terms"
                            class="flex cursor-pointer items-center">

                            <x-checkbox
                                name="terms"
                                id="terms"
                                required
                                class="rounded border-black/20 bg-white/5 text-[#D4AF37] focus:ring-0" />

                            <div class="ms-3 text-[10px] font-bold uppercase leading-relaxed tracking-wider text-slate-400">

                                {!! __('I accept the :terms_of_service and :privacy_policy', [
                                'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="text-[#D4AF37] transition-all hover:underline">'.__('Terms').'</a>',
                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="text-[#D4AF37] transition-all hover:underline">'.__('Privacy Policy').'</a>',
                                ]) !!}

                            </div>

                        </label>

                    </div>

                    @endif


                    <!-- Cloudflare Turnstile -->
                    <div class="my-3 flex justify-center">

                        <div
                            class="cf-turnstile"
                            data-sitekey="{{ config('services.turnstile.site_key') }}"
                            data-theme="light"></div>

                    </div>


                    @error('cf-turnstile-response')

                    <p class="-mt-1 mb-2 text-center text-[10px] font-bold uppercase tracking-wider text-rose-400">
                        {{ $message }}
                    </p>

                    @enderror


                    <!-- Buttons -->
                    <div class="flex flex-col space-y-4 pt-2">

                        <!-- Register -->
                        <button
                            type="submit"
                            class="group relative w-full overflow-hidden rounded-xl bg-[#D4AF37] p-4 shadow-[0_10px_20px_-5px_rgba(212,175,55,0.4)] transition-all hover:bg-[#e6c152] active:scale-95">

                            <span class="relative z-10 text-xs font-black uppercase tracking-[0.3em] text-black">
                                Create New Account
                            </span>

                        </button>


                        <!-- Login -->
                        <a
                            class="text-center text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 transition-colors hover:text-[#D4AF37]"
                            href="{{ route('login') }}">
                            {{ __('Already registered? Login') }}
                        </a>

                    </div>

                </form>

            </div>


            <!-- Footer -->
            <p class="mt-8 text-center text-[9px] font-medium uppercase tracking-[0.5em] text-slate-600">
                ARAULLO UNIVERSITY &bull; COMPUTER LABORATORY MANAGEMENT SYSTEM
            </p>

        </div>

    </div>

</x-guest-layout>