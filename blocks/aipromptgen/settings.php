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
 * Settings for the AI Prompt Generator block.
 *
 * @package    block_aipromptgen
 * @author     Boban Blagojevic
 * @copyright  2025 AI4Teachers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // System prompt (applies to all providers).
    $settings->add(new admin_setting_configtextarea(
        'block_aipromptgen/system_prompt',
        get_string('setting:system_prompt', 'block_aipromptgen'),
        get_string('setting:system_prompt_desc', 'block_aipromptgen'),
        ''
    ));

    // Predefined templates (JSON).
    $settings->add(new admin_setting_configtextarea(
        'block_aipromptgen/templates',
        get_string('setting:templates', 'block_aipromptgen'),
        get_string('setting:templates_desc', 'block_aipromptgen'),
        ''
    ));

    // Temperature (0.0 - 2.0).
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/temperature',
        get_string('setting:temperature', 'block_aipromptgen'),
        get_string('setting:temperature_desc', 'block_aipromptgen'),
        '0.7',
        PARAM_FLOAT
    ));

    // Max tokens.
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/max_tokens',
        get_string('setting:max_tokens', 'block_aipromptgen'),
        get_string('setting:max_tokens_desc', 'block_aipromptgen'),
        '1024',
        PARAM_INT
    ));

    // Rate limit (requests per hour).
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/rate_limit',
        get_string('setting:rate_limit', 'block_aipromptgen'),
        get_string('setting:rate_limit_desc', 'block_aipromptgen'),
        '50',
        PARAM_INT
    ));

    // OpenAI API key (password-unmask field).
    $settings->add(new admin_setting_configpasswordunmask(
        'block_aipromptgen/openai_apikey',
        get_string('setting:apikey', 'block_aipromptgen'),
        get_string('setting:apikey_desc', 'block_aipromptgen'),
        ''
    ));

    // Default OpenAI model.
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/openai_model',
        get_string('setting:model', 'block_aipromptgen'),
        get_string('setting:model_desc', 'block_aipromptgen'),
        'gpt-4o-mini',
        PARAM_TEXT
    ));

    // Gemini API key (password-unmask field).
    $settings->add(new admin_setting_configpasswordunmask(
        'block_aipromptgen/gemini_apikey',
        get_string('setting:gemini_apikey', 'block_aipromptgen'),
        get_string('setting:gemini_apikey_desc', 'block_aipromptgen'),
        ''
    ));

    // Default Gemini model.
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/gemini_model',
        get_string('setting:gemini_model', 'block_aipromptgen'),
        get_string('setting:gemini_model_desc', 'block_aipromptgen'),
        'gemini-1.5-flash',
        PARAM_TEXT
    ));

    // Claude API key (password-unmask field).
    $settings->add(new admin_setting_configpasswordunmask(
        'block_aipromptgen/claude_apikey',
        get_string('setting:claude_apikey', 'block_aipromptgen'),
        get_string('setting:claude_apikey_desc', 'block_aipromptgen'),
        ''
    ));

    // Default Claude model.
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/claude_model',
        get_string('setting:claude_model', 'block_aipromptgen'),
        get_string('setting:claude_model_desc', 'block_aipromptgen'),
        'claude-3-5-sonnet-latest',
        PARAM_TEXT
    ));

    // DeepSeek API key.
    $settings->add(new admin_setting_configpasswordunmask(
        'block_aipromptgen/deepseek_apikey',
        get_string('setting:deepseek_apikey', 'block_aipromptgen'),
        get_string('setting:deepseek_apikey_desc', 'block_aipromptgen'),
        ''
    ));

    // Default DeepSeek model.
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/deepseek_model',
        get_string('setting:deepseek_model', 'block_aipromptgen'),
        get_string('setting:deepseek_model_desc', 'block_aipromptgen'),
        'deepseek-chat',
        PARAM_TEXT
    ));

    // Custom API (OpenAI-compatible) endpoint.
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/custom_endpoint',
        get_string('setting:custom_endpoint', 'block_aipromptgen'),
        get_string('setting:custom_endpoint_desc', 'block_aipromptgen'),
        '',
        PARAM_URL
    ));

    // Custom API key (optional).
    $settings->add(new admin_setting_configpasswordunmask(
        'block_aipromptgen/custom_apikey',
        get_string('setting:custom_apikey', 'block_aipromptgen'),
        get_string('setting:custom_apikey_desc', 'block_aipromptgen'),
        ''
    ));

    // Custom API model name.
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/custom_model',
        get_string('setting:custom_model', 'block_aipromptgen'),
        get_string('setting:custom_model_desc', 'block_aipromptgen'),
        '',
        PARAM_TEXT
    ));

    // Ollama endpoint.
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/ollama_endpoint',
        get_string('setting:ollama_endpoint', 'block_aipromptgen'),
        get_string('setting:ollama_endpoint_desc', 'block_aipromptgen'),
        'http://localhost:11434',
        PARAM_URL
    ));

    // Ollama model (allow dots/colons via PARAM_TEXT).
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/ollama_model',
        get_string('setting:ollama_model', 'block_aipromptgen'),
        get_string('setting:ollama_model_desc', 'block_aipromptgen'),
        'llama3',
        PARAM_TEXT // Allow dots/colons.
    ));

    // Optional JSON Schema for structured output.
    $settings->add(new admin_setting_configtextarea(
        'block_aipromptgen/ollama_schema',
        get_string('setting:ollama_schema', 'block_aipromptgen'),
        get_string('setting:ollama_schema_desc', 'block_aipromptgen'),
        ''
    ));

    // Max tokens (num_predict).
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/ollama_num_predict',
        get_string('setting:ollama_num_predict', 'block_aipromptgen'),
        get_string('setting:ollama_num_predict_desc', 'block_aipromptgen'),
        512,
        PARAM_INT
    ));

    // Timeout seconds.
    $settings->add(new admin_setting_configtext(
        'block_aipromptgen/ollama_timeout',
        get_string('setting:ollama_timeout', 'block_aipromptgen'),
        get_string('setting:ollama_timeout_desc', 'block_aipromptgen'),
        90,
        PARAM_INT
    ));
}
