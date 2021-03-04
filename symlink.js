/* eslint-disable flowtype/require-valid-file-annotation */

const { exec } = require('child_process');
const path = require('path');

const arguments = process.argv.slice(2);

const from = arguments.length > 1 ? arguments[0] : '../../vendor';
const to = arguments.length > 2 ? arguments[1] : 'node_modules/@sulu/vendor';

console.log('npx symlink-dir ' + from + ' ' + to);

exec('npx symlink-dir ' + from + ' ' + to, (error) => {
    if (error) {
        console.error('Error occured while creating symlink: ' + error);
    }
});
