// src/Service/LayoutTemplateGenerator.php
<?php

namespace Drupal\openai_layout_converter\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class LayoutTemplateGenerator {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function generateTemplate($analysisJson) {
    $analysis = json_decode($analysisJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception('Invalid JSON from OpenAI: ' . json_last_error_msg());
    }

    $template = [
      'label' => 'Generated Layout - ' . date('Y-m-d H:i'),
      'id' => 'layout_' . time(),
      'sections' => [],
      'css' => $this->generateCSS($analysis),
      'metadata' => $this->extractMetadata($analysis),
    ];

    if (!empty($analysis['layout']['sections'])) {
      foreach ($analysis['layout']['sections'] as $section) {
        $template['sections'][] = $this->createLayoutSection($section);
      }
    }

    return $template;
  }

  protected function createLayoutSection($sectionData) {
    return [
      'id' => $sectionData['id'] ?? 'section_' . uniqid(),
      'type' => $sectionData['type'] ?? 'content',
      'label' => ucfirst(str_replace('_', ' ', $sectionData['id'] ?? 'Section')),
      'layout_id' => $this->getLayoutType($sectionData['layout_type'] ?? 'single'),
      'width' => $sectionData['width'] ?? 'full',
      'styling' => [
        'background_color' => $sectionData['background']['color'] ?? NULL,
        'padding' => $sectionData['padding'] ?? '20px',
        'margin' => $sectionData['margin'] ?? '0',
      ],
      'regions' => $this->generateRegions($sectionData),
    ];
  }

  protected function generateRegions($sectionData) {
    $layoutType = $sectionData['layout_type'] ?? 'single';

    $regions = match($layoutType) {
      'two-col' => ['left', 'right'],
      'three-col' => ['left', 'center', 'right'],
      'grid' => ['content'],
      default => ['content'],
    };

    $result = [];
    foreach ($regions as $region) {
      $result[$region] = [
        'label' => ucfirst($region),
        'blocks' => [],
      ];
    }

    return $result;
  }

  protected function generateCSS($analysis) {
    $css = ":root {\n";

    // Color variables
    if (!empty($analysis['colors'])) {
      $colors = $analysis['colors'];
      $css .= "  --color-primary: " . ($colors['primary'] ?? '#333333') . ";\n";
      $css .= "  --color-secondary: " . ($colors['secondary'] ?? '#666666') . ";\n";
      $css .= "  --color-accent: " . ($colors['accent'] ?? '#0066cc') . ";\n";
      $css .= "  --color-text: " . ($colors['text'] ?? '#000000') . ";\n";

      if (!empty($colors['neutral'])) {
        foreach ($colors['neutral'] as $index => $color) {
          $css .= "  --color-neutral-{$index}: {$color};\n";
        }
      }
    }

    // Spacing variables
    if (!empty($analysis['spacing'])) {
      $baseUnit = $analysis['spacing']['base_unit'] ?? '16px';
      $css .= "  --spacing-base: {$baseUnit};\n";
      $css .= "  --spacing-xs: calc(var(--spacing-base) * 0.25);\n";
      $css .= "  --spacing-sm: calc(var(--spacing-base) * 0.5);\n";
      $css .= "  --spacing-md: var(--spacing-base);\n";
      $css .= "  --spacing-lg: calc(var(--spacing-base) * 1.5);\n";
      $css .= "  --spacing-xl: calc(var(--spacing-base) * 2);\n";
      $css .= "  --spacing-xxl: calc(var(--spacing-base) * 3);\n";
    }

    $css .= "}\n\n";

    // Typography
    $css .= $this->generateTypographyCSS($analysis);

    // Layout sections
    $css .= $this->generateLayoutCSS($analysis);

    // Responsive
    $css .= $this->generateResponsiveCSS();

    return $css;
  }

  protected function generateTypographyCSS($analysis) {
    $css = "/* Typography */\n";

    if (!empty($analysis['typography'])) {
      $typography = $analysis['typography'];

      if (!empty($typography['heading'])) {
        $css .= "h1, h2, h3, h4, h5, h6 {\n";
        $css .= "  font-family: " . $typography['heading'] . ";\n";
        $css .= "  font-weight: 600;\n";
        $css .= "  line-height: 1.2;\n";
        $css .= "}\n";
      }

      if (!empty($typography['body'])) {
        $css .= "body, p, span {\n";
        $css .= "  font-family: " . $typography['body'] . ";\n";
        $css .= "  line-height: 1.6;\n";
        $css .= "  color: var(--color-text);\n";
        $css .= "}\n";
      }
    }

    $css .= "\n";
    return $css;
  }

  protected function generateLayoutCSS($analysis) {
    $css = "/* Layout Sections */\n";

    if (!empty($analysis['layout']['sections'])) {
      foreach ($analysis['layout']['sections'] as $section) {
        $sectionId = $section['id'] ?? 'section';
        $css .= ".section-{$sectionId} {\n";

        if (!empty($section['background']['color'])) {
          $css .= "  background-color: " . $section['background']['color'] . ";\n";
        }

        if (!empty($section['padding'])) {
          $css .= "  padding: " . $section['padding'] . ";\n";
        }

        if (!empty($section['margin'])) {
          $css .= "  margin: " . $section['margin'] . ";\n";
        }

        $css .= "}\n\n";
      }
    }

    return $css;
  }

  protected function generateResponsiveCSS() {
    $css = "/* Responsive Design */\n";
    $css .= "@media (max-width: 768px) {\n";
    $css .= "  .layout-twocol, .layout-threecol {\n";
    $css .= "    display: flex;\n";
    $css .= "    flex-direction: column;\n";
    $css .= "  }\n";
    $css .= "  .layout-twocol > div, .layout-threecol > div {\n";
    $css .= "    width: 100% !important;\n";
    $css .= "  }\n";
    $css .= "}\n";

    return $css;
  }

  protected function getLayoutType($layoutType) {
    return match($layoutType) {
      'two-col' => 'layout_twocol',
      'three-col' => 'layout_threecol',
      'grid' => 'layout_threecol',
      default => 'layout_onecol',
    };
  }

  protected function extractMetadata($analysis) {
    return [
      'layout_type' => $analysis['layout']['type'] ?? 'responsive',
      'max_width' => $analysis['layout']['max_width'] ?? 'auto',
      'has_sidebar' => $this->hasSidebar($analysis),
      'has_hero' => $this->hasSection($analysis, 'hero'),
      'color_scheme' => $analysis['colors'] ?? [],
    ];
  }

  protected function hasSidebar($analysis) {
    if (empty($analysis['layout']['sections'])) {
      return false;
    }

    foreach ($analysis['layout']['sections'] as $section) {
      if ($section['type'] === 'sidebar') {
        return true;
      }
    }

    return false;
  }

  protected function hasSection($analysis, $type) {
    if (empty($analysis['layout']['sections'])) {
      return false;
    }

    foreach ($analysis['layout']['sections'] as $section) {
      if ($section['type'] === $type) {
        return true;
      }
    }

    return false;
  }
}
