// src/Service/ImageConverterService.php
<?php

namespace Drupal\openai_layout_converter\Service;

use Drupal\file\Entity\File;
use Psr\Log\LoggerInterface;

class ImageConverterService {

  protected $imageAnalyzer;
  protected $templateGenerator;
  protected $logger;

  public function __construct(
    OpenAiImageAnalyzer $analyzer,
    LayoutTemplateGenerator $generator,
    LoggerInterface $logger
  ) {
    $this->imageAnalyzer = $analyzer;
    $this->templateGenerator = $generator;
    $this->logger = $logger;
  }

  public function convertImage($fileId, $depth = 'detailed', $generatePreview = true) {
    try {
      $file = File::load($fileId);

      if (!$file) {
        throw new \Exception('File not found: ' . $fileId);
      }

      $this->logger->info('Starting image conversion for file @id', ['@id' => $fileId]);

      $imageUrl = $file->createFileUrl(false);

      // Analyze image with OpenAI
      $analysis = $this->imageAnalyzer->analyzeImage($imageUrl, $depth);

      // Generate layout template
      $template = $this->templateGenerator->generateTemplate($analysis);

      // Generate HTML preview if requested
      if ($generatePreview) {
        $template['html_preview'] = $this->generateHTMLPreview($template);
      }

      $this->logger->info('Successfully converted image @id to layout', ['@id' => $fileId]);

      return $template;

    } catch (\Exception $e) {
      $this->logger->error('Image conversion error: @message', ['@message' => $e->getMessage()]);
      throw $e;
    }
  }

  protected function generateHTMLPreview($template) {
    $html = "<!DOCTYPE html>\n<html>\n<head>\n";
    $html .= "<meta charset='UTF-8'>\n";
    $html .= "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
    $html .= "<title>" . htmlspecialchars($template['label']) . "</title>\n";
    $html .= "<style>\n" . $template['css'] . "\n</style>\n";
    $html .= "</head>\n<body>\n";

    foreach ($template['sections'] as $section) {
      $html .= "<section class='section-" . htmlspecialchars($section['id']) . "'>\n";
      $html .= "<h2>" . htmlspecialchars($section['label']) . "</h2>\n";
      $html .= "<p>Content area for " . htmlspecialchars($section['type']) . "</p>\n";
      $html .= "</section>\n";
    }

    $html .= "</body>\n</html>";

    return $html;
  }
}
