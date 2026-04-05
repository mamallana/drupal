// src/Service/LayoutTemplateGenerator.php
<?php

namespace Drupal\openai_layout_converter\Service;

class LayoutTemplateGenerator {
  
  public function generateTemplate($analysisJson) {
    $analysis = json_decode($analysisJson, true);
    
    $template = [
      'label' => 'Generated Layout',
      'id' => 'generated_layout_' . time(),
      'sections' => [],
      'css' => $this->generateCSS($analysis),
    ];
    
    if (!empty($analysis['sections'])) {
      foreach ($analysis['sections'] as $section) {
        $template['sections'][] = $this->createLayoutSection($section);
      }
    }
    
    return $template;
  }
  
  protected function createLayoutSection($sectionData) {
    return [
      'layout_id' => $this->getLayoutType($sectionData['width']),
      'label' => $sectionData['name'] ?? 'Section',
      'uuid' => $this->generateUuid(),
      'region_data' => [
        'content' => [
          [
            'type' => 'inline_block:basic',
            'uuid' => $this->generateUuid(),
            'settings' => [
              'label_display' => 0,
              'view_mode' => 'full',
            ],
            'attributes' => [
              'class' => ['layout-section-' . strtolower($sectionData['type'])],
            ],
          ],
        ],
      ],
    ];
  }
  
  protected function generateCSS($analysis) {
    $css = ":root {\n";
    
    if (!empty($analysis['colors'])) {
      foreach ($analysis['colors'] as $index => $color) {
        $css .= "  --color-{$index}: {$color};\n";
      }
    }
    
    $css .= "}\n\n";
    
    // Layout section styles
    $css .= ".layout-section-header { background-color: var(--color-0); padding: 2rem; }\n";
    $css .= ".layout-section-content { padding: 2rem; }\n";
    $css .= ".layout-section-sidebar { width: 300px; }\n";
    $css .= ".layout-section-footer { background-color: var(--color-0); padding: 2rem; margin-top: auto; }\n";
    
    // Typography
    if (!empty($analysis['typography'])) {
      $css .= "\nh1, h2, h3 { font-family: " . ($analysis['typography']['heading'] ?? 'sans-serif') . "; }\n";
      $css .= "body, p { font-family: " . ($analysis['typography']['body'] ?? 'sans-serif') . "; }\n";
    }
    
    return $css;
  }
  
  protected function getLayoutType($width) {
    return match($width) {
      'full' => 'layout_onecol',
      'half' => 'layout_twocol',
      'third' => 'layout_threecol',
      default => 'layout_onecol',
    };
  }
  
  protected function generateUuid() {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
  }
}
