<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_aipromptgen;

use curl;

/**
 * AI Client class for the block_aipromptgen plugin.
 *
 * @package    block_aipromptgen
 * @copyright  2025 AI4Teachers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_client {
    /** @var string OpenAI API Key */
    private $openaikey;

    /** @var string OpenAI Model */
    private $openaimodel;

    /** @var string Gemini API Key */
    private $geminikey;

    /** @var string Gemini Model */
    private $geminimodel;

    /** @var string Claude API Key */
    private $claudekey;

    /** @var string Claude Model */
    private $claudemodel;

    /** @var string DeepSeek API Key */
    private $deepseekkey;

    /** @var string DeepSeek Model */
    private $deepseekmodel;

    /** @var string Custom API Endpoint */
    private $customendpoint;

    /** @var string Custom API Key */
    private $customapikey;

    /** @var string Custom API Model */
    private $custommodel;

    /** @var string Ollama Endpoint */
    private $ollamaendpoint;

    /** @var string Ollama Model */
    private $ollamamodel;

    /** @var float AI response temperature (0.0 – 2.0) */
    private $temperature;

    /** @var int Maximum tokens in AI response */
    private $maxtokens;

    /**
     * Constructor. Retrieves configuration settings.
     */
    public function __construct() {
        $this->openaikey = get_config('block_aipromptgen', 'openai_apikey');
        $this->openaimodel = get_config('block_aipromptgen', 'openai_model') ?: 'gpt-3.5-turbo';

        $this->geminikey = get_config('block_aipromptgen', 'gemini_apikey');
        $this->geminimodel = get_config('block_aipromptgen', 'gemini_model') ?: 'gemini-1.5-flash';

        $this->claudekey = get_config('block_aipromptgen', 'claude_apikey');
        $this->claudemodel = get_config('block_aipromptgen', 'claude_model') ?: 'claude-3-5-sonnet-20240620';

        $this->deepseekkey = get_config('block_aipromptgen', 'deepseek_apikey');
        $this->deepseekmodel = get_config('block_aipromptgen', 'deepseek_model') ?: 'deepseek-chat';

        $this->customendpoint = get_config('block_aipromptgen', 'custom_endpoint');
        $this->customapikey = get_config('block_aipromptgen', 'custom_apikey');
        $this->custommodel = get_config('block_aipromptgen', 'custom_model') ?: 'custom-model';

        $this->ollamaendpoint = get_config('block_aipromptgen', 'ollama_endpoint');
        $this->ollamamodel = get_config('block_aipromptgen', 'ollama_model') ?: 'llama3';

        $rawtemp = get_config('block_aipromptgen', 'temperature');
        $this->temperature = ($rawtemp !== false && $rawtemp !== '') ? (float)$rawtemp : 0.7;

        $rawmax = get_config('block_aipromptgen', 'max_tokens');
        $this->maxtokens = ($rawmax !== false && $rawmax !== '') ? (int)$rawmax : 1024;
    }

    /**
     * Get the configured system prompt, falling back to the default lang string.
     *
     * @return string
     */
    private function get_system_prompt(): string {
        $configured = get_config('block_aipromptgen', 'system_prompt');
        if (!empty($configured)) {
            return (string)$configured;
        }
        return get_string('system_role', 'block_aipromptgen');
    }

    /**
     * Send a prompt to the specified provider.
     *
     * @param string $provider 'openai', 'gemini', 'claude', 'deepseek', 'custom' or 'ollama'
     * @param string $prompt The prompt text
     * @return string The AI response text
     */
    public function send_request(string $provider, string $prompt): string {
        switch ($provider) {
            case 'openai':
                return $this->send_to_openai($prompt);
            case 'gemini':
                return $this->send_to_gemini($prompt);
            case 'claude':
                return $this->send_to_claude($prompt);
            case 'deepseek':
                return $this->send_to_deepseek($prompt);
            case 'custom':
                return $this->send_to_custom($prompt);
            case 'ollama':
                return $this->send_to_ollama($prompt);
            default:
                return 'Unknown provider specified.';
        }
    }

    /**
     * Send request to OpenAI.
     *
     * @param string $prompt The prompt to send.
     * @return string The API response.
     */
    private function send_to_openai(string $prompt): string {
        if (empty($this->openaikey)) {
            return get_string('error_noapikey', 'block_aipromptgen');
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openaikey,
        ];

        $payload = json_encode([
            'model' => $this->openaimodel,
            'messages' => [
                ['role' => 'system', 'content' => $this->get_system_prompt()],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxtokens,
        ]);

        return $this->perform_curl_request($endpoint, $payload, $headers, 60, false, 'openai');
    }

    /**
     * Send request to Gemini.
     *
     * @param string $prompt
     * @return string
     */
    private function send_to_gemini(string $prompt): string {
        if (empty($this->geminikey)) {
            return get_string('error_nogeminiapikey', 'block_aipromptgen');
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/" .
            "{$this->geminimodel}:generateContent?key={$this->geminikey}";
        $headers = ['Content-Type: application/json'];

        $payload = json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'system_instruction' => [
                'parts' => [['text' => $this->get_system_prompt()]],
            ],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => $this->maxtokens,
            ],
        ]);

        return $this->perform_curl_request($endpoint, $payload, $headers, 60, false, 'gemini');
    }

    /**
     * Send request to Claude.
     *
     * @param string $prompt
     * @return string
     */
    private function send_to_claude(string $prompt): string {
        if (empty($this->claudekey)) {
            return get_string('error_noclaudeapikey', 'block_aipromptgen');
        }

        $endpoint = 'https://api.anthropic.com/v1/messages';
        $headers = [
            'X-API-Key: ' . $this->claudekey,
            'Anthropic-Version: 2023-06-01',
            'Content-Type: application/json',
        ];

        $payload = json_encode([
            'model' => $this->claudemodel,
            'max_tokens' => $this->maxtokens,
            'system' => $this->get_system_prompt(),
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $this->temperature,
        ]);

        return $this->perform_curl_request($endpoint, $payload, $headers, 60, false, 'claude');
    }

    /**
     * Send request to DeepSeek (OpenAI-compatible API).
     *
     * @param string $prompt
     * @return string
     */
    private function send_to_deepseek(string $prompt): string {
        if (empty($this->deepseekkey)) {
            return get_string('error_nodeepseek_apikey', 'block_aipromptgen');
        }

        $endpoint = 'https://api.deepseek.com/v1/chat/completions';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->deepseekkey,
        ];

        $payload = json_encode([
            'model' => $this->deepseekmodel,
            'messages' => [
                ['role' => 'system', 'content' => $this->get_system_prompt()],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxtokens,
        ]);

        return $this->perform_curl_request($endpoint, $payload, $headers, 60, false, 'deepseek');
    }

    /**
     * Send request to a Custom OpenAI-compatible endpoint.
     *
     * @param string $prompt
     * @return string
     */
    private function send_to_custom(string $prompt): string {
        if (empty($this->customendpoint)) {
            return get_string('error_nocustom_endpoint', 'block_aipromptgen');
        }

        $headers = ['Content-Type: application/json'];
        if (!empty($this->customapikey)) {
            $headers[] = 'Authorization: Bearer ' . $this->customapikey;
        }

        $payload = json_encode([
            'model' => $this->custommodel,
            'messages' => [
                ['role' => 'system', 'content' => $this->get_system_prompt()],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxtokens,
        ]);

        $ignoresecurity = (bool)preg_match(
            '~^https?://(localhost|127\.0\.0\.1|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)~i',
            $this->customendpoint
        );

        return $this->perform_curl_request(
            $this->customendpoint,
            $payload,
            $headers,
            60,
            $ignoresecurity,
            'custom'
        );
    }

    /**
     * Send request to Ollama (Synchronous Fallback).
     *
     * @param string $prompt The prompt to send.
     * @return string The API response.
     */
    private function send_to_ollama(string $prompt): string {
        if (empty($this->ollamaendpoint)) {
            return get_string('error_noendpoint', 'block_aipromptgen');
        }

        $endpoint = rtrim($this->ollamaendpoint, '/') . '/api/generate';
        $payload = json_encode([
            'model' => $this->ollamamodel,
            'prompt' => $prompt,
            'stream' => false,
            'options' => ['num_predict' => $this->maxtokens, 'temperature' => $this->temperature],
        ]);

        // Ollama usually runs on local network/localhost, SSL might be self-signed or HTTP.
        return $this->perform_curl_request(
            $endpoint,
            $payload,
            [],
            60,
            true,
            'ollama'
        );
    }

    /**
     * Execute cURL request.
     *
     * @param string $endpoint
     * @param string $payload
     * @param array $headers
     * @param int $timeout
     * @param bool $ignoresecurity
     * @param string $provider
     * @return string
     */
    private function perform_curl_request(
        string $endpoint,
        string $payload,
        array $headers = [],
        int $timeout = 60,
        bool $ignoresecurity = false,
        string $provider = 'openai'
    ): string {
        $curl = new curl();

        $options = [
            'CURLOPT_TIMEOUT' => $timeout,
            'CURLOPT_CONNECTTIMEOUT' => 20,
            'CURLOPT_RETURNTRANSFER' => true,
        ];

        if ($ignoresecurity) {
            $options['CURLOPT_SSL_VERIFYHOST'] = 0;
            $options['CURLOPT_SSL_VERIFYPEER'] = false;
        }

        foreach ($headers as $h) {
            $curl->setHeader($h);
        }

        $response = $curl->post($endpoint, $payload, $options);

        if ($curl->errno > 0) {
            return 'cURL Error (' . $curl->errno . '): ' . $curl->error;
        }

        if ($provider === 'ollama') {
            return $this->process_ollama_response($response, false);
        }

        $json = json_decode($response, true);
        if ($provider === 'openai') {
            if (isset($json['choices'][0]['message']['content'])) {
                return $json['choices'][0]['message']['content'];
            }
        } else if ($provider === 'gemini') {
            if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                return $json['candidates'][0]['content']['parts'][0]['text'];
            }
        } else if ($provider === 'claude') {
            if (isset($json['content'][0]['text'])) {
                return $json['content'][0]['text'];
            }
        } else if ($provider === 'deepseek' || $provider === 'custom') {
            if (isset($json['choices'][0]['message']['content'])) {
                return $json['choices'][0]['message']['content'];
            }
        }

        // Generic error handling.
        if (isset($json['error']['message'])) {
            return 'API Error: ' . $json['error']['message'];
        } else if (isset($json['error'])) {
            return 'API Error: ' . (is_array($json['error']) ? json_encode($json['error']) : $json['error']);
        }

        return 'Unknown response format: ' . substr($response, 0, 200) . '...';
    }

    /**
     * Process Ollama response.
     *
     * @param string $rawresponse
     * @param bool $hasschema
     * @return string
     */
    private function process_ollama_response(string $rawresponse, bool $hasschema): string {
        $trim = trim($rawresponse);
        // Check for simple JSON first (stream=false).
        $firstchar = substr($trim, 0, 1);
        if ($firstchar === '{') {
            $json = json_decode($trim, true);
            if (isset($json['response'])) {
                $text = $json['response'];
                if ($hasschema) {
                    // Try to pretty print JSON if it is a schema response.
                    $decoded = json_decode($text);
                    if ($decoded) {
                        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    }
                }
                return $text;
            }
        }

        // Handle NDJSON stream accumulation (if it happened to return that way).
        $fulltext = '';
        foreach (preg_split("/(?:\r\n|\n|\r)/", $rawresponse) as $line) {
            if ($line === '') {
                continue;
            }
            $obj = json_decode($line, true);
            if (!is_array($obj)) {
                continue;
            }
            if (isset($obj['response'])) {
                $fulltext .= $obj['response'];
            }
            if (isset($obj['error'])) {
                return 'Ollama Error: ' . $obj['error'];
            }
        }

        if ($hasschema) {
            // Try to find JSON blob in fulltext.
            $start = strpos($fulltext, '{');
            $end = strrpos($fulltext, '}');
            if ($start !== false && $end !== false) {
                $cand = substr($fulltext, $start, $end - $start + 1);
                $decoded = json_decode($cand);
                if ($decoded) {
                    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            }
        }

        return $fulltext ?: $rawresponse;
    }
}
