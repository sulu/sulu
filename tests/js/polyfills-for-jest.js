// @flow
// eslint-disable-next-line import/no-nodejs-modules
import {TextDecoder, TextEncoder} from 'util';
import 'core-js/features/string/replace-all';

Object.defineProperty(window, 'TextDecoder', {
    writable: true,
    value: TextDecoder,
});

Object.defineProperty(window, 'TextEncoder', {
    writable: true,
    value: TextEncoder,
});
