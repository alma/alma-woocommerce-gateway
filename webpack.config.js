const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config');

const config = {
    ...defaultConfig,
    entry: path.resolve(process.cwd(), 'src', 'assets', 'js', 'alma-checkout-blocks.js' ),
    output: {
        filename: 'alma-checkout-blocks.js',
        path: path.resolve(process.cwd(), 'src', 'build'),
    },
}

module.exports = config;