// src/Service/OpenAiImageAnalyzer.php
<?php

namespace Drupal\openai_layout_converter\Service;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

class OpenAiImageAnalyzer {

  protected $httpClient;
  protected $configFactory;
  protected $logger;

  public function __construct(Client $httpClient, ConfigFactoryInterface $configFactory, LoggerInterface $logger) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  public function analyzeImage($imageUrl, $depth = 'detailed') {
    $config = $this->configFactory->get('openai_layout_converter.settings');
    $apiKey = $config->get('openai_api_key');
    $model = $config->get('openai_model') ?: 'gpt-4o';
    $maxTokens = $config->get('max_tokens') ?: 2000;

    if (!$apiKey) {
      throw new \Exception('OpenAI API key not configured. Please visit admin settings.');
    }

    try {
      $prompt = $this->getPromptForDepth($depth);

      $this->logger->info('Sending image to OpenAI for analysis', ['url' => $imageUrl, 'model' => $model]);

      $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'messages' => [
            [
              'role' => 'user',
              'content' => [
                [
                  'type' => 'text',
                  'text' => $prompt,
                ],
                [
                  'type' => 'image_url',
                  'image_url' => [
                    'url' => $imageUrl,
                    'detail' => 'high',
                  ],
                ],
              ],
            ],
          ],
          'max_tokens' => $maxTokens,
          'temperature' => 0.7,
        ],
      ]);

      $data = json_decode($response->getBody(), true);

      if (isset($data['error'])) {
        throw new \Exception('OpenAI API Error: ' . $data['error']['message']);
      }

      return $data['choices'][0]['message']['content'];

    } catch (\Exception $e) {
      $this->logger->error('OpenAI API error: @message', ['@message' => $e->getMessage()]);
      throw $e;
    }
  }

  protected function getPromptForDepth($depth) {
    $basePrompt = <<<PROMPT
Analyze this image and create a detailed description of its layout structure.

Identify the following:
1. Main sections/containers and their hierarchy
2. Content areas and their arrangement
3. Grid structure (columns, rows)
4. Spacing and alignment patterns

PROMPT;

    $jsonFormat = <<<JSON
Provide the response in valid JSON format:
{
  "layout": {
    "type": "responsive|fixed|fluid",
    "max_width": "pixels or percentage",
    "sections": [
      {
        "id": "section_name",
        "type": "header|hero|content|sidebar|footer|custom",
        "width": "full|half|third|quarter|custom",
        "height": "auto|fixed pixels",
        "layout_type": "single|two-col|three-col|grid",
        "background": {
          "color": "hex color",
          "image": "yes/no",
          "gradient": "yes/no"
        },
        "padding": "top right bottom left in pixels/rem",
        "margin": "top right bottom left in pixels/rem",
        "content_description": "brief description of content",
        "child_sections": []
      }
    ]
  },
  "colors": {
    "primary": "hex color",
    "secondary": "hex color",
    "accent": "hex color",
    "neutral": ["hex colors for grays/neutrals"],
    "text": "hex color"
  },
  "typography": {
    "heading": "font-family, size, weight",
    "body": "font-family, size, weight",
    "notes": "any special typography"
  },
  "spacing": {
    "base_unit": "pixels (usually 8, 10, or 16)",
    "gutter": "spacing between columns",
    "padding": "standard padding"
  },
  "responsive_notes": "notes about responsive behavior",
  "observations": "additional important notes"
}
JSON;

    switch ($depth) {
      case 'basic':
        return $basePrompt . 'Focus on: overall layout structure, main sections, colors. ' . $jsonFormat;

      case 'comprehensive':
        return $basePrompt . 'Provide extremely detailed analysis including: exact spacing, typography details, color psychology, interaction patterns, and responsive behavior. ' . $jsonFormat;

      case 'detailed':
      default:
        return $basePrompt . 'Include colors, typography, spacing details, and responsive considerations. ' . $jsonFormat;
    }
  }
}
