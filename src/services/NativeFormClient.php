<?php
/**
 * Sharpspring Integration plugin for Craft CMS 3.x
 *
 * A SharpSpring integration plugin.
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2020 The Refinery
 */

namespace therefinery\sharpspringintegration\services;

use therefinery\sharpspringintegration\SharpspringIntegration;

use Craft;
use craft\base\Component;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use therefinery\sharpspringintegration\responses\NativeFormResponse;

/**
 * NativeFormClient Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    The Refinery
 * @package   SharpspringIntegration
 * @since     3.0.0
 */
class NativeFormClient extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     SharpspringIntegration::$plugin->nativeFormClient->exampleService()
     *
     * @return mixed
     */
     public function postData($data, $endpointUrl) {
        if(!isset($endpointUrl)) {
            throw new Exception('Native Form Endpoint url is blank. Cannot proceed. Check your config/sharpspringintegration.php file.');
        }

        $client = new Client();

        try {
            foreach ($data as $key => $value)  {
                if($value === true) {
                    $data[$key] = "True";
                }
                if($value === false) {
                    $data[$key] = "False";
                }
            }
            $ssResponse = $client->request('GET', $endpointUrl."/jsonp", ['query' => $data]);

            $response = new NativeFormResponse((string) $ssResponse->getBody());

        } catch (\Exception $e) {
            Craft::Error(
                "There was an issue posting to SharpSpring Native Form endpoint:\n\n".$e->getMessage(),
                'sharpspring-integration'
            );
            throw $e;
        }

        return $response;
    }
}
