// src/Controller/ImageConverterController.php
<?php

namespace Drupal\openai_layout_converter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openai_layout_converter\Service\ImageConverterService;
use Symfony\Component\HttpFoundation\JsonResponse;

class ImageConverterController extends ControllerBase {

  protected $converterService;

  public function __construct(ImageConverterService $converterService) {
    $this->converterService = $converterService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openai_layout_converter.converter')
    );
  }

  public function convertImage($fileId) {
    try {
      $template = $this->converterService->convertImage($fileId, 'detailed', true);
      return new JsonResponse($template);
    } catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 400);
    }
  }
}
