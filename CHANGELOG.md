Changelog
=========

* feat: compatibility Woocommerce 8.1.1
* change : PHP client version 1.11.2

v5.0.3
------
* fix : Incompatibility with Woocommerce Paypal Plugin

v5.0.2
------
* fix : In-Page creation order
* feat: Compatibility WordPress 6.3.1

v5.0.1
------
* fix: missing shipping information when In Page is activated
* fix: missing client information when In Page is activated
* fix: loading of the translation files
* feat: Compatibility Woocommerce 8.0.3

v5.0.0
------
* feat: In Page checkout
* feat: Separate the gateways in the checkout page
* feat: Compatibility Woocommerce 8.0.1
* feat: Compatibility Woocommerce 8.0.2
* feat: Compatibility WordPress 6.3
* feat: Update Widget version 3.3.5

v4.3.4
------
* fix: wrong variable in mismatch

v4.3.3
------
* fix: prevent to call Eligibility api with an amount to 0~~
* fix: wrong variable name
* fix: Change the return code (500 to 200)on Ipn callback when it's a mismatch or potential fraud
* feat: upgrade PHP client to 1.11.1
* feat: Compatibility woocommerce 7.9.0

v4.3.2
------
* fix: SEPA deprecated test
* fix: Restore HTML description in gateway
* fix: Improve auto-update
* feat: Add merchant infos in BO
* feat: Add previous version info in BO
* feat: Prevent issues with Germanized for WooCommerce
* feat: Compatibility woocommerce 7.8.2
* feat: New widget v3.3.4

v4.3.1
------
* fix: Warning: Cannot modify header information - headers already sent

v4.3.0
------
* feat: Implement Pay Now
* feat: Compatibility woocommerce 7.8.0
* feat: Update Widget to 3.3.3

v4.2.5
------
* fix: fix the accordion css on checkout
* feat: Update widget to 3.3.2

v4.2.4
------
* feat: Allow to customize the gateway title

v4.2.3
------
* hotfix: don't do the basic gateway checks during update process

v4.2.2
------
* hotfix: Prevent to send multi update thread

v4.2.1
------
* hotfix: Plugin update when the gateway is disabled

v4.2.0
------
* feat: Share of checkout feature
* feat: Encryption Key feature in database
* feat: Gather the gateway in checkout (design)
* fix: Reduce extended-call data
* feat: Send basket order details for credit
* feat: Implement a template system

v4.1.2
------

* fix: nonce error log issue
* feat: Update widget
* feat: compatibility with woocommerce 7.6


v4.1.1
------

* fix: PHP 8.0 and 8.1 compatibility

v4.1.0
------

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

v3.2.2
------

* feat: update widget to 3.1.0

v3.2.1
------

* feat: update widget to 3.0.4
* fix: nonce double check issue

v3.2.0
------

* feat: rebranding 2022

v3.1.2
------

* fix: widget translations
* fix: php8 compatibility

v3.1.1
------

* fix: correct bad button text for refund
* fix: correcting issue of multiple times the same order note
* fix: correcting wording issue in case of order not have a transaction_id

v3.1.0
------

* feat: implement B2B fields (INT-714 #65)
* refact: widget injection (INT-589 #57)
* fix: use sales price to update widget instead regular price (INT-290 / INT-570 #52)
* feat: refund from woocommerce back-office (INT-510 #69)
* fix: alma should be hidden on checkout when test key is selected & user is not admin (INT-586 #71)

v3.0.0
------

* feat: add nonce to secure checkout form
* feat: add script to retrieve widget files
* fix: data sanitizing
* fix: upd payment fields validation notice messages

v2.6.0
------

* feat: add BO dynamic check variation
* feat: add BO technical fields section
* feat: add payment upon shipping
* fix: names inversion between shipping & billing addresses

v2.5.2
------

* feat: ignore & remove composer.lock
* fix: allow widget to be injected in builder

v2.5.1
------

* fix: admin test on unavailable var for other admin pages
* fix: check cart eligibility without customer

v2.5.0
------

* feat: back-office i18n custom fields

v2.4.1
------

* fix: issue on payment method order on checkout page

v2.4.0
------

* feat: split payments
* fix: model get_cart on null in wysiwyg builder
* refactor: Remove the unnecessary boolean literal
* refactor: reduce cognitive complexity

v2.3.0
------

* feat(widget): upgrade CDN from 1.x to 2.x
* feat(widget): alma API mode
* feat(widget): deferred_days & deferred_months
* feat(widget): locale
* fix: filter eligible feeplans in checkout

v2.2.1
------

* fix: send user_locale in Alma checkout

v2.2.0
------

* ci: add shell script bumper
* feat: add IT & DE languages
* fix: call eligibility without country if null
* fix: dynamic update on checkout for anonymous user
* fix: update translations without space on go_to_log
* fix: widget handler replace excluding by including tax

v2.1.1
------

* fix: remove upgrade process
* fix: usage of woo deprecated get price func
* refactor: remove redundant properties initializations
* refactor: add Settings magic properties
* refactor: remove unnecessary brackets into strings
* word(typo): fix all doc block typo

v2.1.0
------

* [i18n] allow plugin to works with all locales
* [i18n] add locale & advanced address fields on payment creation
* [i18n] add customer addresses information to check payment plans on checkout
* [i18n] add nl & es translations
* minor refactorings
* enhance docker env

v2.0.0
------

* Add inline Pay Later (Eligibility V2)
* Add dedicated DB migrations processor
* Load & update fee-plans dynamically from ALMA dashboard config
* Add autoloader
* Minor refactorisations
* Root tree refactorisation
* Re-design back-office fee plans configuration

v1.3.1
------

* Avoid Fatal error on (not found) product badge injection
* Increase debug log on non displayed bages

v1.3.0
------

* Remove PHP warnings & do not display widget on out-stock or un-priced product
* Minor refactoring
* Enhance dev docker env (display php warnings / errors + prioritize custom-php-ini file)
* Fix widget display price without tax depending on WooCommerce tax rule configuration
* Add fallback locale on checkout payment ALMA API call
* Add filter to override locale on checkout payment ALMA API call
* Add widget shortcodes

v1.2.3
------

* Use unpkg.com instead of unpkg.io

v1.2.2
------

* Do not display a radio button at checkout, when there's only one payment plan available

v1.2.1
------

* Fixes default min/max amount values being converted to cents multiple times in a row
* Improves xdebug configuration

v1.2.0
------

* Allows merchant to activate 2-, 3- a 4-installment plans, with min and max allowed amounts per plan
* Displays an Alma widget on product pages and cart page to inform customers of eligible payment plans
* Displays a full payment plan for each plan option on the checkout page
* Enables Alma by default for all `fr_*` locales
* Adds a `alma_wc_enable_for_locale` that can be used to enable Alma for additional locales
* Any of the module's settings can be overridden via a filter: `alma_wc_settings_[setting_name]`
* Fixes compatibility issues with WooCommerce 2.6.14
* Fixes many bugs
* Dependencies update

v1.1.7
------

* Do not require Live key in Test mode and vice versa
* Include cancel url, order reference & order URLs in payment data
* Don't show the eligibility message in case of API error
* Stop checking in vendor dependencies – they'll be included in each release's ZIP file


v1.1.6
------

* Dependencies update

v1.1.5
-------

* Fixes display on cart option that was always on
* Tested against latest versions of Wordpress/WooCommerce

v1.1.4
-------

* Fixes various warnings when WooCommerce isn't activated
* Updates dependencies
* Fixes errors when Alma's API is not available
* Attempt to fix problem triggered when calling the Wordpress API 

v1.1.3
-------

* Fixes warning when `excluded_products_list` is not set

v1.1.2
------

* Adds hidden option to be able to choose installments count to use for created payments

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
