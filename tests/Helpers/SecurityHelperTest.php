<?php
/**
 * Class AssetsHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\PaymentHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;

use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Exceptions\AlmaInvalidSignatureException;
use Alma\Woocommerce\Helpers\PaymentHelper;
use Alma\Woocommerce\Helpers\SecurityHelper;
use WP_UnitTestCase;

class SecurityHelperTest extends WP_UnitTestCase
{
    /**
     * @var PaymentHelper
     */
    protected $security_helper;
    /**
     * @var PaymentHelper
     */
    protected $logger;

    public function set_up()
    {
        $this->logger = \Mockery::mock(AlmaLogger::class);

        $this->security_helper = new SecurityHelper(
            $this->logger
        );
    }

    public function tear_down()
    {
        parent::tear_down();
        \Mockery::close(); // Ferme Mockery après chaque test
    }

    public function test_validate_ipn_throw_invalid_signature_exception_for_bad_params()
    {
        $signature = 'bad_signature';
        $payment_id = 'payment_id';
        $api_key = 'api_key';
        $this->expectException(AlmaInvalidSignatureException::class);
        $this->security_helper->validate_ipn_signature($payment_id, $api_key, $signature);
    }

    public function test_validate_ipn_signature()
    {
        $signature = '3dcb1255e432da08a2bd65df2963659bb0b362888500e18c8cf6c5d5958db752';
        $payment_id = 'payment_xxxxx';
        $api_key = 'sk_test_xxxxx';
        $this->assertNull($this->security_helper->validate_ipn_signature($payment_id, $api_key, $signature));
    }

    public function test_validate_collect_data_signature_throw_invalid_signature_exception_for_bad_params()
    {
        $signature = 'bad_signature';
        $merchant_id = 'merchant_id';
        $api_key = 'api_key';
        $this->expectException(AlmaInvalidSignatureException::class);
        $this->security_helper->validate_collect_data_signature($merchant_id, $api_key, $signature);
    }

    public function test_validate_collect_data_signature()
    {
        $signature = '7d572bbfbedb1bde72378691973a67ff52fb56cd9d18b1f0ab3c7e88b119b9d3';
        $merchant_id = 'merchant_xxxxxx';
        $api_key = 'sk_test_xxxxx';
        $this->assertNull($this->security_helper->validate_collect_data_signature($merchant_id, $api_key, $signature));
    }
}
