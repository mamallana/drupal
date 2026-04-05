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
  
  public function analyzeImage($imageUrl) {
    $config = $this->configFactory->get('openai_layout_converter.settings');
    $apiKey = $config->get('openai_api_key');
    
    if (!$apiKey) {
      throw new \Exception('OpenAI API key not configured');
    }
    
    try {
      $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => 'gpt-4-vision',
          'messages' => [
            [
              'role' => 'user',
              'content' => [
                [
                  'type' => 'text',
                  'text' => $this->getPrompt(),
                ],
                [
                  'type' => 'image_url',
                  'image_url' => [
                    'url' => $imageUrl,
                  ],
                ],
              ],
            ],
          ],
          'max_tokens' => 2000,
        ],
      ]);
      
      $data = json_decode($response->getBody(), true);
      return $data['choices'][0]['message']['content'];
      
    } catch (\Exception $e) {
      $this->logger->error('OpenAI API error: @message', ['@message' => $e->getMessage()]);
      throw $e;
    }
  }
  
  protected function getPrompt() {
    return <<<PROMPT
Analyze this image and create a detailed description of its layout structure. 
Identify:
1. Main sections/containers
2. Content areas and their arrangement
3. Colors and styling
4. Typography hierarchy
5. Spacing and alignment

Provide the response in JSON format with the following structure:
{
  "sections": [
    {
      "name": "section name",
      "type": "header|content|sidebar|footer",
      "width": "full|half|third",
      "backgroundColor": "hex color",
      "padding": "spacing value",
      "content": "description of content"
    }
  ],
  "colors": ["color palette"],
  "typography": {"heading": "font details", "body": "font details"},
  "notes": "additional layout observations"
}
PROMPT;
  }
}
