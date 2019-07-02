Changelog
=========

v1.1.1
------

* Fixes bug occurring in `wp-admin/nav-menus.php`

v1.1.0
------

* Fixes case of "alma" in includes path
* Adds possibility to exclude product categories from installment payments, to help enforce contractual restrictions 
  (i.e. Alma cannot be used to sell/buy virtual/downloadable products such as gift cards, subscriptions, ...)

v1.0.1
------
Let's start following semver.

* Switches logo file to SVG
* Uses latest Eligibility check behaviour to remove one extra call to the API
* Adds User-Agent string containing the module's version, WordPress version, WooCommerce, PHP client and PHP versions,
to all requests going to Alma's API.
* Adds support for Alma's IPN callback mechanism

v1.0.0
------
This version evolved for a while without any version bump 🤷‍♂️
Features in the latest push to this release:

* Module can be configured in Test and Live mode; Test mode only shows Alma's payment method to visitors who are also
administrators of the shop
* A message displays below the cart to indicate whether the purchase is eligible to monthly installments
* The module adds a payment method to the checkout, which redirects the user to Alma's payment page.
If everything goes right (i.e. Customer doesn't cancel, pays the right amount, ... ), the order is validated upon customer return.
