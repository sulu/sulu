/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */

const {exec} = require('child_process'); //
const path = require('path');

// the sulu/skeleton uses the assets/admin directory as root directory for the administration interface javascript
// application. this application imports the sulu/sulu code by requiring the bundles from the the vendor directory
// in its package.json.
//
// unfortunately this setup causes various problems in the javascript ecosystem because the assets/admin directory
// is not an ancestor of the vendor/sulu/sulu directory which is used to require the code of bundles. for example,
// npm is not able to correctly dedupe packages that are required by a bundle and also in the assets/admin directory.
// because of this, packages that were not deduped might be included in the webpack build multiple times. this
// increases the size of the build and will lead to an error in case of the @ckeditor packages:
// http://ckeditor.com/docs/ckeditor5/latest/framework/guides/support/error-codes.html#error-ckeditor-duplicated-modules
//
// to prevent these problems, this file creates a assets/admin/node_modules/@sulu/vendor symlink and we use the
// symlinked vendor directory to require the bundles in the assets/admin/package.json.
// this makes the directory that is used for requiring the bundles a descendant of the assets/admin directory and
// allows npm to correctly dedupe the installed packages.

const parameters = process.argv.slice(2);

const from = parameters.length >= 1 ? parameters[0] : '../../vendor';
const to = parameters.length >= 2 ? parameters[1] : 'node_modules/@sulu/vendor';
const doNotCheckCurrentDirectory = parameters.length > 3 && parameters[2] === '--force';

if (
    doNotCheckCurrentDirectory
    || (
        path.basename(process.cwd()) === 'admin'
        && path.basename(path.dirname(process.cwd())) === 'assets'
    )
) {
    exec('npx symlink-dir ' + from + ' ' + to, (error) => {
        if (error) {
            throw new Error('Error occured while creating symlink: ' + error);
        }
    });
}
