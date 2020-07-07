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

use Craft;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Craft\LogLevel;
use therefinery\sharpspringintegration\SharpspringIntegration;
use therefinery\sharpspringintegration\responses\ApiResponse;

class ApiClientBuilder
{
  public $apiVersion = "1.2";
  public $credentialSet = "*";
  public $request;

  public function withApiVersion($version) {
    $this->apiVersion = $version;
    return $this;
  }

  public function withCredentialSet($credentialSet) {
    $this->credentialSet = $credentialSet;
    return $this;
  }

  public function withRequest($request) {
    $this->request = $request;
    return $this;
  }

  public function submit() {
    $allCredentials = include (Craft::$app->config->configDir . '/sharpspringintegration.php');
    $allCredentials = $allCredentials['*']['credentialSets'];
    $useCredentials = $allCredentials[$this->credentialSet];

    if(!array_key_exists('accountID', $useCredentials)) {
      throw new \Exception("ERROR: SharpSpring credentialSets for '".$this->credentialSet."' does not have accountID set. Please refer to the documention on how to set these up.");
    }

    if(!array_key_exists('secretKey', $useCredentials)) {
      throw new \Exception("ERROR: SharpSpring credentialSets for '".$this->credentialSet."' does not have secretKey set. Please refer to the documention on how to set these up.");
    }

    $client = new Client();
    $uri = $this->getApiRootUrl() . '?accountID=' . $useCredentials['accountID'] . '&secretKey=' . $useCredentials['secretKey'];

    try {
      $response = $client->request('POST', $uri, ['body' => $this->request->toJson()]);

      $body = $response->getBody(true);
      $data = json_decode($body, true);

    } catch (\Exception $e) {
      Craft::Error(
        "There was an issue obtaining/parsing data from SharpSpring's API:\n\n".$e->getMessage(),
        'sharpspring-integration'
      );
      throw $e;
    }

    return new ApiResponse($data);
  }

  private function getApiRootUrl() {
    return 'https://api.sharpspring.com/pubapi/v'.$this->apiVersion.'/';
  }
}