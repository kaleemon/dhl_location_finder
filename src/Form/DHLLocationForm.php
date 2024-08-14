<?php

namespace Drupal\dhl_location_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DHLLocationForm extends FormBase {

    protected $httpClient;

    public function __construct(ClientInterface $http_client) {
        $this->httpClient = $http_client;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('http_client')
        );
    }

    public function getFormId() {
        return 'dhl_location_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['country_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Country'),
            '#required' => TRUE,
            '#attributes' => [
                'placeholder' => $this->t('Enter country code (e.g., DE, US)'),
            ],
        ];

        $form['city'] = [
            '#type' => 'textfield',
            '#title' => $this->t('City'),
            '#required' => TRUE,
            '#attributes' => [
                'placeholder' => $this->t('Enter city name (e.g., Bonn, New York)'),
            ],
        ];

        $form['postal_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Postal Code'),
            '#required' => TRUE,
            '#attributes' => [
                'placeholder' => $this->t('Enter postal code (e.g., 53113, 10001)'),
            ],
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Find Locations'),
        ];

        // Placeholder for API response.
        $form['api_response'] = [
            '#type' => 'markup',
            '#markup' => '',
            '#prefix' => '<div id="dhl-api-response">',
            '#suffix' => '</div>',
        ];

        // Retrieve and display the API response if available.
        if ($form_state->get('api_response')) {
            $form['api_response']['#markup'] = $form_state->get('api_response');
        }

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        // Validate country code
        $country_code = $form_state->getValue('country_code');
        if (!preg_match('/^[A-Z]{2}$/', $country_code)) {
            $form_state->setErrorByName('country_code', $this->t('The country code must be exactly 2 uppercase letters.'));
        }

        // Validate city
        $city = $form_state->getValue('city');
        if (empty($city)) {
            $form_state->setErrorByName('city', $this->t('City cannot be empty.'));
        }

        // Validate postal code
        $postal_code = $form_state->getValue('postal_code');
        if (empty($postal_code)) {
            $form_state->setErrorByName('postal_code', $this->t('The postal code must not be empty.'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $country_code = $form_state->getValue('country_code');
        $city = $form_state->getValue('city');
        $postal_code = $form_state->getValue('postal_code');

        // Prepare API request
        $url = 'https://api.dhl.com/location-finder/v1/find-by-address';
        $headers = [
            'DHL-API-Key' => 'demo-key',
        ];

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
                'query' => [
                    'countryCode' => $country_code,
                    'city' => $city,
                    'postalCode' => $postal_code,
                ],
            ]);

            $data = $response->getBody()->getContents();
            $locations = json_decode($data, TRUE);

            if ($response->getStatusCode() === 400) {
                $api_response = 'Bad Request: Please check the parameters.';
            } elseif (!isset($locations['locations'])) {
                $api_response = 'No locations found.';
            } else {
                $filtered_locations = $this->filterLocations($locations['locations']);
                if (empty($filtered_locations)) {
                    $api_response = 'No locations match the criteria.';
                } else {
                    $yaml = Yaml::dump($filtered_locations);
                    $api_response = '<pre>' . htmlspecialchars($yaml) . '</pre>';
                }
            }

        } catch (\Exception $e) {
            $api_response = 'Error: ' . htmlspecialchars($e->getMessage());
        }

        // Store the API response in the form state.
        $form_state->set('api_response', $api_response);

        // Rebuild the form to display the API response.
        $form_state->setRebuild(TRUE);
    }

    private function filterLocations(array $locations) {
        return array_filter($locations, function ($location) {
            // Extract the street address number and ensure it is a number
            $address_number = preg_replace('/\D/', '', $location['place']['address']['streetAddress']);
            // Check if opening hours exist and contain weekend hours
            $opening_hours = $location['openingHours'];
            $works_on_weekends = false;
            foreach ($opening_hours as $hours) {
                if (isset($hours['dayOfWeek'])) {
                    $dayOfWeek = basename($hours['dayOfWeek']);
                    if ($dayOfWeek === 'Saturday' || $dayOfWeek === 'Sunday') {
                        $works_on_weekends = true;
                        break;
                    }
                }
            }

            return $works_on_weekends && ($address_number % 2 == 0);
        });
    }
}
