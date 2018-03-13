# Flutterwave Rave (Drupal 8 Module)

CONTENTS
---------------------
* [Introduction](#introduction)
* [Requirements](#requirements)
* [Installation](#installation)
* [Configuration](#configuration)
* [How It Works](#how-it-works)
* [To Do](#to-do)
* [License](#license)
* [Credits](#credits)

## Introduction
Accept Credit card, Debit card and Bank account payment directly on your store with the Rave payment gateway for WooCommerce.
Take Credit card payments easily and directly on your store.

Signup for an account [here](https://rave.flutterwave.com).
* For a full description of the module, visit the project page:
  [https://www.drupal.org/project/commerce_rave](https://www.drupal.org/project/commerce_rave)
* To submit bug reports and feature suggestions, or to track changes:
  [https://www.drupal.org/project/issues/commerce_rave](https://www.drupal.org/project/issues/commerce_rave)

## Requirements
This module requires the following:
* Submodules of [Drupal Commerce Module](https://drupal.org/project/commerce). 
  - Commerce core
  - Commerce Payment (and its dependencies)

## Installation
* This module can be [installed via Composer](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies).
```
composer require 'drupal/commerce_rave:^1.0'
```
* You can also install with [Drush](https://www.drupal.org/node/2603018):
```
drush en commerce_rave -y
```
which will download and enable the module automatically.

For more information about installing Drupal Modules: 
* [https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
* [https://www.drupal.org/docs/user_guide/en/extend-module-install.html](https://www.drupal.org/docs/user_guide/en/extend-module-install.html)

## Configuration
* Create a new Rave payment gateway.  
  *Administration > Commerce > Configuration > Payment gateways > Add payment gateway*  
  Rave-specific settings available:
  - Secret key
  - Public key
  - Payment Flow (iFrame or [Hosted Payment Pages](https://flutterwavedevelopers.readme.io/docs/validate-payment-without-internet))
  
  Use the API credentials provided by your Rave merchant account. It is
  recommended to enter test credentials and then override these with live
  credentials in settings.php. This way, live credentials will not be stored in the db.

## How It Works
* General considerations:
  - The store owner must have a Rave merchant account.
    Sign up here:
    [https://rave.flutterwave.com](https://rave.flutterwave.com)
  - Customers should have a valid credit card/bank account.
    - Rave provides some test credit card numbers for testing:
      [https://flutterwavedevelopers.readme.io/docs/test-cards](https://flutterwavedevelopers.readme.io/docs/test-cards)
    - Rave also provides some test bank accounts for testing:
      [https://flutterwavedevelopers.readme.io/docs/test-bank-accounts](https://flutterwavedevelopers.readme.io/docs/test-bank-accounts)
* Checkout workflow:
  - It follows the Drupal Commerce Credit Card workflow.
  The customer should enter his/her credit card data or bank account info.
  - The Rave modal uses the site information to configure the modal automatically with:
    - Title
    - Description
    - Site Logo
* Payment Terminal:
  - The store owner can view the Rave payments.

## To Do
- [ ] Save Credit Cards data to User account after first use.
- [ ] Save Bank Account data to User account after first use.
- [ ] Integrate Refund ability so the store owner can refund the Rave Payments.


## License

GNU GENERAL PUBLIC LICENSE V2

Please see [License File](LICENSE.txt) for more information.

## Credits

- [Elijah Oyekunle](https://elijahoyekunle.com) - [Twitter](https://twitter.com/elijahoyekunle) - [Drupal.org](https://www.drupal.org/u/elijahoyekunle) - [Github](https://github.com/playmice)
