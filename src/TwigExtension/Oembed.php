<?php

namespace Drupal\event_database_pull\TwigExtension;

/**
 *
 */
class Oembed extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('oembed', [$this, 'oembed']),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'oembed.twig_extension';
  }

  /**
   * Replaces all numbers from the string.
   */
  public static function oembed($url) {
    $oembed_endpoint = '';
    $json_url = '';
    $output = FALSE;
    if (strpos($url, 'youtube') !== FALSE) {
      $oembed_endpoint = 'http://www.youtube.com/oembed';
      $json_url = $oembed_endpoint . '?url=' . rawurlencode($url) . '&format=json';
    }
    if (strpos($url, 'vimeo') !== FALSE) {
      $oembed_endpoint = 'http://vimeo.com/api/oembed';
      $json_url = $oembed_endpoint . '.json?url=' . rawurlencode($url);
    }

    if ($url && $oembed_endpoint) {
      $curl = curl_init($json_url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_TIMEOUT, 30);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
      $output = json_decode(curl_exec($curl));
      curl_close($curl);
    }
    return ($output) ? html_entity_decode($output->html) : '';
  }

}
