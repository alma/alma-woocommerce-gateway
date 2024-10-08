<?php
/**
 * Class AssetsHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\PaymentHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;

use Alma\API\Lib\PaymentValidator;
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
    /**
     * @var PaymentValidator
     */
    protected $payment_validator;

    public function set_up()
    {
        $this->logger = \Mockery::mock(AlmaLogger::class);
        $this->payment_validator = \Mockery::mock(PaymentValidator::class);

        $this->security_helper = new SecurityHelper(
            $this->logger,
            $this->payment_validator
        );
    }

    public function tear_down()
    {
        parent::tear_down();
        \Mockery::close(); // Ferme Mockery après chaque test
    }

    public function test_validate_ipn_throw_Invalide_signature_exception_for_bad_params()
    {
        $signature = 'bad_signature';
        $payment_id = 'payment_id';
        $api_key = 'api_key';
        $this->payment_validator->shouldReceive('isHmacValidated')->andReturn(false);
        $this->expectException(AlmaInvalidSignatureException::class);
        $this->security_helper->validate_ipn_signature($payment_id, $api_key, $signature);
    }

    public function test_validate_ipn_signature()
    {
        $signature = 'good_signature';
        $payment_id = 'valid_payment_id';
        $api_key = 'valid_api_key';
        $this->payment_validator->shouldReceive('isHmacValidated')->with($payment_id, $api_key, $signature)->andReturn(true);
        $this->assertNull($this->security_helper->validate_ipn_signature($payment_id, $api_key, $signature));
    }
}
