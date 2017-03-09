<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a mapping element.
 *
 * @FormElement("webform_mapping")
 */
class WebformMapping extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformMapping'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#required' => FALSE,
      '#source' => [],
      '#destination' => [],
      '#arrow' => '→',
    ];
  }

  /**
   * Processes a likert scale webform element.
   */
  public static function processWebformMapping(&$element, FormStateInterface $form_state, &$complete_form) {
    // Set translated default properties.
    $element += [
      '#source__title' => t('Source'),
      '#destination__title' => t('Destination'),
      '#arrow' => '→',
    ];

    $arrow = htmlentities($element['#arrow']);

    // Setup destination__type depending if #destination is defined.
    if (empty($element['#destination__type'])) {
      $element['#destination__type'] = (empty($element['#destination'])) ? 'textfield' : 'select';
    }

    // Set base destination element.
    $destination_element_base = [
      '#title_display' => 'invisible',
      '#required' => $element['#required'],
    ];
    // Get base #destination__* properties.
    foreach ($element as $element_key => $element_value) {
      if (strpos($element_key, '#destination__') === 0 && !in_array($element_key, ['#destination__title'])) {
        $destination_element_base[str_replace('#destination__', '#', $element_key)] = $element_value;
      }
    }

    // Build header.
    $header = [
      ['data' => ['#markup' => $element['#source__title']  . ' ' . $arrow], 'width' => '50%'],
      ['data' => ['#markup' => $element['#destination__title']], 'width' => '50%'],
    ];

    // Build rows.
    $rows = [];
    foreach ($element['#source'] as $source_key => $source_title) {
      $default_value = (isset($element['#default_value'][$source_key])) ? $element['#default_value'][$source_key] : NULL;
      $destination_element = $destination_element_base + [
        '#title' => $source_title,
        '#required' => $element['#required'],
        '#default_value' => $default_value,
        '#parents' => [$element['#name'], $source_key],
      ];

      switch ($element['#destination__type']) {
        case 'select':
        case 'webform_select_other':
          $destination_element += [
            '#empty_value' => '',
            '#options' => $element['#destination'],
          ];
          break;
      }

      $rows[$source_key] = [
        'source' => ['#markup' => $source_title . ' ' . $arrow],
        $source_key => $destination_element,
      ];
    }

    $element['table'] = [
      '#tree' => TRUE,
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => [
        'class' => ['webform-mapping-table'],
      ],
    ] + $rows;

    $element['#element_validate'] = [[get_called_class(), 'validateWebformMapping']];

    return $element;
  }


  /**
   * Validates a mapping element.
   */
  public static function validateWebformMapping(&$element, FormStateInterface $form_state, &$complete_form) {
    $values = NestedArray::getValue($form_state->getValues(), $element['#parents']);
    $form_state->setValueForElement($element, array_filter($values));
  }

}
