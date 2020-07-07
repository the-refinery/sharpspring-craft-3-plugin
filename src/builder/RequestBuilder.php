<?php
/**
 * Sharpspring Integration plugin for Craft CMS 3.x
 *
 * A SharpSpring integration plugin.
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2020 The Refinery
 */

namespace therefinery\sharpspringintegration\builders;

class RequestBuilder
{
  public $apiMethod;
  public $requestId;
  public $params = [];

  public function withApiMethod($apiMethod) {
    $this->apiMethod = $apiMethod;
    return $this;
  }

  public function withRequestId($requestId) {
    $this->requestId = $requestId;
    return $this;
  }

  public function pushParam($paramArray) {
    $this->params = array_merge($this->params, $paramArray);
    return $this;
  }

  public function toJson() {
    if(!$this->requestId) {
      $this->requestId = (string) round(microtime(true) * 1000);
    }

    if(!$this->apiMethod) {
      throw new Exception("SharpSpring Request Builder requires an apiMethod to be set. Please see #withApiMethod");
    }

    if(empty($this->params)) {
      throw new Exception("SharpSpring Request Builder requires a non-empty params set. Please see #pushParam");
    }

    return json_encode(
      array(
        "method" => $this->apiMethod,
        "id" => $this->requestId,
        "params" => $this->params
      )
    );
  }
}