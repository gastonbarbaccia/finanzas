<?php

return [
    'default' => env('AI_DEFAULT_PROVIDER', 'gemini'),
    'timeout' => (int) env('AI_TIMEOUT', 60),

    'providers' => [
        'gemini' => [
            'label' => 'Google Gemini',
            'key'   => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'url'   => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],
        'openai' => [
            'label' => 'OpenAI',
            'key'   => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            // Permite usar servicios compatibles con la API de OpenAI (por ejemplo OpenRouter)
            'url'   => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],
        'claude' => [
            'label' => 'Anthropic Claude',
            'key'   => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5-5'),
            'url'   => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        ],
    ],
];
