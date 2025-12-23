<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Gateway\Backend;

use Alma\Gateway\Infrastructure\Gateway\Backend\AbstractBackendGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class AbstractBackendGatewayTest extends TestCase {
	private AbstractBackendGateway $abstractBackendGateway;
	/**
	 * @var PayNowGateway
	 */
	private $payNowGatewayMock;
	/**
	 * @var PnxGateway
	 */
	private $pnxGatewayMock;
	/**
	 * @var PayLaterGateway
	 */
	private $payLaterGatewayMock;
	/**
	 * @var CreditGateway
	 */
	private $creditGatewayMock;

	public function setUp(): void {
		Monkey\setUp();

		Functions\when('__')->returnArg();

		// Mock les hooks WordPress pour éviter l'erreur
		Functions\when('add_filter')->justReturn(true);
		Functions\when('add_action')->justReturn(true);
		Functions\when('get_option')->justReturn(true);
		Functions\when('almalog')->justReturn(true);

		$this->payNowGatewayMock = $this->createMock(PayNowGateway::class);
		$this->pnxGatewayMock = $this->createMock(PnxGateway::class);
		$this->payLaterGatewayMock = $this->createMock(PayLaterGateway::class);
		$this->creditGatewayMock = $this->createMock(CreditGateway::class);
		$this->abstractBackendGateway = new AbstractBackendGateway();
	}

	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testCustomizePaymentButtonsTextFieldsetWithoutGatewaysEnabled(): void {
		$this->payNowGatewayMock->expects( $this->once() )->method('is_enabled')->willReturn(false);
		$this->pnxGatewayMock->expects( $this->once() )->method('is_enabled')->willReturn(false);
		$this->payLaterGatewayMock->expects( $this->once() )->method('is_enabled')->willReturn(false);
		$this->creditGatewayMock->expects( $this->once() )->method('is_enabled')->willReturn(false);
		$expected = [];
		$this->assertEquals($expected, $this->abstractBackendGateway->customize_payment_buttons_text_fieldset(
			$this->payNowGatewayMock,
			$this->pnxGatewayMock,
			$this->payLaterGatewayMock,
			$this->creditGatewayMock
		));
	}

	public function testCustomizePaymentButtonsTextFieldsetWithPayNowAndPnxEnabled(): void {
		$this->payNowGatewayMock->method('is_enabled')->willReturn(true);
		$this->pnxGatewayMock->method('is_enabled')->willReturn(true);
		$this->payLaterGatewayMock->method('is_enabled')->willReturn(false);
		$this->creditGatewayMock->method('is_enabled')->willReturn(false);
		$expected = [
			'customize_payment_buttons_text_section' => [
				'title'       => '<hr>→ Customize payment button text',
				'type'        => 'title',
				'description' => 'Customize the text displayed on the Alma payment button on the checkout page',
				'desc_tip'    => false,
			],
			'paynow_title' => [
				'title' => '<h3>Pay now:</h3>',
				'type'  => 'title',
			],
			'paynow_title_field' => [
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'This controls the payment method name which the user sees during checkout.',
				'desc_tip'    => true,
				'default'     => 'Pay by credit card',
			],
			'paynow_description_field' => [
				'title'       => 'Description',
				'type'        => 'text',
				'description' => 'This controls the payment method description which the user sees during checkout.',
				'desc_tip'    => true,
				'default'     => 'Fast and secured payments',
			],
			'pnx_title' => [
				'title' => '<h3>Payments in 2, 3 and 4 installments:</h3>',
				'type'  => 'title',
			],
			'pnx_title_field' => [
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'This controls the payment method name which the user sees during checkout.',
				'desc_tip'    => true,
				'default'     => 'Pay in installments',
			],
			'pnx_description_field' => [
				'title'       => 'Description',
				'type'        => 'text',
				'description' => 'This controls the payment method description which the user sees during checkout.',
				'desc_tip'    => true,
				'default'     => 'Fast and secure payment by credit card',
			],
		];
		$this->assertEquals($expected, $this->abstractBackendGateway->customize_payment_buttons_text_fieldset(
			$this->payNowGatewayMock,
			$this->pnxGatewayMock,
			$this->payLaterGatewayMock,
			$this->creditGatewayMock
		));
	}
}