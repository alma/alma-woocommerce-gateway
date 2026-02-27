module.exports = {
    preset: '@wordpress/jest-preset-default',
    testEnvironment: 'jsdom',
    testMatch: [
        '**/__tests__/**/*.js',
        '**/?(*.)+(spec|test).js'
    ],
    moduleNameMapper: {
        '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
    },
    setupFilesAfterEnv: ['<rootDir>/tests/js/setup-tests.js'],
    collectCoverageFrom: [
        'tests/js/**/*.test.js',
        '!**/node_modules/**',
        '!**/vendor/**',
        '!**/build/**',
        '!**/src/**',
        '!**/assets/**',
    ],
    coveragePathIgnorePatterns: [
        '/node_modules/',
        '/vendor/',
        '/build/',
        '/src/',
        '/assets/',
        '/tests/js/setup-tests.js',
        '/tests/js/TEMPLATE.test.js.example',
        'jest.config.js'
    ],
    coverageDirectory: 'coverage',
    coverageReporters: ['text', 'lcov', 'html'],
    globals: {
        'wp': {},
        'wc': {},
        'alma_in_page_settings': {
            merchant_id: 'merchant_test',
            environment: 'test'
        }
    },
    testPathIgnorePatterns: [
        '/node_modules/',
        '/vendor/',
        '/build/',
        '\\.example\\.js$'
    ]
};

