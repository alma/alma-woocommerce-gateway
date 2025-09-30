<?php

namespace Alma\Gateway\Tests\Unit\Mocks;

use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use PHPUnit\Framework\TestCase;

class OrderAdapterMockFactory {

	public static function createMock( TestCase $testCase ) {
		$orderInterface = $testCase
			->getMockBuilder( OrderAdapter::class )
			->disableOriginalConstructor()
			->getMock();
		$orderInterface->method( 'getBillingFirstName' )->willReturn( 'John' );
		$orderInterface->method( 'getBillingLastName' )->willReturn( 'Doe' );
		$orderInterface->method( 'getBillingEmail' )->willReturn( 'john.doe@example.com' );
		$orderInterface->method( 'getBillingPhone' )->willReturn( '1234567890' );
		$orderInterface->method( 'getBillingCompany' )->willReturn( 'Acme Corp' );
		$orderInterface->method( 'hasBillingAddress' )->willReturn( true );
		$orderInterface->method( 'getBillingAddress1' )->willReturn( '123 Main St' );
		$orderInterface->method( 'getBillingAddress2' )->willReturn( 'Apt 4B' );
		$orderInterface->method( 'getBillingPostcode' )->willReturn( '12345' );
		$orderInterface->method( 'getBillingCity' )->willReturn( 'Metropolis' );
		$orderInterface->method( 'getBillingCountry' )->willReturn( 'FR' );
		$orderInterface->method( 'hasShippingAddress' )->willReturn( true );
		$orderInterface->method( 'getShippingFirstName' )->willReturn( 'Jane' );
		$orderInterface->method( 'getShippingLastName' )->willReturn( 'Doe' );
		$orderInterface->method( 'getShippingCompany' )->willReturn( 'Acme Corp' );
		$orderInterface->method( 'getShippingAddress1' )->willReturn( '456 Elm St' );
		$orderInterface->method( 'getShippingAddress2' )->willReturn( 'Suite 5C' );
		$orderInterface->method( 'getShippingPostcode' )->willReturn( '67890' );
		$orderInterface->method( 'getShippingCity' )->willReturn( 'Gotham' );
		$orderInterface->method( 'getShippingCountry' )->willReturn( 'FR' );
		$orderInterface->method( 'getShippingEmail' )->willReturn( 'jane.doe@example.com' );
		$orderInterface->method( 'getOrderNumber' )->willReturn( 'ORDER123' );
		$orderInterface->method( 'getEditOrderUrl' )->willReturn( 'http://example.com/wp-admin/post.php?post=123&action=edit' );
		$orderInterface->method( 'getViewOrderUrl' )->willReturn( 'http://example.com/wp-admin/post.php?post=123&action=view' );
		$orderInterface->method( 'getCustomerNote' )->willReturn( 'Please deliver between 9 AM and 5 PM.' );

		return $orderInterface;
	}

	public static function resultArray(): array {
		return [
			'is_business'            => true,
			'business_name'          => 'Acme Corp',
			'first_name'             => 'John',
			'last_name'              => 'Doe',
			'email'                  => 'john.doe@example.com',
			'phone'                  => '1234567890',
			'addresses'              => [
				[
					'first_name'  => 'John',
					'last_name'   => 'Doe',
					'email'       => 'john.doe@example.com',
					'line1'       => '123 Main St',
					'line2'       => 'Apt 4B',
					'postal_code' => '12345',
					'city'        => 'Metropolis',
					'country'     => 'FR',
					'company'     => 'Acme Corp'
				],
				[
					'first_name'  => 'Jane',
					'last_name'   => 'Doe',
					'email'       => 'jane.doe@example.com',
					'line1'       => '456 Elm St',
					'line2'       => 'Suite 5C',
					'postal_code' => '67890',
					'city'        => 'Gotham',
					'country'     => 'FR',
					'company'     => 'Acme Corp'
				]
			],
			'banking_data_collected' => false,
		];
	}
}