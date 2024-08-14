# DHL Location Finder

This Drupal module provides a form to search for DHL locations using the DHL API and filters results based on specific criteria.

## Installation

1. Place the `dhl_location_finder` directory in the `modules/custom` directory of your Drupal installation.
2. Enable the module through the Drupal admin interface or using Drush:

   ```sh
   drush en dhl_location_finder


## Usage

1. Navigate to the form at /dhl-location-form on your Drupal site.
2.  Enter the required details:
   Country Code: e.g., DE (Germany)
   City: e.g., Dresden
   Postal Code: e.g., 01067
   Click the "Find Locations" button.
3.The results will be displayed on the same page in YAML format.
