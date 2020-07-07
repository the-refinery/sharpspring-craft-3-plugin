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
use Solspace\Freeform\Services\SubmissionsService;
use Solspace\Freeform\Events\Submissions\SubmitEvent;
use craft\base\Element;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;

use yii\base\Event;

/**
 * MappingConfig Service
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
class MappingConfig extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     SharpspringIntegration::$plugin->mappingConfig->exampleService()
     *
     * @return mixed
     */
    public function setup() {
        // Not currently used - needs testing for Craft 3
        //$this->setupEntryMappings();
        $this->setupFreeformMappings();
    }


  public function getCustomMapping($mappingKey) {
        $mainMapping = include (Craft::$app->config->configDir . '/sharpspringintegration.php');
        $mainMapping = $mainMapping['*']['customMappings'];
        if(is_null($mainMapping)) {
            throw new \Exception("customMapping not found in configuration file.");
        }

        if(!array_key_exists($mappingKey, $mainMapping)) {
            throw new \Exception("Custom SharpSpring mapping for '{$mappingKey}' not found. Please check integration configuration file for details.");
        }

        $customMapping = $mainMapping[$mappingKey];


        $newMapping = [];

        $newMapping["credentialSet"] = $customMapping["credentialSet"] ?? "*";
        $newMapping["map"] = [];

        foreach($customMapping["map"] as $key => $value) {
            $newMapping["map"][$key] = $value;
        }

        return $newMapping;

    }

    private function setupFreeformMappings() {
        $freeformMappingSetup = include (Craft::$app->config->configDir . '/sharpspringintegration.php');

        if(!is_null($freeformMappingSetup)) {
            foreach($freeformMappingSetup['*']['freeformSubmissionMappings'] as $freeformHandle => $freeformHandleConfig) {

                Event::on(
                    SubmissionsService::class,
                    SubmissionsService::EVENT_BEFORE_SUBMIT,
                    function(SubmitEvent $event) use ($freeformHandle, $freeformHandleConfig) {
                        $form = $event->getForm();
                        $submission = $event->getElement();
                        $isNewEntry = !$form->isFormSaved();

                        if($form->getHandle() == $freeformHandle) {
                            $shouldPost = true;
                            $fireOnCreate = true;
                            $fireOnUpdate = false;

                            if(array_key_exists('fireOnCreate', $freeformHandleConfig)) {
                                $fireOnCreate = $freeformHandleConfig['fireOnCreate'];
                            }

                            if(array_key_exists('fireOnUpdate', $freeformHandleConfig)) {
                                $fireOnUpdate = $freeformHandleConfig['fireOnUpdate'];
                            }

                            // Don't post if this is an entry update and we shouldn't fire on update
                            if(!$isNewEntry && !$fireOnUpdate) {
                                $shouldPost = false;
                            }

                            if($shouldPost) {
                                $credentialSet = "*";
                                if(array_key_exists('credentialSet', $freeformHandleConfig)) {
                                    $credentialSet = $freeformHandleConfig['credentialSet'];
                                }

                                $sharpSpringData = [];

                                if(array_key_exists('map', $freeformHandleConfig)) {
                                    foreach($freeformHandleConfig['map'] as $entryField => $sharpSpringKey) {
                                        $fieldType = $form->getLayout()->getFieldByHandle($entryField)->getType();

                                        switch($fieldType) {
                                            case "email":
                                                // NOTE: Freeform stores email addresses as an array of email addresses. Most of the time you only
                                                // want the first one.
                                                $values = $submission->__get($entryField)->getValue();
                                                if(!empty($values)) {
                                                    $sharpSpringData[$sharpSpringKey] = $values[0];
                                                }
                                                break;
                                            case "text":
                                                $sharpSpringData[$sharpSpringKey] = $submission->__get($entryField)->getValue();
                                                break;
                                            case "textarea":
                                                $sharpSpringData[$sharpSpringKey] = $submission->__get($entryField)->getValue();
                                                break;
                                            case "select":
                                                $sharpSpringData[$sharpSpringKey] = $submission->__get($entryField)->getValue();
                                                break;
                                            case "hidden":
                                                $sharpSpringData[$sharpSpringKey] = $submission->__get($entryField)->getValue();
                                                break;
                                            case "checkbox":
                                                if($submission->__get($entryField)->getValue()) {
                                                    $value = true;
                                                } else {
                                                    $value = false;
                                                }

                                                $sharpSpringData[$sharpSpringKey] = $value;
                                                break;
                                            default:
                                                Craft::warning(
                                                    "WARNING: Freeform field '".$entryField."' type '".$fieldType."' for form handle '".$freeformHandle."' is not a known type to process.",
                                                    'sharpspring-integration'
                                                );
                                        }
                                    }
                                }

                                $publishMethod = $freeformHandleConfig["publishMethod"] ?? "api-lead";

                                switch($publishMethod) {
                                    case "native-form":
                                        $response = SharpspringIntegration::$plugin
                                            ->nativeFormClient
                                            ->postData(
                                                $sharpSpringData,
                                                $freeformHandleConfig["nativeFormEndpoint"]
                                            );
                                        break;
                                    case "api-lead":
                                        $response = SharpspringIntegration::$plugin
                                            ->apiClient
                                            ->upsertSingleLead(
                                                $sharpSpringData,
                                                $credentialSet,
                                                null
                                            );
                                        break;
                                    default:
                                        Craft::warning(
                                            "WARNING: API Publishing method '".$publishMethod."' is not a valid publish type.",
                                            'sharpspring-integration'
                                        );
                                }

                                if($response->hasErrors()) {
                                    Craft::warning(
                                        "There was an error posting data to SharpSpring from Freeform '".$freeformHandle."': \n\nRequest:\n========\n\nError:\n=======\n".json_encode($response->getError())."\n\n",
                                        'sharpspring-integration'
                                    );

                                    throw new Exception("There was an error posting data to CRM. Please see logs for details.");
                                }
                            }
                        }
                    }
                );
            }
        }
    }

    private function setupEntryMappings() {
        $entryMappingSetup = include (Craft::$app->config->configDir . '/sharpspringintegration.php');
        if(!is_null($entryMappingSetup)) {
            foreach($entryMappingSetup['*']['freeformSubmissionMappings'] as $entryTypeHandle => $entryTypeHandleConfig) {

                Event::on(
                Entry::class,
                Element::EVENT_BEFORE_SAVE,
                function(ModelEvent $e) use ($entryTypeHandle, $entryTypeHandleConfig) {
                        /* @var Entry $entry */
                        $entry = $e->sender;
                        //echo '<pre>'; var_dump($e); echo '</pre>'; die;
                        //$isNewEntry = $event->params["isNewEntry"];

                        if($entry->type->name == $entryTypeHandle) {
                            $shouldPost = true;
                            $fireOnCreate = true;
                            $fireOnUpdate = false;

                            if(array_key_exists('fireOnCreate', $entryTypeHandleConfig)) {
                               $fireOnCreate = $entryTypeHandleConfig['fireOnCreate'];
                            }

                            if(array_key_exists('fireOnUpdate', $entryTypeHandleConfig)) {
                               $fireOnUpdate = $entryTypeHandleConfig['fireOnUpdate'];
                            }

                            // Don't post if this is an entry update and we shouldn't fire on update
                            if(!$isNewEntry && !$fireOnUpdate) {
                                $shouldPost = false;
                            }

                            if($shouldPost) {
                                $credentialSet = "*";
                                if(array_key_exists('credentialSet', $entryTypeHandleConfig)) {
                                    $credentialSet = $entryTypeHandleConfig['credentialSet'];
                                }

                                $sharpSpringData = [];

                                if(array_key_exists('map', $entryTypeHandleConfig)) {
                                    foreach($entryTypeHandleConfig['map'] as $entryField => $sharpSpringKey) {
                                        $fieldType = Craft::$app->fields->getFieldByHandle($entryField)->type;

                                        switch($fieldType) {
                                            case "PlainText":
                                                $sharpSpringData[$sharpSpringKey] = $entry->getFieldValue($entryField);
                                                break;
                                            case "Checkboxes":
                                                //TODO: Figure out how to set up multiple checkboxes via configuration.
                                                $fieldValue = $entry->getFieldValue($entryField);
                                                if (array_key_exists(0, $fieldValue)) {
                                                    $sharpSpringData[$sharpSpringKey] = true;
                                                }
                                                break;
                                            case "Number":
                                                $sharpSpringData[$sharpSpringKey] = $entry->getFieldValue($entryField);
                                                break;
                                            case "Dropdown":
                                                $sharpSpringData[$sharpSpringKey] = $entry->getFieldValue($entryField)->value;
                                                break;
                                        }
                                    }
                                }

                                $response = SharpspringIntegration::$plugin
                                    ->apiClient
                                    ->upsertSingleLead(
                                        $sharpSpringData,
                                        $credentialSet,
                                        null
                                    );

                                if($response->hasErrors()) {
                                    Craft::warning(
                                        "There was an error posting data to SharpSpring from Craft Entry'".$entryTypeHandle."': \n\nRequest:\n========\n".json_encode($sharpSpringData)."\n\nError:\n=======\n".json_encode($response->getError())."\n\n",
                                        LogLevel::Error,
                                        true
                                    );

                                    throw new Exception("There was an error posting data to CRM. Please see logs for details.");
                                }
                            }
                        }
                    }
                );
            }
        }
    }
}
