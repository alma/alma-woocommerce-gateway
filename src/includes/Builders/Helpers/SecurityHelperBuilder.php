<?php

namespace Alma\Woocommerce\Builders\Helpers;

use Alma\Woocommerce\Helpers\ProductHelper;
use Alma\Woocommerce\Helpers\SecurityHelper;
use Alma\Woocommerce\Traits\BuilderTrait;

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Not allowed' ); // Exit if accessed directly.
}
class SecurityHelperBuilder
{
    use BuilderTrait;

    public function get_instance()
    {
        return new SecurityHelper(
            $this->get_alma_logger(),
            $this->get_payment_validator()
        );
    }
}