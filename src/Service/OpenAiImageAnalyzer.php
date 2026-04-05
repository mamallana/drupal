<?php

namespace Drupal\openai_layout_converter\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException; 
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

class OpenAiImageAnalyzer {
    private $httpClient;

    public function __construct() {
        $this->httpClient = new Client(['timeout' => 30]); // Set timeout to 30 seconds
    }

    public function analyzeImage($image) {
        try {
            // Your existing code for analyzing image
        } catch (NotEncodableValueException $e) {
            // Handle JSON validation error
            return ['error' => 'Invalid JSON input: ' . $e->getMessage()];
        } catch (GuzzleException $e) {
            // Handle HTTP request errors
            return ['error' => 'HTTP request failed: ' . $e->getMessage()];
        } catch (\Exception $e) {
            // Handle other exceptions
            return ['error' => 'An unexpected error occurred: ' . $e->getMessage()];
        }
    }
}