<?php
/**
 * Sharpspring Integration plugin for Craft CMS 3.x
 *
 * A SharpSpring integration plugin.
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2020 The Refinery
 */

namespace therefinery\sharpspringintegration\responses;

class NativeFormResponse
{
  public $data;

  public function __construct($data) {
    $this->data = $data;
  }

  public function getError() {
    return $this->getErrorMessage();
  }

  public function hasErrors() {
    $test = "heh";

    $matchResult = preg_match('/_ss_noform.success = ([^;]*);/', $this->data, $matches);

    if($matchResult === 0 || $matchResult === false) {
      SharpspringIntegrationPlugin::log("There was an issue determining errors from sharpspring native form post:\n\n".$this->data, LogLevel::Warning, true);
      return false;
    }

    // "_ss_noform.success = false;" means failure
    // "_ss_noform.success = true;" means success

    return $matches[1] == "false";
  }

  public function getErrorMessage() {
    if($this->hasErrors()) {
      $matchResult = preg_match('/_ss_noform.error = ([^;]*);/', $this->data, $matches);

      if($matchResult === 0 || $matchResult === false) {
        SharpspringIntegrationPlugin::log("There was an issue extracting error message from native form post:\n\n".$this->data, LogLevel::Warning, true);
        return null;
      }

      return $matches[1];
    }

    return null;
  }
}