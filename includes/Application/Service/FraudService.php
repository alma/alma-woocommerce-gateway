<?php

namespace Alma\Gateway\Application\Service;

use Alma\Client\Domain\Entity\Payment;
use Alma\Gateway\Application\Exception\Provider\PaymentProviderException;
use Alma\Gateway\Application\Exception\Service\FraudServiceException;
use Alma\Gateway\Application\Provider\PaymentProviderAwareTrait;
use Alma\Gateway\Application\Provider\PaymentProviderFactory;
use Alma\Plugin\Infrastructure\Adapter\OrderAdapterInterface;

class FraudService {

	use PaymentProviderAwareTrait;

	public function __construct(
		PaymentProviderFactory $paymentProviderFactory
	) {
		$this->paymentProviderFactory = $paymentProviderFactory;
	}

	/**
	 * Check if the order total matches the payment amount. If not, flag as fraud and update order status.
	 * @throws FraudServiceException
	 */
	public function manageMismatch( OrderAdapterInterface $order, Payment $payment ): void {
		$order_total = $order->getTotal();
		if ( $order_total !== $payment->getPurchaseAmount() ) {
			try {
				$this->getPaymentProvider()->flagAsFraud( $payment->getId(), Payment::FRAUD_AMOUNT_MISMATCH );
			} catch ( PaymentProviderException $e ) {
				throw new FraudServiceException( $e->getMessage() );
			}
			$order->updateStatus( 'failed', Payment::FRAUD_AMOUNT_MISMATCH );
			throw new FraudServiceException( 'Potential fraud detected: order total does not match payment amount.' );
		}
	}

	/**
	 * Check if the payment state is valid (in progress or paid). If not, flag as fraud and update order status.
	 * @throws FraudServiceException
	 */
	public function managePotentialFraud( OrderAdapterInterface $order, Payment $payment ): void {
		if ( ! in_array( $payment->getState(), array( Payment::STATE_IN_PROGRESS, Payment::STATE_PAID ), true ) ) {
			try {
				$this->getPaymentProvider()->flagAsFraud( $payment->getId(), Payment::FRAUD_STATE_ERROR );
			} catch ( PaymentProviderException $e ) {
				throw new FraudServiceException( $e->getMessage() );
			}
			$order->updateStatus( 'failed', Payment::FRAUD_STATE_ERROR );

			throw new FraudServiceException( 'Potential fraud detected: payment state is not in progress or paid.' );
		}
	}
}
