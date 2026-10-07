/* eslint-disable flowtype/require-valid-file-annotation */
'use strict';

module.exports = { // eslint-disable-line
    'extends': 'stylelint-config-standard-scss',
    'rules': {
        'scss/dollar-variable-pattern': null,
        'scss/dollar-variable-no-missing-interpolation': null,
        'selector-class-pattern': null,
        'scss/dollar-variable-empty-line-before': null,
        'scss/load-partial-extension': null,
        'shorthand-property-no-redundant-values': null,
        'declaration-block-no-redundant-longhand-properties': null,
        'value-keyword-case': ['lower', {
            camelCaseSvgKeywords: true,
        }],
        'no-descending-specificity': null,
        'media-feature-range-notation': 'prefix',
        'declaration-property-value-keyword-no-deprecated': null,
        'selector-pseudo-class-no-unknown': [ true, {
            ignorePseudoClasses: [
                'global',
                'export',
            ],
        }],
    },
};
