/* eslint-disable flowtype/require-valid-file-annotation */
'use strict';

module.exports = { // eslint-disable-line
    'extends': 'stylelint-config-standard',
    'rules': {
        'selector-pseudo-class-no-unknown': [ true, {
            ignorePseudoClasses: [
                'global',
                'export',
            ],
        }],
        'indentation': 4,
        'number-leading-zero': 'never',
        'string-quotes': 'single',
        'max-line-length': 120,
    },
};
