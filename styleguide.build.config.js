/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */
/* eslint-disable no-undef */

const path = require('path');
require('./styleguide-globals.js');

const styleguideConfig = require('./styleguide.config');
styleguideConfig.styleguideDir = `styleguide/${globalThis.STYLEGUIDE_CURRENT_VERSION}`;
styleguideConfig.require.push(
    path.join(__dirname, 'styleguide-globals.js'),
    path.join(__dirname, 'node_modules/version-switcher/dist/main.js')
);

module.exports = styleguideConfig;
