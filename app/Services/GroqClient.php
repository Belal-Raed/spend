<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Asks Groq's chat model for the next reply, with tools it may call.
 *
 * Groq follows OpenAI's chat completions format.
 *
 * @see https://console.groq.com/docs/tool-use
 */
class GroqClient
{
    /**
     * The model's next message: text for the user, or tool calls to run first.
     *
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return array{role: string, content: ?string, tool_calls?: list<array{id: string, type: string, function: array{name: string, arguments: string}}>}
     *
     * @throws RequestException With status 429 once the account's free limits are used up for now.
     */
    public function chat(array $messages, array $tools): array
    {
        return Http::baseUrl(config('services.groq.base_url'))
            ->withToken(config('services.groq.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post('chat/completions', [
                'model' => config('services.groq.model'),
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => 'auto',
                'temperature' => 0.2,
            ])
            ->throw()
            ->json('choices.0.message');
    }
}
