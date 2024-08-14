<?php

namespace Drupal\dhl_location_finder\Tests\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the DHL Location Finder form.
 *
 * @group dhl_location_finder
 */
class DHLLocationFormTest extends BrowserTestBase {

    /**
     * {@inheritdoc}
     */
    protected static $modules = ['dhl_location_finder'];

    /**
     * Tests the DHL Location Finder form.
     */
    public function testForm() {
        // Create a user with permission to access content.
        $user = $this->drupalCreateUser(['access content']);
        $this->drupalLogin($user);

        // Visit the form page.
        $this->drupalGet('/dhl-location-form');
        $this->assertSession()->statusCodeEquals(200);
        $this->assertSession()->pageTextContains('Find Locations');

        // Fill out and submit the form.
        $this->submitForm([
            'country_code' => 'DE',
            'city' => 'Dresden',
            'postal_code' => '01067',
        ], 'Find Locations');

        // Check that the response is displayed.
        $this->assertSession()->pageTextContains('locationName');
    }
}
