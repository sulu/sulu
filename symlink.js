/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */

const {exec} = require('child_process'); //
const path = require('path');

const parameters = process.argv.slice(2);

const from = parameters.length > 1 ? parameters[0] : '../../vendor';
const to = parameters.length > 2 ? parameters[1] : 'node_modules/@sulu/vendor';
const ignoreCheck = parameters.length > 3 && parameters[2] === 'ignoreCheck';

if (
    ignoreCheck
    || (
        path.basename(process.cwd()) === 'admin'
        && path.basename(path.dirname(process.cwd())) === 'assets'
    )
) {
    exec('npx symlink-dir ' + from + ' ' + to, (error) => {
        if (error) {
            console.error('Error occured while creating symlink: ' + error); // eslint-disable-line no-console
        }
    });
}
