// @flow
import 'core-js/features/string/replace-all';
import Enzyme from 'enzyme';
import Adapter from '@wojtekmaj/enzyme-adapter-react-17';
import {isObservableArray, toJS} from 'mobx';
import '@testing-library/jest-dom';

Enzyme.configure({adapter: new Adapter()});

function mobxAwareEqualityTester(a, b, customTesters) {
    const isAObservable = isObservableArray(a);
    const isBObservable = isObservableArray(b);

    if (!isAObservable && !isBObservable) {
        return undefined;
    }

    const normalizedA = isAObservable ? toJS(a) : a;
    const normalizedB = isBObservable ? toJS(b) : b;

    return this.equals(normalizedA, normalizedB, customTesters);
}

expect.addEqualityTesters([mobxAwareEqualityTester]);

const oldToLocaleString = Number.prototype.toLocaleString;
// $FlowFixMe[cannot-write]
Number.prototype.toLocaleString = function(locale, options) {
    return oldToLocaleString.call(this, locale || 'en-US', options);
};

const oldDateToLocaleString = Date.prototype.toLocaleString;
// $FlowFixMe[cannot-write]
Date.prototype.toLocaleString = function(locale, options) {
    return oldDateToLocaleString.call(this, locale || 'en-US', options);
};

const oldDateToLocaleDateString = Date.prototype.toLocaleDateString;
// $FlowFixMe[cannot-write]
Date.prototype.toLocaleDateString = function(locale, options) {
    return oldDateToLocaleDateString.call(this, locale || 'en-US', options);
};

jest.mock('sulu-admin-bundle/services/Config', () => ({
    endpoints: {
        'config': 'config_url',
        'translations': 'translations_url',
        'loginCheck': 'login_check_url',
        'logout': 'logout_url',
        'profileSettings': 'profile_settings_url',
        'forgotPasswordReset': 'forgot_password_reset_url',
        'resetPassword': 'reset_password',
        'resources': 'resources_url/:resource',
        'routing': 'routing',
    },
    translations: ['en', 'de'],
    fallbackLocale: 'en',
}));

Object.defineProperty(window, 'matchMedia', { // see https://github.com/ckeditor/ckeditor5/issues/16368
    writable: true,
    value: jest.fn().mockImplementation((query) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: jest.fn(), // deprecated
        removeListener: jest.fn(), // deprecated
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
    })),
});
