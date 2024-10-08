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
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\OrderHelper;
use Alma\Woocommerce\Helpers\PaymentHelper;
use Alma\Woocommerce\Helpers\ProductHelper;
use Alma\Woocommerce\Helpers\SecurityHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\Services\PaymentUponTriggerService;
use WP_UnitTestCase;

class PaymentHelperTest extends WP_UnitTestCase
{
    /**
     * @var PaymentHelper
     */
    protected $payment_helper;
    /**
     * @var PaymentHelper
     */
    protected $logger;
    public function set_up() {
        $this->logger = \Mockery::mock(AlmaLogger::class);
        $trigger = \Mockery::mock(PaymentUponTriggerService::class);
        $settings = \Mockery::mock(AlmaSettings::class);
        $tool_helper = \Mockery::mock(ToolsHelper::class);
        $cart_helper = \Mockery::mock(CartHelper::class);
        $order_helper = \Mockery::mock(OrderHelper::class);
        $product_helper = \Mockery::mock(ProductHelper::class);
        $security_helper = \Mockery::mock(SecurityHelper::class);
        $this->payment_helper = new PaymentHelper(
            $this->logger,
            $trigger,
            $settings,
            $tool_helper,
            $cart_helper,
            $order_helper,
            $product_helper,
            $security_helper
        );
    }

    public function tear_down()
    {
        parent::tear_down();
        \Mockery::close();
    }

    public function test()
    {
        // TODO: Need to add test or remove this Test file
        $this->assertTrue(true);
    }
}
