<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LeadService
{
    public function sendLeadToDiscord(array $lead): bool
    {
        if (empty($lead)) {
            return false;
        }

        $commonData = [
            "name" => $lead['firstname'],
            "mobile_number" => $lead['ph_number'],
            "email" => $lead['email'],
            "source_url" => config('services.discord.source_url', 'https://launchgovtest.homes/'),
        ];

        $additionalData = $this->getAdditionalData($lead);

        $commonData['additional_data'] = $additionalData;
        $leadManagement = $commonData;

        $checkJunk = $this->checkJunk(json_encode($leadManagement));
        $checkDnc = $this->checkDnc($lead['email'], $lead['ph_number']);
        $ipAddress = $this->fetchIp();

        $webhookData = [
            'client_id' => null,
            'project_id' => null,
            'ip_address' => $ipAddress,
            'is_verified' => 0,
        ];

        if (!empty($lead['wp_otp']) && $lead['wp_otp'] === $lead['user_otp']) {
            $leadManagement['additional_data'][] = [
                "key" => "Whatsapp Verified",
                "value" => "Yes",
            ];
        } else {
            $leadManagement['additional_data'][] = [
                "key" => "Whatsapp Verified",
                "value" => "No",
            ];
        }

        if (!empty($checkJunk['Terms']) || $checkDnc['status'] ?? false) {
            $webhookData['status'] = !empty($checkJunk['Terms']) ? 'junk' : 'DNC Registry';
            $webhookData['is_send_discord'] = 0;
        } else {
            $webhookData['status'] = 'clear';
            $webhookData['is_send_discord'] = 1;
            $this->sendFrequencyLead($leadManagement);
            session(['lead_sent' => true]);
        }

        $webhookData = array_merge($webhookData, $lead);

        return $this->sendData($webhookData);
    }

    private function getAdditionalData(array $lead): array
    {
        $additionalData = [];

        if ($lead['form_type'] === 'condo') {
            $additionalData = [
                ["key" => "Project", "value" => "Condo " . $lead['project']],
                ["key" => "Blk", "value" => $lead['block']],
                ["key" => "Looking to sell your property", "value" => $lead['sell']],
                ["key" => "Floor - Unit number", "value" => $lead['floor'] . " - " . $lead['number']],
            ];
        } elseif ($lead['form_type'] === 'landed') {
            $additionalData = [
                ["key" => "Project", "value" => "Landed"],
                ["key" => "Landed Street", "value" => $lead['street']],
                ["key" => "SQFT", "value" => $lead['sqft']],
                ["key" => "Like to Know", "value" => $lead['like_to_know']],
                ["key" => "Plans", "value" => $lead['plan']],
            ];
        } elseif ($lead['form_type'] === 'hdb') {
            $additionalData = [
                ["key" => "Project", "value" => "HDB"],
                ["key" => "Town", "value" => $lead['town']],
                ["key" => "Street Name", "value" => $lead['street']],
                ["key" => "Blk", "value" => $lead['block']],
                ["key" => "HDB Flat Type", "value" => $lead['flat_type']],
                ["key" => "Looking to sell your property", "value" => $lead['sell']],
                ["key" => "Floor - Unit number", "value" => $lead['floor'] . " - " . $lead['unit']],
            ];
        }

        return $additionalData;
    }

    private function sendData(array $data): bool
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode(config('services.webhook.auth')),
        ])->post(config('services.webhook.url'), $data);

        return $response->successful();
    }

    private function fetchIp(): ?string
    {
        $response = Http::get('https://api.ipify.org/?format=json');

        if ($response->successful()) {
            return $response->json('ip');
        }

        return null;
    }

    private function checkJunk(string $data): ?array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'text/plain',
            'Ocp-Apim-Subscription-Key' => config('services.content_moderator.key'),
        ])->post(config('services.content_moderator.url'), $data);

        return $response->json();
    }

    private function sendFrequencyLead(array $data): bool
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode(config('services.frequency.auth')),
        ])->post(config('services.frequency.url'), $data);

        return $response->successful();
    }

    private function checkDnc(string $email, string $phone): ?array
    {
        $response = Http::post(config('services.dnc.url'), [
            'email' => $email,
            'ph_number' => $phone,
        ]);

        return $response->json();
    }
}
