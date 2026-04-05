// src/Controller/ImageConverterController.php
<?php

namespace Drupal\openai_layout_converter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openai_layout_converter\Service\OpenAiImageAnalyzer;
use Drupal\openai_layout_converter\Service\LayoutTemplateGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;

class ImageConverterController extends ControllerBase {
  
  protected $imageAnalyzer;
  protected $templateGenerator;
  
  public function __construct(OpenAiImageAnalyzer $analyzer, LayoutTemplateGenerator $generator) {
    $this->imageAnalyzer = $analyzer;
    $this->templateGenerator = $generator;
  }
  
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openai_layout_converter.image_analyzer'),
      $container->get('openai_layout_converter.template_generator')
    );
  }
  
  public function convertImage($fileId) {
    try {
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($fileId);
      
      if (!$file) {
        return new JsonResponse(['error' => 'File not found'], 404);
      }
      
      $imageUrl = $file->createFileUrl(false);
      $analysis = $this->imageAnalyzer->analyzeImage($imageUrl);
      $template = $this->templateGenerator->generateTemplate($analysis);
      
      return new JsonResponse($template);
      
    } catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }
}
