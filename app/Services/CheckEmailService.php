<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmailCheckService
{
    /**
     * Check email and phone number using an external API.
     *
     * @param string $email
     * @param string $ph_number
     * @return array|null
     */
    public function checkEmail(string $email, string $ph_number): ?array
    {
        try {
            // Make the HTTP POST request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(config('services.email_check.url'), [
                'email' => $email,
                'ph_number' => $ph_number,
            ]);

            // Return the JSON response as an array
            if ($response->successful()) {
                return $response->json();
            }

            // Handle error response
            return [
                'error' => 'Failed to check email. Status: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            // Handle exceptions
            return [
                'error' => 'Exception occurred: ' . $e->getMessage(),
            ];
        }
    }
}
