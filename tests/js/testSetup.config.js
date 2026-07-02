// @flow
// eslint-disable-next-line import/no-nodejs-modules
import {TextDecoder, TextEncoder} from 'util';
import 'core-js/features/string/replace-all';
import Enzyme from 'enzyme';
import Adapter from '@wojtekmaj/enzyme-adapter-react-17';
import {isObservableArray, toJS} from 'mobx';
import '@testing-library/jest-dom';

Enzyme.configure({adapter: new Adapter()});

Object.defineProperty(window, 'TextDecoder', {
    writable: true,
    value: TextDecoder,
});

Object.defineProperty(window, 'TextEncoder', {
    writable: true,
    value: TextEncoder,
});

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

// copied from: https://github.com/evelynhathaway/jest-location-mock
const originalLocationRef = {current: null};

(function() {
    if (typeof window === 'undefined') {
        return;
    }

    if (!(window._globalProxy)) {
        // eslint-disable-next-line max-len
        throw new Error('window._globalProxy is not defined. This mock relies on an internal JSDOM property that may have changed. Please report this issue to the jest-location-mock.');
    }

    originalLocationRef.current = window.location;

    const locationMock = {
        assign: jest.fn(),
        href: 'http://localhost',
        replace: jest.fn(),
        reload: jest.fn(),
        origin: 'http://localhost',
    };

    jest.spyOn(locationMock, 'assign').mockName('window.location.assign');
    jest.spyOn(locationMock, 'reload').mockName('window.location.reload');
    jest.spyOn(locationMock, 'replace').mockName('window.location.replace');

    // I am unsure how long this internal property will work, but I cannot find any other way to shadow the
    // unconfigurable `window.location` property in JSDOM v21+
    // https://github.com/jsdom/jsdom/blob/57bbf9a5c2bd32d3c811068480dee3cc8da3dd34/lib/jsdom/browser/Window.js#L54-L60
    window._globalProxy = new Proxy(window, {
        get(target, property, receiver) {
            if (property === 'location') {
                return locationMock;
            }
            return Reflect.get(target, property, receiver);
        },
        set(target, property, value) {
            if (property === 'location') {
                locationMock.href = value;
                return true;
            }
            return Reflect.set(target, property, value);
        },
    });
})();
