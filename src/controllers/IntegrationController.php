<?php
/**
 * Sharpspring Integration plugin for Craft CMS 3.x
 *
 * A SharpSpring integration plugin.
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2020 The Refinery
 */

namespace therefinery\sharpspringintegration\controllers;

use therefinery\sharpspringintegration\SharpspringIntegration;

use Craft;
use craft\web\Controller;

/**
 * Integration Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    The Refinery
 * @package   SharpspringIntegration
 * @since     3.0.0
 */
class IntegrationController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['actionIndex', 'actionPushAsync'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/sharpspring-integration/integration
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $result = 'Welcome to the IntegrationController actionIndex() method';

        return $result;
    }

    /**
     * Handle a request going to our plugin's actionPushAsync URL,
     * e.g.: actions/sharpspring-integration/integration/push-async
     *
     * @return mixed
     */
    public function actionPushAsync()
    {
        $result = 'Welcome to the IntegrationController actionPushAsync() method';
        $this->requirePostRequest();
        $data = json_decode(Craft::$app->request->getRawBody(), true);


        // Require a "mapping" key in the JSON body
        if(!array_key_exists("mapping", $data))
        {
            Craft::$app->response->headers->set('status', 400);
            $this->asJson(
                array(
                    "status" => "error",
                    "errors" => array(
                        array("detail" => "Key 'mapping' must be supplied.")
                    )
                )
            );
        }

        // Always look in the custom mappings for any AJAX-based calls
        $config = include (Craft::$app->config->configDir . '/sharpspringintegration.php');

        if(!$config)
        {
            Craft::$app->response->headers->set('status', 400);
            $this->asJson(
                array(
                    "status" => "error",
                    "errors" => array(
                        array(
                            "detail" =>
                                "Configuration not found. Please check if the appropriate integration files are set up properly."
                        )
                    )
                )
            );
        }

        // If custom mapping key is not found in configuration, send back an error
        if(!array_key_exists($data["mapping"], $config))
        {
            Craft::$app->response->headers->set('status', 400);
            $this->asJson(
                array(
                    "status" => "error",
                    "errors" => array(
                        array(
                            "detail" =>
                                "Mapping for '{$data["mapping"]}' not found. Please check if the appropriate integration files are set up properly."
                        )
                    )
                )
            );
        }

        $configMapping = SharpspringIntegration::$plugin
            ->mappingConfig
            ->getCustomMapping($data["mapping"]);

        $sharpSpringData = [];

        // Always read from the custom mappings.
        // Example of incoming async request (read via POST body as a JSON String):
        // {
        //     "mapping": "someCustomMapping",
        //     "data": [
        //          {
        //             "email": "someone@somewhere.com",
        //             "iWantToSubscribe": true
        //          },
        //     ]
        // }

        if(array_key_exists("data", $data)) {
            // First iteration of this plugin only expects one data point.
            // It should later include the ability to push multiple data points in one call.
            foreach($data["data"][0] as $key => $value) {
                if(array_key_exists($key, $configMapping["map"])) {
                    $sharpSpringData[$configMapping["map"][$key]] = $value;
                } else {
                    Craft::warning(
                        "WARNING: incoming async data key #{$key} does not have an associated mapping for configuration {$data['mapping']}",
                        'sharpspring-integration'
                    );
                }
            }

            try {
                $response = SharpspringIntegration::$plugin
                    ->apiClient
                    ->upsertSingleLead(
                        $sharpSpringData,
                        $configMapping["credentialSet"],
                        null
                    );

                if($response->hasErrors()) {
                    Craft::error(
                        "There was an issue posting to sharpspring using custom mapping '{$data['mapping']}': \n\nRequest:\n========\n".json_encode($sharpSpringData)."\n\nError:\n=======\n".json_encode($response->getError())."\n\n",
                        'sharpspring-integration'
                    );
                    Craft::$app->response->headers->set('status', 400);
                    $this->asJson(
                        array(
                            "status" => "error",
                            "errors" => array(
                                array(
                                    "detail" =>
                                        "There was an error with your submission"
                                )
                            )
                        )
                    );
                }
            } catch(\GuzzleHttp\Exception\ClientException $e) {
                Craft::error(
                    "There was a client response issue posting to sharpspring:\n========\n{$e->getMessage()}",
                    'sharpspring-integration'
                );
                Craft::$app->response->headers->set('status', 500);
                $this->asJson(
                    array(
                        "status" => "error",
                        "errors" => array(
                            array(
                                "detail" =>
                                    "An unexpected error has occurred while submitting data to SharpSpring. Please check configurations and try again."
                            )
                        )
                    )
                );
            } catch(\Exception $e) {
                Craft::error(
                    "There was an unexpected issue posting to sharpspring:\n========\n{$e->getMessage()}",
                    'sharpspring-integration'
                );
                Craft::$app->response->headers->set('status', 500);
                $this->asJson(
                    array(
                        "status" => "error",
                        "errors" => array(
                            array(
                                "detail" =>
                                    "An unexpected error has occurred while submitting data to SharpSpring. Please check configurations and try again."
                            )
                        )
                    )
                );
            }

            $this->asJson(
                array(
                    "status" => "success",
                    "message" => "Data has been successfully sent."
                )
            );
        } else {
            // Log an error if the incoming data was empty
            Craft::warning(
                "WARNING: incoming async payload does not have any data to push (using mapping {$data['mapping']})",
                'sharpspring-integration'
            );

            Craft::$app->response->headers->set('status', 400);
            $this->asJson(
                array(
                    "status" => "error",
                    "errors" => array(
                        array(
                            "detail" =>
                                "Incoming data was empty."
                        )
                    )
                )
            );
        }
        return $result;
    }
}
