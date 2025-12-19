<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Gateway\Backend;

use Alma\Gateway\Infrastructure\Gateway\Backend\AbstractBackendGateway;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class AbstractBackendGatewayTest extends TestCase {
	private AbstractBackendGateway $abstractBackendGateway;

	public function setUp(): void {
		Monkey\setUp();

		Functions\when('__')->returnArg();

		// Mock les hooks WordPress pour éviter l'erreur
		Functions\when('add_filter')->justReturn(true);
		Functions\when('add_action')->justReturn(true);
		$this->abstractBackendGateway = new AbstractBackendGateway();
	}

	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testCustomizePaymentButtonsTextFieldsetWithoutGatewaysEnabled(): void {
		$active_gateways = [];
		$expected = [];
		$this->assertEquals($expected, $this->abstractBackendGateway->customize_payment_buttons_text_fieldset($active_gateways));
	}

	public function testCustomizePaymentButtonsTextFieldsetWithWrongStringInArray(): void {
		$active_gateways = ['wrong_string'];
		$expected = [];
		$this->assertEquals($expected, $this->abstractBackendGateway->customize_payment_buttons_text_fieldset($active_gateways));
	}

	public function testCustomizePaymentButtonsTextFieldsetWithPayNowAndPnxEnabled(): void {
		$active_gateways = [
			'paynow',
			'pnx',
		];
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
		$this->assertEquals($expected, $this->abstractBackendGateway->customize_payment_buttons_text_fieldset($active_gateways));
	}
}