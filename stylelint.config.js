/* eslint-disable flowtype/require-valid-file-annotation */
'use strict';

module.exports = { // eslint-disable-line
    'extends': 'stylelint-config-standard',
    'rules': {
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
