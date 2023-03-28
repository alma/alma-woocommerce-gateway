=== Alma - Pay in installments or later for WooCommerce ===

Contributors: almapayments, olance, syjust, clairealma
Tags: payments, BNPL, woocommerce, ecommerce, e-commerce, payment gateway, sell, woo commerce, alma, monthly payments, split payments
Requires at least: 4.4
Tested up to: 6.2
Requires PHP: 5.6
Stable tag: 4.1.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin adds a new payment method to WooCommerce, which allows you to offer monthly payments to your customer using Alma.

== Description ==

[Alma](https://almapay.com) is a service to provide merchants with an **easy** and **safe** monthly payments solution.
Let your customers pay for their purchases at their own pace! You'll receive the funds instantly, and your customer will pay later over a few monthly instalments.

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
2. Product eligibility for monthly payments
3. Cart eligibility for monthly payments
4. Alma badge reinsurance Info
5. Payment method at checkout
6. Alma\'s payment page that users are sent to upon order confirmation

== Contributing ==

- Clone the repository from [github](https://github.com/alma/alma-woocommerce-gateway) in you wp-content/plugins/alma-gateway-for-woocommerce folder
- Run `composer install` to install dependencies
- Create an account on http://dashboard.sandbox.getalma.eu/ to get an API key
- Fill your API key in plugin settings
- Visit the shop and add a product to the cart to see Alma in action

= Translations =

To edit the translations, use [Poedit](https://poedit.net/)

- Open the `.pot` file and click on `Update from code`, then save
- Open the `.po` file and click on `Update from code`, add/update the translations, then save

= Build =

To build extension for production run `./bin/build.sh`

== Changelog ==

= 4.1.1 =
=========


* fix: PHP 8.0 and 8.1 compatibility
* fix:phpcs
* fix: remove useless files
* fix: remove useless files
* fix: remove useless logs

= 4.1.0 =
* feat: compatibility WordPress 6.2
* feat: add logger to the php client
* feat: compatibility woocommerce 7.5
* feat: add namespaces
* fix: save alma version in db on fresh install
* fix: translations
* fix: unique nonce fields
* fix: compatibility with other plugins
* fix: reduce api calls
* fix: migration between versions of alma module
* fix: implement best practices Woocommerce and WordPress

= 3.2.2 =
* feat: update widget to 3.1.0

= 3.2.1 =
* feat: update widget to 3.0.4
* fix: nonce double check issue

= 3.2.0 =
* feat: rebranding 2022

= 3.1.2 =
* fix: widget translations
* fix: php8 compatibility

= 3.1.1 =
* fix: correct bad button text for refund
* fix: correcting issue of multiple times the same order note
* fix: correcting wording issue in case of order not have a transaction_id

= 3.1.0 =
* feat: implement B2B fields (INT-714 #65)
* refact: widget injection (INT-589 #57)
* fix: use sales price to update widget instead regular price (INT-290 / INT-570 #52)
* feat: refund from woocommerce back-office (INT-510 #69)
* fix: alma should be hidden on checkout when test key is selected & user is not admin (INT-586 #71)

= 3.0.0 =
* feat: add nonce to secure checkout form
* feat: add script to retrieve widget files
* fix: data sanitizing
* fix: upd payment fields validation notice messages

= 2.6.0 =
* feat: add BO dynamic check variation
* feat: add BO technical fields section
* feat: add payment upon shipping
* fix: names inversion between shipping & billing addresses

= 2.5.2 =
* feat: ignore & remove composer.lock
* fix: allow widget to be injected in builder

= 2.5.1 =
* fix: admin test on unavailable var for other admin pages
* fix: check cart eligibility without customer

= 2.5.0 =
* feat: back-office i18n custom fields

= 2.4.1 =
* fix: issue on payment method order on checkout page

= 2.4.0 =
* feat: split payments
* fix: model get_cart on null in wysiwyg builder
* refactor: Remove the unnecessary boolean literal
* refactor: payment gateway description fix code duplication
* refactor: reduce cognitive complexity

= 2.3.0 =
* feat(widget): upgrade CDN from 1.x to 2.x
* feat(widget): alma API mode
* feat(widget): deferred_days & deferred_months
* feat(widget): locale
* fix: filter eligible feeplans in checkout

= 2.2.1 =
* fix: send user_locale in Alma checkout

= 2.2.0 =
* ci: add shell script bumper
* feat: add IT & DE languages
* fix: call eligibility without country if null
* fix: dynamic update on checkout for anonymous user
* fix: update translations without space on go_to_log
* fix: widget handler replace excluding by including tax

= 2.1.1 =
* fix: remove upgrade process
* fix: usage of woo deprecated get price func
* refactor: remove redundant properties initializations
* refactor: add Settings magic properties
* refactor: remove unnecessary brackets into strings
* word(typo): fix all doc block typo

= 2.1.0 =
* [i18n] allow plugin to works with all locales
* [i18n] add locale & advanced address fields on payment creation
* [i18n] add customer addresses information to check payment plans on checkout
* [i18n] add nl & es translations
* minor refactorings
* enhance docker env

= 2.0.0 =
* Add inline Pay Later (Eligibility V2)
* Add dedicated DB migrations processor
* Load & update fee-plans dynamically from ALMA dashboard config
* Add autoloader
* Minor refactorisations
* Root tree refactorisation
* Re-design back-office fee plans configuration

= 1.3.1 =
* Avoid Fatal error on (not found) product badge injection
* Increase debug log on non displayed bages

= 1.3.0 =
* Remove PHP warnings & do not display widget on out-stock or un-priced product
* Minor refactoring
* Enhance dev docker env (display php warnings / errors + prioritize custom-php-ini file)
* Fix widget display price without tax depending on WooCommerce tax rule configuration
* Add fallback locale on checkout payment ALMA API call
* Add filter to override locale on checkout payment ALMA API call
* Add widget shortcodes

= 1.2.3 =
* Use unpkg.com instead of unpkg.io

= 1.2.2 =
* Do not display a radio button at checkout, when there's only one payment plan available

= 1.2.1 =
* Fixes default min/max amount values being converted to cents multiple times in a row
* Improves xdebug configuration

= 1.2.0 =
* Allows merchant to activate 2-, 3- a 4-installment plans, with min and max allowed amounts per plan
* Displays an Alma widget on product pages and cart page to inform customers of eligible payment plans
* Displays a full payment plan for each plan option on the checkout page
* Enables Alma by default for all `fr_*` locales
* Adds a `alma_wc_enable_for_locale` that can be used to enable Alma for additional locales
* Any of the module's settings can be overridden via a filter: `alma_wc_settings_[setting_name]`
* Fixes compatibility issues with WooCommerce 2.6.14
* Fixes many bugs
* Dependencies update

= 1.17 =
* Do not require Live key in Test mode and vice versa
* Include cancel url, order reference & order URLs in payment data
* Don't show the eligibility message in case of API error
* Stop checking in vendor dependencies – they'll be included in each release's ZIP file

= 1.1.6 =
* Dependencies update

= 1.1.5 =
* Fixes display on cart option that was always on
* Tested against latest versions of Wordpress/WooCommerce

= 1.1.4 =
* Fixes various warnings when WooCommerce isn't activated
* Updates dependencies
* Fixes errors when Alma's API is not available
* Attempt to fix problem triggered when calling the Wordpress API

= 1.1.3 =
* Fixes warning when `excluded_products_list` is not set

= 1.1.2 =
* Adds hidden option to be able to choose installments count to use for created payments

= 1.1.1 =
* Fixes bug occurring in `wp-admin/nav-menus.php`

= 1.1.0 =
* Fixes case of "alma" in includes path
* Adds possibility to exclude product categories from installment payments, to help enforce contractual restrictions
  (i.e. Alma cannot be used to sell/buy virtual/downloadable products such as gift cards, subscriptions, ...)

= 1.0.1 =
* Switches logo file to SVG
* Uses latest Eligibility check behaviour to remove one extra call to the API
* Adds User-Agent string containing the module's version, WordPress version, WooCommerce, PHP client and PHP versions, to all requests going to Alma's API.
* Adds support for Alma's IPN callback mechanism

= 1.0.0 =
* Module can be configured in Test and Live mode; Test mode only shows Alma's payment method to visitors who are also
administrators of the shop
* A message displays below the cart to indicate whether the purchase is eligible to monthly installments
* The module adds a payment method to the checkout, which redirects the user to Alma's payment page.
