<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Infrastructure\Helper\SessionHelper;

class BusinessEventsService
{
	const ALMA_BUSINESS_EVENT_TABLE = 'alma_business_data';
	const ALMA_CART_ID       = 'alma_cart_id';
	private SessionHelper $session;

	public function __construct(SessionHelper $session) {
		$this->session = $session;
	}
	public function onCartInitiated() {
		//  Get cart id on session
		$alma_cart_id = $this->session->getSession( self::ALMA_CART_ID, null );
		if ( ! $alma_cart_id ) {
			// Set cart id in session
			$alma_cart_id = uniqid( 'alma_cart_' );
			$this->session->setSession( self::ALMA_CART_ID, $alma_cart_id );
		}
	}
}
