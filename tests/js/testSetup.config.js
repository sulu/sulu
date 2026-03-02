// @flow
import 'core-js/features/string/replace-all';
import Enzyme from 'enzyme';
import Adapter from '@wojtekmaj/enzyme-adapter-react-17';
import {isObservableArray, toJS} from 'mobx';
import '@testing-library/jest-dom';

const DEFAULT_INTL_LOCALE = 'en-US';

function withDefaultLocale(IntlConstructor) {
    const WrappedConstructor = function(locales, options) {
        const resolvedLocales = locales === undefined ? DEFAULT_INTL_LOCALE : locales;

        return new IntlConstructor(resolvedLocales, options);
    };

    WrappedConstructor.prototype = IntlConstructor.prototype;
    Object.setPrototypeOf(WrappedConstructor, IntlConstructor);
    WrappedConstructor.supportedLocalesOf = IntlConstructor.supportedLocalesOf.bind(IntlConstructor);

    return WrappedConstructor;
}

function withDefaultLocaleArgument(localizedFunction) {
    return function(locales, options) {
        const resolvedLocales = locales === undefined ? DEFAULT_INTL_LOCALE : locales;

        return localizedFunction.call(this, resolvedLocales, options);
    };
}

function withDefaultLocaleSecondArgument(localizedFunction) {
    return function(value, locales, options) {
        const resolvedLocales = locales === undefined ? DEFAULT_INTL_LOCALE : locales;

        return localizedFunction.call(this, value, resolvedLocales, options);
    };
}

function withDefaultLocaleSingleArgument(localizedFunction) {
    return function(locales) {
        const resolvedLocales = locales === undefined ? DEFAULT_INTL_LOCALE : locales;

        return localizedFunction.call(this, resolvedLocales);
    };
}

Intl.Collator = withDefaultLocale(Intl.Collator);
Intl.DateTimeFormat = withDefaultLocale(Intl.DateTimeFormat);
Intl.DisplayNames = Intl.DisplayNames ? withDefaultLocale(Intl.DisplayNames) : Intl.DisplayNames;
Intl.ListFormat = Intl.ListFormat ? withDefaultLocale(Intl.ListFormat) : Intl.ListFormat;
Intl.NumberFormat = withDefaultLocale(Intl.NumberFormat);
Intl.PluralRules = withDefaultLocale(Intl.PluralRules);
Intl.RelativeTimeFormat = Intl.RelativeTimeFormat ? withDefaultLocale(Intl.RelativeTimeFormat) : Intl.RelativeTimeFormat;

Date.prototype.toLocaleString = withDefaultLocaleArgument(Date.prototype.toLocaleString);
Date.prototype.toLocaleDateString = withDefaultLocaleArgument(Date.prototype.toLocaleDateString);
Date.prototype.toLocaleTimeString = withDefaultLocaleArgument(Date.prototype.toLocaleTimeString);
Number.prototype.toLocaleString = withDefaultLocaleArgument(Number.prototype.toLocaleString);
String.prototype.localeCompare = withDefaultLocaleSecondArgument(String.prototype.localeCompare);
String.prototype.toLocaleLowerCase = withDefaultLocaleSingleArgument(String.prototype.toLocaleLowerCase);
String.prototype.toLocaleUpperCase = withDefaultLocaleSingleArgument(String.prototype.toLocaleUpperCase);
Array.prototype.toLocaleString = withDefaultLocaleArgument(Array.prototype.toLocaleString);
BigInt.prototype.toLocaleString = BigInt.prototype.toLocaleString
    ? withDefaultLocaleArgument(BigInt.prototype.toLocaleString)
    : BigInt.prototype.toLocaleString;

Object.defineProperty(window.navigator, 'language', {
    configurable: true,
    value: DEFAULT_INTL_LOCALE,
});

Object.defineProperty(window.navigator, 'languages', {
    configurable: true,
    value: [DEFAULT_INTL_LOCALE],
});

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
