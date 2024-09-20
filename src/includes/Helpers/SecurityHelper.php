<?php

namespace Alma\Woocommerce\Helpers;

use Alma\API\Lib\PaymentValidator;
use Alma\Woocommerce\Exceptions\AlmaInvalidSignatureException;

class SecurityHelper
{
    /**
     * The logger.
     *
     * @var AlmaLogger
     */
    protected $logger;
    /**
     * @var PaymentValidator
     */
    protected $payment_validator;

    public function __construct($logger, $payment_validator)
    {
        $this->logger = $logger;
        $this->payment_validator = $payment_validator;
    }

    public function validate_ipn_signature($payment_id, $api_key, $signature)
    {
        if(!$this->payment_validator->isHmacValidated($payment_id, $api_key, $signature)){
            throw new AlmaInvalidSignatureException();
        }
    }
}