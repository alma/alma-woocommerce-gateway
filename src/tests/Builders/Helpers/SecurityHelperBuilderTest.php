<?php

namespace Alma\Woocommerce\Tests\Builders\Helpers;

use Alma\Woocommerce\Builders\Helpers\SecurityHelperBuilder;
use Alma\Woocommerce\Helpers\SecurityHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Builders\Helpers\SecurityHelperBuilder
 */
class SecurityHelperBuilderTest extends WP_UnitTestCase
{
    /**
     * The security helper builder.
     *
     * @var SecurityHelperBuilder $security_helper_builder
     */
    protected $security_helper_builder;
    public function set_up() {
        $this->security_helper_builder = new SecurityHelperBuilder();
    }

    public function test_get_instance() {
        $this->assertInstanceOf(SecurityHelper::class, $this->security_helper_builder->get_instance());
    }

}