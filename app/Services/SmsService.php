<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS message to a phone number.
     *
     * @param string $to The recipient's phone number
     * @param string $message The text message to send
     * @return bool True if successful, false otherwise
     */
    public function send(string $to, string $message): bool
    {
        // TODO: Integrate actual SMS provider API here (e.g. Twilio, Termii, AWS SNS)
        
        // For now, log the SMS content
        Log::info("SMS Service - Sending SMS to {$to}: {$message}");
        
        return true;
    }
}
