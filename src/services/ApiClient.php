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
use therefinery\sharpspringintegration\builders\ApiClientBuilder as ApiClientBuilder;
use therefinery\sharpspringintegration\builders\RequestBuilder as RequestBuilder;

/**
 * ApiClient Service
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
class ApiClient extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     SharpspringIntegration::$plugin->apiClient->exampleService()
     *
     * @return mixed
     */
    public function build() {
        return new ApiClientBuilder();
    }

    public function upsertSingleLead($data, $credentialSet, $apiVersion) {
        $getLeadsRequest = (new RequestBuilder())
            ->withApiMethod("getLeads")
            ->pushParam(
                array(
                    "where" => array(
                        "emailAddress" => $data["emailAddress"]
                    )
                )
            );

            $response = SharpspringIntegration::$plugin
                ->apiClient
                ->build()
                ->withCredentialSet($credentialSet)
                ->withRequest($getLeadsRequest)
                ->submit();

            $leadId = null;

            if($response->getResult()["lead"] && count($response->getResult()["lead"]) > 0) {
                $leadId = $response->getResult()["lead"][0]["id"];
            }

            if($leadId) {
                $apiMethod = "updateLeads";
                $leadsParams = array(
                    "objects" => array(
                        array_merge(
                            $data,
                            array("id" => $leadId)
                        )
                    )
                );
            } else {
                $apiMethod = "createLeads";
                $leadsParams = array(
                    "objects" => array(
                        $data
                    )
                );
            }

            $leadsRequest = (new RequestBuilder())
                ->withApiMethod($apiMethod)
                ->pushParam(
                    $leadsParams
                );

            $response = SharpspringIntegration::$plugin
                ->apiClient
                ->build()
                ->withCredentialSet($credentialSet)
                ->withRequest($leadsRequest)
                ->submit();

            return $response;
    }
}
