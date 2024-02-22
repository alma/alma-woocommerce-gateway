const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config');

const config = {
    ...defaultConfig,
    entry: {
        'alma-checkout-blocks': path.resolve(process.cwd(), 'src', 'assets', 'js', 'alma-checkout-blocks.js'),
        // 'alma-checkout-blocks-pay-later': path.resolve(process.cwd(), 'src', 'assets', 'js', 'alma-checkout-blocks-pay-later.js'),
        // 'alma-checkout-blocks-pay-more-than-four': path.resolve(process.cwd(), 'src', 'assets', 'js', 'alma-checkout-blocks-pay-more-than-four.js'),
    },
    output: {
        path: path.resolve(process.cwd(), 'src', 'build'),
    },
}

module.exports = config;