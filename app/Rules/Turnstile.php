<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Exception;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.turnstile.secret_key');

        if (empty($secretKey)) {
            logger()->error('Turnstile Error: Secret key is missing.');
            $fail('CAPTCHA configuration error. Please contact the administrator.');
            return;
        }

        if (empty($value)) {
            $fail('The CAPTCHA verification failed. Please try again.');
            return;
        }

        try {
            $response = Http::withOptions([
                'verify' => ! app()->isLocal(),
            ])->asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secretKey,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            $responseData = $response->json();

            if (! ($responseData['success'] ?? false)) {
                logger()->warning('Turnstile Verification Failed', [
                    'errors' => $responseData['error-codes'] ?? ['Unknown error'],
                    'response_payload' => $responseData,
                ]);

                $fail('The CAPTCHA verification failed. Please try again.');
            }
        } catch (Exception $e) {
            logger()->error('Turnstile Connection Exception: ' . $e->getMessage());

            if (! app()->isLocal()) {
                $fail('Unable to reach security verification servers. Please try again.');
            }
        }
    }
}
