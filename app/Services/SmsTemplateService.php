<?php

namespace App\Services;

use App\Models\SmsTemplate;

class SmsTemplateService
{
    public function render(string $code, ?string $channel, array $values = [], ?string $fallback = null): string
    {
        $template = $this->template($code, $channel);
        $message = $template?->message ?: $fallback;

        if (!$message) {
            return '';
        }

        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function (array $matches) use ($values) {
            return array_key_exists($matches[1], $values) ? (string) $values[$matches[1]] : $matches[0];
        }, $message);
    }

    public function template(string $code, ?string $channel): ?SmsTemplate
    {
        $channel = strtoupper((string) ($channel ?: 'ALL'));

        return SmsTemplate::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->whereIn('channel', [$channel, 'ALL'])
            ->orderByRaw("CASE WHEN channel = ? THEN 0 ELSE 1 END", [$channel])
            ->first();
    }

    public function accountLastDigits(?string $accountNumber, int $digits = 3): string
    {
        $accountNumber = preg_replace('/\D+/', '', (string) $accountNumber);

        if ($accountNumber === '') {
            return '';
        }

        return substr($accountNumber, -$digits);
    }
}
