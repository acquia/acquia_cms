<?php

/**
 * @file
 * Lists available colors and color schemes for the Acquia Claro theme.
 */

[
  // Available colors and color labels used in theme.
  'fields' => [
    'contentheader' => t('Header background'),
    'pagetitle' => t('Page Title'),
    'base' => t('Body background'),
    'text' => t('Text color'),
    'link' => t('Link color'),
    'primarytabs' => t('Primary Tabs'),
    'primarytabshover' => t('Primary Tabs Hover'),
    'primarybuttonhover' => t('Button Hover'),
    'focus' => t('Focus color'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Acquia (default)'),
      'colors' => [
        'contentheader' => '#0e68a7',
        'pagetitle' => '#ffffff',
        'base' => '#fff',
        'text' => '#222330',
        'link' => '#168ec4',
        'primarytabs' => '#cccccc',
        'primarytabshover' => '#f3f4f9',
        'primarybuttonhover' => '#096b92',
        'focus' => '#26a769',
      ],
    ],
    'green' => [
      'title' => t('Green'),
      'colors' => [
        'contentheader' => '#008d52',
        'pagetitle' => '#ffffff',
        'text' => '#222330',
        'base' => '#fff',
        'link' => '#008d51',
        'primarytabs' => '#cccccc',
        'primarytabshover' => '#d1ffec',
        'primarybuttonhover' => '#007c48',
        'focus' => '#26a769',
      ],
    ],
    'red' => [
      'title' => t('Red'),
      'colors' => [
        'contentheader' => '#cd2d2d',
        'pagetitle' => '#ffffff',
        'base' => '#fff',
        'text' => '#222330',
        'link' => '#cd2d2c',
        'primarytabs' => '#cccccc',
        'primarytabshover' => '#f8dfdf',
        'primarybuttonhover' => '#bf2a2a',
        'focus' => '#cd2d2d',
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'css/acquia_claro_colors.css',
  ],

  // Preview files.
  'preview_library' => 'acquia_claro/color.preview',
  'preview_html' => 'color/preview.html',

];
