<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Please enter the 6-digit code sent to your email to verify your identity.') }}
    </div>

    <form method="POST" action="{{ route('verify.store') }}">
        @csrf
        <div>
            <x-label for="two_factor_code" value="{{ __('Verification Code') }}" />
            <x-input id="two_factor_code" class="block mt-1 w-full" type="text" name="two_factor_code" required autofocus />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-button>
                {{ __('Verify Code') }}
            </x-button>
        </div>
    </form>
</x-guest-layout>