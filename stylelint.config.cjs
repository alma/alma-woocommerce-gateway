module.exports = {
    plugins: ['stylelint-prettier'],
    extends: ['stylelint-config-standard'],
    rules: {
        'color-hex-length': null,
        'prettier/prettier': true,
        'at-rule-no-unknown': true,
        'no-descending-specificity': null,
        'selector-pseudo-class-no-unknown': [
            true,
            {
                ignorePseudoClasses: ['global'],
            },
        ],
        // kebab-case does not suit CSS modules used in React
        'selector-class-pattern': null,
    },
}
