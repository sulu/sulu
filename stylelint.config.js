/* eslint-disable flowtype/require-valid-file-annotation */
'use strict';

module.exports = { // eslint-disable-line
    'extends': 'stylelint-config-standard-scss',
    'rules': {
        'scss/dollar-variable-pattern': null,
        'selector-class-pattern': null,
        'scss/dollar-variable-empty-line-before': null,
        'scss/at-import-partial-extension': null,
        'shorthand-property-no-redundant-values': null,
        'declaration-block-no-redundant-longhand-properties': null,
        'value-keyword-case': ['lower', {
            camelCaseSvgKeywords: true,
        }],
        'indentation': 4,
        'max-line-length': 120,
        'no-descending-specificity': null,
        'number-leading-zero': 'never',
        'selector-pseudo-class-no-unknown': [ true, {
            ignorePseudoClasses: [
                'global',
                'export',
            ],
        }],
        'string-quotes': 'single',
    },
};
