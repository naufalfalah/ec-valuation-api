<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DiscordService
{
    public function sendLeadToDiscord(array $lead)
    {
        Http::post(config('services.discord.webhook_url'), $lead);
    }
}
