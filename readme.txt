=== Alma Monthly Payments for WooCommerce ===
Contributors: almapayments, olance
Tags: payments, payment gateway, woocommerce, ecommerce, e-commerce, sell, woo commerce, alma, monthly payments, split payments
Requires at least: 4.4
Tested up to: 4.9
Requires PHP: 5.3
Stable tag: 1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin adds a new payment method to WooCommerce, which allows you to offer monthly payments to your customer using Alma.

== Description ==
[Alma](https://getalma.eu) is a service to provide merchants with an **easy** and **safe** monthly payments solution.

This plugin integrates Alma into WooCommerce by adding a new payment method that you can activate to offer monthly payments to your customers.

== Installation ==

= Prerequisites =

You first need to create your merchant account on [dashboard.getalma.eu](https://dashboard.getalma.eu) and activate your account.

= Configuring the plugin =

After installing the plugin, go to WooCommerce settings and activate the new Alma payment method.
You should be redirected to the payment method settings upon activation.

Fill in the API keys for your account, which you can find on your dashboard\'s [security page](https://dashboard.getalma.eu/security).

After you save your API keys, you\'ll have access to different settings to control what the plugin should display on the Cart and Checkout pages.
We advise you to stay in \"Test\" mode until you\'re happy with your configuration and are ready to accept payments from your customers.

Once everything is properly set up, go ahead and switch to \"Live\" mode!

== Screenshots ==
1. Alma\'s payment method settings
2. Cart eligibility for monthly payments
3. Payment method at checkout
4. Alma\'s payment page that users are sent to upon order confirmation

== Changelog ==

= 1.1.2 =
* Adds hidden option to be able to choose installments count to use for created payments

= 1.1.1 =
* Fixes bug occurring in `wp-admin/nav-menus.php`

= 1.1.0 =
* Fixes case of "alma" in includes path
* Adds possibility to exclude product categories from installment payments, to help enforce contractual restrictions
  (i.e. Alma cannot be used to sell/buy virtual/downloadable products such as gift cards, subscriptions, ...)

= 1.0.1 =
Let's start following semver.

* Switches logo file to SVG
* Uses latest Eligibility check behaviour to remove one extra call to the API
* Adds User-Agent string containing the module's version, WordPress version, WooCommerce, PHP client and PHP versions,
to all requests going to Alma's API.
* Adds support for Alma's IPN callback mechanism

= 1.0.0 =
This version evolved for a while without any version bump 🤷‍♂️
Features in the latest push to this release:

* Module can be configured in Test and Live mode; Test mode only shows Alma's payment method to visitors who are also
administrators of the shop
* A message displays below the cart to indicate whether the purchase is eligible to monthly installments
* The module adds a payment method to the checkout, which redirects the user to Alma's payment page.
If everything goes right (i.e. Customer doesn't cancel, pays the right amount, ... ), the order is validated upon customer return.
