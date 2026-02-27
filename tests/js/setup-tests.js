/**
 * Setup file for Jest tests
 * Configures the test environment before running tests
 */

// Mock jQuery
global.$ = global.jQuery = require('jquery');

// Mock WordPress globals
global.wp = {
    i18n: {
        __: (text) => text,
        _x: (text) => text,
        _n: (single, plural, number) => number === 1 ? single : plural,
    }
};

// Mock WooCommerce globals
global.wc = {};

// Mock Alma SDK
global.Alma = {
    InPage: {
        initialize: jest.fn((config) => ({
            startPayment: jest.fn(),
            unmount: jest.fn(),
        }))
    }
};

// Mock alma_in_page_settings
global.alma_in_page_settings = {
    merchant_id: 'merchant_test123',
    environment: 'test'
};

// Mock console methods to avoid noise in tests
global.console = {
    ...console,
    log: jest.fn(),
    debug: jest.fn(),
    info: jest.fn(),
    warn: jest.fn(),
    error: jest.fn(),
};

// Add custom matchers if needed
expect.extend({
    toBeWithinRange(received, floor, ceiling) {
        const pass = received >= floor && received <= ceiling;
        if (pass) {
            return {
                message: () =>
                    `expected ${received} not to be within range ${floor} - ${ceiling}`,
                pass: true,
            };
        } else {
            return {
                message: () =>
                    `expected ${received} to be within range ${floor} - ${ceiling}`,
                pass: false,
            };
        }
    },
});

