<?php

namespace Alma\Gateway\Tests\Unit\Mocks;

/**
 * Minimal stand-in for WordPress' wpdb, which is not loaded in unit tests.
 * Mock it with onlyMethods(): MockBuilder::addMethods() is removed in PHPUnit 12.
 */
class WpdbStub {
	public $prefix = '';

	public function prepare( $query, ...$args ) {
	}

	public function get_row( $query = null ) {
	}

	public function get_var( $query = null ) {
	}

	public function update( $table, $data, $where ) {
	}
}
