const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

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
    target: 'node',
    module: {
        rules: [
            ...defaultConfig.module.rules,
            {
                test: /\.css$/,
                use: [
                    "style-loader",
                    "css-loader",
                    "postcss-loader",
                    {
                        loader: 'sass-loader',
                    },
                ],
                include: path.resolve(__dirname, '../src/build'),
            },
        ],
    },
}

module.exports = config;