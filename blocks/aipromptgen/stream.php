<?php
// This file is part of Moodle - http://moodle.org/.
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

/**
 * Server-Sent Events streaming endpoint for AI (Ollama) responses.
 *
 * Provides an SSE (text/event-stream) interface that proxies incremental NDJSON
 * output from a local Ollama server to the browser as start/chunk/error/done events.
 *
 * @package    block_aipromptgen
 * @copyright  2025 AI4Teachers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */







require_once(__DIR__ . '/../../config.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
require_login($courseid);
$provider = optional_param('provider', 'ollama', PARAM_ALPHA);

// Disable buffering for streaming.
while (ob_get_level()) {
    @ob_end_clean();
}
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

/**
 * Emit a Server-Sent Event.
 * Splits multi-line data so each line is sent as a distinct data: field for the same event type.
 *
 * @param string $data  The payload to send (may contain newlines).
 * @param string $event The SSE event name (e.g. start, chunk, error, done).
 * @return void
 */
function block_aipromptgen_send_event(string $data, string $event = 'message'): void {
    echo "event: {$event}\n";
    foreach (preg_split('/\r?\n/', $data) as $line) {
        echo 'data: ' . $line . "\n";
    }
    echo "\n";
    @flush();
}

if (empty($courseid)) {
    block_aipromptgen_send_event('Required parameter "courseid" is missing or invalid.', 'error');
    block_aipromptgen_send_event('[DONE]', 'done');
    exit;
}

try {
    $course = get_course($courseid);
    $context = context_course::instance($course->id);
    require_capability('block/aipromptgen:manage', $context);
} catch (Exception $e) {
    block_aipromptgen_send_event('Access error: ' . $e->getMessage(), 'error');
    block_aipromptgen_send_event('[DONE]', 'done');
    exit;
}
$rawprompt = optional_param('prompt', '', PARAM_RAW_TRIMMED);
if ($rawprompt === '') {
    // Fallback: concatenate basic fields (topic, lesson, outcomes). This is simplified vs full view builder.
    $topic = optional_param('topic', '', PARAM_TEXT);
    $lesson = optional_param('lesson', '', PARAM_TEXT);
    $outcomes = optional_param('outcomes', '', PARAM_RAW_TRIMMED);
    $rawprompt = "Topic: {$topic}\nLesson: {$lesson}\nOutcomes: {$outcomes}";
}

// Rate limiting.
if (!\block_aipromptgen\helper::check_rate_limit()) {
    block_aipromptgen_send_event(get_string('error_ratelimit', 'block_aipromptgen'), 'error');
    block_aipromptgen_send_event('[DONE]', 'done');
    exit;
}

// Ollama, Gemini, Claude, DeepSeek and Custom API streaming implemented here.
if (!in_array($provider, ['ollama', 'gemini', 'claude', 'deepseek', 'custom'])) {
    block_aipromptgen_send_event('Unsupported provider for streaming: ' . $provider, 'error');
    block_aipromptgen_send_event('[DONE]', 'done');
    exit;
}

// Resolve the system prompt (admin-configured or built-in default).
$configuredsystemprompt = (string)(get_config('block_aipromptgen', 'system_prompt') ?? '');
$systemprompt = $configuredsystemprompt !== '' ? $configuredsystemprompt : 'You are a helpful assistant.';

// Resolve temperature and max tokens.
$rawtemp = get_config('block_aipromptgen', 'temperature');
$streamtemperature = ($rawtemp !== false && $rawtemp !== '') ? (float)$rawtemp : 0.7;
$rawmax = get_config('block_aipromptgen', 'max_tokens');
$streammaxtokens = ($rawmax !== false && $rawmax !== '') ? (int)$rawmax : 1024;

if ($provider === 'gemini') {
    $apikey = (string)(get_config('block_aipromptgen', 'gemini_apikey') ?? '');
    $model = (string)(get_config('block_aipromptgen', 'gemini_model') ?? 'gemini-1.5-flash');
    if ($apikey === '') {
        block_aipromptgen_send_event('Gemini API key not configured', 'error');
        block_aipromptgen_send_event('[DONE]', 'done');
        exit;
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?key={$apikey}";
    $payload = json_encode([
        'contents' => [['parts' => [['text' => $rawprompt]]]],
        'system_instruction' => ['parts' => [['text' => $systemprompt]]],
        'generationConfig' => [
            'temperature' => $streamtemperature,
            'maxOutputTokens' => $streammaxtokens,
        ],
    ]);

    require_once($CFG->libdir . '/filelib.php');
    $curl = new curl(['ignoresecurity' => true]);
    $options = [
        'CURLOPT_HTTPHEADER' => ['Content-Type: application/json'],
        'CURLOPT_RETURNTRANSFER' => false,
        'CURLOPT_WRITEFUNCTION' => function ($ch, $chunk) {
            static $buffer = '';
            $buffer .= $chunk;
            // Gemini sends a JSON array of candidates.
            while (($start = strpos($buffer, '{')) !== false) {
                $depth = 0;
                $end = -1;
                for ($i = $start; $i < strlen($buffer); $i++) {
                    if ($buffer[$i] === '{') {
                        $depth++;
                    } else if ($buffer[$i] === '}') {
                        $depth--;
                    }
                    if ($depth === 0) {
                        $end = $i;
                        break;
                    }
                }
                if ($end === -1) {
                    break;
                }

                $json = substr($buffer, $start, $end - $start + 1);
                $buffer = substr($buffer, $end + 1);
                $obj = json_decode($json, true);
                if (isset($obj['candidates'][0]['content']['parts'][0]['text'])) {
                    block_aipromptgen_send_event($obj['candidates'][0]['content']['parts'][0]['text'], 'chunk');
                }
            }
            return strlen($chunk);
        },
    ];

    block_aipromptgen_send_event('Gemini streaming start', 'start');
    $res = $curl->post($url, $payload, $options);
    if ($res !== true && is_string($res) && $res !== '') {
        block_aipromptgen_send_event('cURL error: ' . $res, 'error');
    } else if (!empty($curl->error)) {
        block_aipromptgen_send_event('cURL error: ' . $curl->error, 'error');
    }
    block_aipromptgen_send_event('[DONE]', 'done');
    exit;
}

if ($provider === 'deepseek') {
    $apikey = (string)(get_config('block_aipromptgen', 'deepseek_apikey') ?? '');
    $model = (string)(get_config('block_aipromptgen', 'deepseek_model') ?? 'deepseek-chat');
    if ($apikey === '') {
        block_aipromptgen_send_event('DeepSeek API key not configured', 'error');
        block_aipromptgen_send_event('[DONE]', 'done');
        exit;
    }

    $url = 'https://api.deepseek.com/v1/chat/completions';
    $payload = json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemprompt],
            ['role' => 'user', 'content' => $rawprompt],
        ],
        'stream' => true,
        'temperature' => $streamtemperature,
        'max_tokens' => $streammaxtokens,
    ]);

    require_once($CFG->libdir . '/filelib.php');
    $curl = new curl(['ignoresecurity' => true]);
    $options = [
        'CURLOPT_HTTPHEADER' => [
            'Authorization: Bearer ' . $apikey,
            'Content-Type: application/json',
        ],
        'CURLOPT_RETURNTRANSFER' => false,
        'CURLOPT_WRITEFUNCTION' => function ($ch, $chunk) {
            static $buffer = '';
            $buffer .= $chunk;
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);
                if (strpos($line, 'data: ') === 0) {
                    $json = substr($line, 6);
                    if ($json === '[DONE]') {
                        break;
                    }
                    $obj = json_decode($json, true);
                    if (isset($obj['choices'][0]['delta']['content'])) {
                        block_aipromptgen_send_event($obj['choices'][0]['delta']['content'], 'chunk');
                    }
                }
            }
            return strlen($chunk);
        },
    ];

    block_aipromptgen_send_event('DeepSeek streaming start', 'start');
    $res = $curl->post($url, $payload, $options);
    if ($res !== true && is_string($res) && $res !== '') {
        block_aipromptgen_send_event('cURL error: ' . $res, 'error');
    } else if (!empty($curl->error)) {
        block_aipromptgen_send_event('cURL error: ' . $curl->error, 'error');
    }
    block_aipromptgen_send_event('[DONE]', 'done');
    exit;
}

if ($provider === 'claude') {
    $apikey = (string)(get_config('block_aipromptgen', 'claude_apikey') ?? '');
    $model = (string)(get_config('block_aipromptgen', 'claude_model') ?? 'claude-3-5-sonnet-20240620');
    if ($apikey === '') {
        block_aipromptgen_send_event('Claude API key not configured', 'error');
        block_aipromptgen_send_event('[DONE]', 'done');
        exit;
    }

    $url = 'https://api.anthropic.com/v1/messages';
    $payload = json_encode([
        'model' => $model,
        'max_tokens' => $streammaxtokens,
        'system' => $systemprompt,
        'messages' => [['role' => 'user', 'content' => $rawprompt]],
        'stream' => true,
        'temperature' => $streamtemperature,
    ]);

    require_once($CFG->libdir . '/filelib.php');
    $curl = new curl(['ignoresecurity' => true]);
    $options = [
        'CURLOPT_HTTPHEADER' => [
            'x-api-key: ' . $apikey,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        'CURLOPT_RETURNTRANSFER' => false,
        'CURLOPT_WRITEFUNCTION' => function ($ch, $chunk) {
            static $buffer = '';
            $buffer .= $chunk;
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);
                if (strpos($line, 'data: ') === 0) {
                    $json = substr($line, 6);
                    $obj = json_decode($json, true);
                    if ($obj && $obj['type'] === 'content_block_delta') {
                        block_aipromptgen_send_event($obj['delta']['text'], 'chunk');
                    }
                }
            }
            return strlen($chunk);
        },
    ];

    block_aipromptgen_send_event('Claude streaming start', 'start');
    $res = $curl->post($url, $payload, $options);
    if ($res !== true && is_string($res) && $res !== '') {
        block_aipromptgen_send_event('cURL error: ' . $res, 'error');
    } else if (!empty($curl->error)) {
        block_aipromptgen_send_event('cURL error: ' . $curl->error, 'error');
    }
    block_aipromptgen_send_event('[DONE]', 'done');
    exit;
}

if ($provider === 'custom') {
    $endpoint = (string)(get_config('block_aipromptgen', 'custom_endpoint') ?? '');
    if ($endpoint === '') {
        block_aipromptgen_send_event('Custom API endpoint not configured', 'error');
        block_aipromptgen_send_event('[DONE]', 'done');
        exit;
    }

    $apikey = (string)(get_config('block_aipromptgen', 'custom_apikey') ?? '');
    $model = (string)(get_config('block_aipromptgen', 'custom_model') ?? '');

    $payload = json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemprompt],
            ['role' => 'user', 'content' => $rawprompt],
        ],
        'stream' => true,
        'temperature' => $streamtemperature,
        'max_tokens' => $streammaxtokens,
    ]);

    $hdrs = ['Content-Type: application/json'];
    if ($apikey !== '') {
        $hdrs[] = 'Authorization: Bearer ' . $apikey;
    }

    require_once($CFG->libdir . '/filelib.php');
    $curl = new curl(['ignoresecurity' => true]);
    $options = [
        'CURLOPT_HTTPHEADER' => $hdrs,
        'CURLOPT_RETURNTRANSFER' => false,
        'CURLOPT_WRITEFUNCTION' => function ($ch, $chunk) {
            static $buffer = '';
            $buffer .= $chunk;
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);
                if (strpos($line, 'data: ') === 0) {
                    $json = substr($line, 6);
                    if ($json === '[DONE]') {
                        break;
                    }
                    $obj = json_decode($json, true);
                    if (isset($obj['choices'][0]['delta']['content'])) {
                        block_aipromptgen_send_event($obj['choices'][0]['delta']['content'], 'chunk');
                    }
                }
            }
            return strlen($chunk);
        },
    ];

    // Bypass SSL checks for local endpoints.
    if (
        preg_match(
            '~^https?://(localhost|127\.0\.0\.1|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)~i',
            $endpoint
        )
    ) {
        $options['CURLOPT_SSL_VERIFYPEER'] = false;
        $options['CURLOPT_SSL_VERIFYHOST'] = 0;
    }

    block_aipromptgen_send_event('Custom API streaming start', 'start');
    $res = $curl->post($endpoint, $payload, $options);
    if ($res !== true && is_string($res) && $res !== '') {
        block_aipromptgen_send_event('cURL error: ' . $res, 'error');
    } else if (!empty($curl->error)) {
        block_aipromptgen_send_event('cURL error: ' . $curl->error, 'error');
    }
    block_aipromptgen_send_event('[DONE]', 'done');
    exit;
}

$endpoint = (string)(get_config('block_aipromptgen', 'ollama_endpoint') ?? '');
$model = (string)(get_config('block_aipromptgen', 'ollama_model') ?? '');
if ($endpoint === '' || $model === '') {
    block_aipromptgen_send_event('Ollama not configured', 'error');
    block_aipromptgen_send_event('[DONE]', 'done');
    exit;
}

$schemastr = (string)(get_config('block_aipromptgen', 'ollama_schema') ?? '');
$schema = null;
if ($schemastr !== '') {
    $tmp = json_decode($schemastr, true);
    if (is_array($tmp)) {
        $schema = $tmp;
    }
}

$maxpredict = (int)(get_config('block_aipromptgen', 'ollama_num_predict') ?? 256);
if ($maxpredict <= 0) {
    $maxpredict = 256;
}

$timeout = (int)(get_config('block_aipromptgen', 'ollama_timeout') ?? 180);
if ($timeout < 30) {
    $timeout = 180;
}
@set_time_limit($timeout + 30);

$url = rtrim($endpoint, '/') . '/api/generate';
$body = [
    'model' => $model,
    'prompt' => $rawprompt,
    'stream' => true,
    'options' => [
        'num_predict' => $maxpredict,
        'temperature' => $schema ? 0 : 0.7,
    ],
];
if ($schema) {
    $body['format'] = $schema;
}
$payload = json_encode($body, JSON_UNESCAPED_UNICODE);
require_once($CFG->libdir . '/filelib.php');
$curl = new curl(['ignoresecurity' => true]);

$options = [
    'CURLOPT_HTTPHEADER' => ['Content-Type: application/json'],
    'CURLOPT_RETURNTRANSFER' => false, // Stream directly via callback.
    'CURLOPT_FOLLOWLOCATION' => true,
    'CURLOPT_TIMEOUT' => $timeout,
    'CURLOPT_WRITEFUNCTION' => function ($ch, $chunk) use (&$schema) {
        static $buffer = '';
        $buffer .= $chunk;
        // Split on newlines for NDJSON.
        while (($pos = strpos($buffer, "\n")) !== false) {
            $line = trim(substr($buffer, 0, $pos));
            $buffer = substr($buffer, $pos + 1);
            if ($line === '') {
                continue;
            }
            $obj = json_decode($line, true);
            if (!is_array($obj)) {
                continue;
            }
            if (isset($obj['error'])) {
                block_aipromptgen_send_event('Error: ' . $obj['error'], 'error');
                continue;
            }
            if (isset($obj['response'])) {
                block_aipromptgen_send_event($obj['response'], 'chunk');
            }
            if (!empty($obj['done'])) {
                block_aipromptgen_send_event('[DONE]', 'done');
            }
        }
        return strlen($chunk);
    },
];

// Bypass Moodle curl security for local/private endpoints.
if (preg_match('~^https?://(localhost|127\.0\.0\.1|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)~i', $url)) {
    $options['CURLOPT_SSL_VERIFYPEER'] = false;
    $options['CURLOPT_SSL_VERIFYHOST'] = 0;
}

block_aipromptgen_send_event('Streaming start', 'start');
$res = $curl->post($url, $payload, $options);
if ($res !== true && is_string($res) && $res !== '') {
    block_aipromptgen_send_event('cURL error: ' . $res, 'error');
} else if (!empty($curl->error)) {
    block_aipromptgen_send_event('cURL error: ' . $curl->error, 'error');
}
block_aipromptgen_send_event('[DONE]', 'done');
exit;
