<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Jika reCAPTCHA dinonaktifkan dalam konfigurasi, otomatis loloskan validasi
        if (! config('services.recaptcha.enabled')) {
            return;
        }

        if (empty($value) || ! is_string($value)) {
            $fail('Silakan centang verifikasi "Saya bukan robot".');
            return;
        }

        $secretKey = config('services.recaptcha.secret_key');
        if (empty($secretKey)) {
            Log::warning('reCAPTCHA enabled but RECAPTCHA_SECRET_KEY is empty in configuration.');
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secretKey,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (! $response->successful() || ! $response->json('success')) {
                $errorCodes = $response->json('error-codes', []);
                Log::notice('reCAPTCHA verification rejected by Google API.', [
                    'ip' => request()->ip(),
                    'errors' => $errorCodes,
                ]);

                $fail('Verifikasi reCAPTCHA tidak valid atau telah kedaluwarsa. Silakan centang kembali.');
            }
        } catch (\Throwable $e) {
            Log::error('reCAPTCHA service connection exception: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $fail('Gagal menghubungi layanan verifikasi keamanan Google reCAPTCHA. Silakan coba sesaat lagi.');
        }
    }
}
