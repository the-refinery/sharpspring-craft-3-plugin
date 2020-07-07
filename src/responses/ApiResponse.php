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

class ApiResponse
{
  public $data;

  public function __construct($data) {
    $this->data = $data;
  }

  public function getResult() {
    return $this->data["result"];
  }

  public function getRequestId() {
    return $this->data["id"];
  }

  public function getError() {
    return $this->data["error"];
  }

  public function hasErrors() {
    return (
      isset($this->data['error']) &&
      (count($this->data['error']) > 0)
    );
  }

  public function getErrorMessage() {
    if($this->hasErrors()) {
      return $this->data['error']['message'];
    }

    return null;
  }
}