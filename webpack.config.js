const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

const config = {
    ...defaultConfig,
    externals: {
        ...defaultConfig.externals,
        'react': 'React',
        'react-dom': 'ReactDOM',
        '@wordpress/element': 'wp.element'
    }
};

module.exports = config;
