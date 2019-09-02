// @flow
import userStore from '../userStore';
import Requester from '../../../services/Requester';
import initializer from '../../../services/initializer';
import localizationStore from '../../../stores/localizationStore';

jest.mock('debounce', () => jest.fn((callback) => callback));

jest.mock('../../../services/Requester', () => ({
    get: jest.fn(),
    post: jest.fn(),
    patch: jest.fn(),
}));

jest.mock('../../../services/initializer', () => ({
    initialize: jest.fn(),
}));

jest.mock('../../../stores/localizationStore', () => ({
    loadLocalizations: jest.fn(() => new Promise((resolve) => resolve([]))),
}));

beforeEach(() => {
    userStore.clear();
});

test('Should clear the user store', () => {
    const user = {id: 1, locale: 'cool_locale', settings: {}, username: 'test'};
    const contact = {id: 12, avatar: undefined, firstName: 'Firsti', lastName: 'Lasti', fullName: 'Firsti Lasti'};
    userStore.setLoggedIn(true);
    userStore.setLoading(true);
    userStore.setLoginError(true);
    userStore.setForgotPasswordSuccess(true);
    userStore.setUser(user);
    userStore.setContact(contact);
    userStore.setPersistentSetting('something', 'somevalue');
    userStore.setFullName(contact.firstName + ' ' + contact.lastName);

    expect(userStore.loggedIn).toBe(true);
    expect(userStore.loading).toBe(true);
    expect(userStore.loginError).toBe(true);
    expect(userStore.forgotPasswordSuccess).toBe(true);
    expect(userStore.user).toEqual(user);
    expect(userStore.contact).toEqual(contact);
    if (userStore.contact){
        expect(userStore.contact.fullName).toEqual(contact.firstName + ' ' + contact.lastName);
    }
    expect(userStore.persistentSettings.size).toBe(1);

    userStore.clear();

    expect(userStore.persistentSettings.size).toBe(0);
});

test('Should return the locale of the user as system-locale', () => {
    userStore.setUser({
        id: 5,
        locale: 'de',
        settings: {},
        username: 'test',
    });

    expect(userStore.systemLocale).toEqual('de');
});

test('Should return the fallback locale as system-locale if the user has none set', () => {
    expect(userStore.systemLocale).toEqual('en');
});

test('Should return the fallback-locale as content-locale if the user is not set', () => {
    expect(userStore.contentLocale).toEqual('en');
});

test('Should load and set first default-localization as content-locale when user is set', () => {
    const localizationsPromise = new Promise((resolve) => resolve([
        {locale: 'cz', default: false},
        {locale: 'ru', default: true},
        {locale: 'de', default: false},
    ]));
    localizationStore.loadLocalizations.mockReturnValueOnce(localizationsPromise);

    userStore.setUser({
        id: 5,
        locale: 'de',
        settings: {},
        username: 'test',
    });

    return localizationsPromise.then(() => {
        expect(localizationStore.loadLocalizations).toBeCalled();
        expect(userStore.contentLocale).toEqual('ru');
    });
});

test('Should load and set first localization as content-locale if there is no default-localiztion', () => {
    const localizationsPromise = new Promise((resolve) => resolve([
        {locale: 'cz', default: false},
        {locale: 'ru', default: false},
        {locale: 'de', default: false},
    ]));
    localizationStore.loadLocalizations.mockReturnValueOnce(localizationsPromise);

    userStore.setUser({
        id: 5,
        locale: 'de',
        settings: {},
        username: 'test',
    });

    return localizationsPromise.then(() => {
        expect(localizationStore.loadLocalizations).toBeCalled();
        expect(userStore.contentLocale).toEqual('cz');
    });
});

test('Should return initial persistent settings', () => {
    userStore.setUser({
        id: 5,
        locale: 'de',
        settings: {
            test1: 'value1',
        },
        username: 'test',
    });

    expect(userStore.getPersistentSetting('test1')).toEqual('value1');
});

test('Should set persistent setting', () => {
    userStore.setPersistentSetting('categories.sortColumn', 'name');
    expect(userStore.getPersistentSetting('categories.sortColumn')).toEqual('name');

    userStore.setPersistentSetting('test.object', {abc: 'DEF', abc2: 'DEF2'});
    expect(userStore.getPersistentSetting('test.object')).toEqual({abc: 'DEF', abc2: 'DEF2'});
});

test('Should update persistent settings of server with a debounce delay of 5 seconds', () => {
    userStore.setPersistentSetting('test1', 'value1');
    expect(Requester.patch).toBeCalledWith('profile_settings_url', {test1: 'value1'});

    userStore.setPersistentSetting('test2', 'value2');
    expect(Requester.patch).toBeCalledWith('profile_settings_url', {test2: 'value2'});
});

test('Should not update persistent setting if the value did not change', () => {
    userStore.setPersistentSetting('test1', 'test');
    expect(Requester.patch).toBeCalledWith('profile_settings_url', {test1: 'test'});

    Requester.patch.mockReset();
    userStore.setPersistentSetting('test1', 'test');
    expect(Requester.patch).not.toBeCalled();
});

test('Should also update persistent setting with the value of false on the server', () => {
    userStore.setPersistentSetting('test1', false);
    expect(Requester.patch).toBeCalledWith('profile_settings_url', {test1: false});
});

test('Should login', () => {
    const loginPromise = Promise.resolve({});
    const initializePromise = Promise.resolve({});

    Requester.post.mockReturnValue(loginPromise);
    initializer.initialize.mockReturnValue(initializePromise);

    userStore.login('test', 'password');
    expect(userStore.loading).toBe(true);

    return loginPromise.then(() => {
        expect(Requester.post).toBeCalledWith('login_check_url', {username: 'test', password: 'password'});
        expect(initializer.initialize).toBeCalledWith(true);

        return initializePromise.then(() => {
            expect(userStore.loading).toBe(false);
        });
    });
});

test('Should login without initializing when it`s the same user', () => {
    const user = {id: 1, locale: 'cool_locale', settings: {}, username: 'test'};
    const loginPromise = Promise.resolve({});
    Requester.post.mockReturnValue(loginPromise);
    userStore.setUser(user);

    userStore.login('test', 'password');
    expect(userStore.loading).toBe(true);

    return loginPromise.then(() => {
        expect(Requester.post).toBeCalledWith('login_check_url', {username: 'test', password: 'password'});
        expect(initializer.initialize).not.toBeCalled();
        expect(userStore.loading).toBe(false);
        expect(userStore.loggedIn).toBe(true);
    });
});

test('Should login with initializing when it`s not the same user', () => {
    const user = {id: 1, locale: 'cool_locale', settings: {}, username: 'test'};
    const loginPromise = Promise.resolve({});
    const initializePromise = Promise.resolve({});
    userStore.setUser(user);

    expect(Requester.post).not.toBeCalled();

    userStore.login('other-user-than-test', 'password');
    expect(userStore.loading).toBe(true);

    return loginPromise.then(() => {
        expect(Requester.post).toBeCalledWith(
            'login_check_url',
            {username: 'other-user-than-test', password: 'password'}
        );
        expect(initializer.initialize).toBeCalledWith(true);
        expect(userStore.loading).toBe(true);
        expect(userStore.loggedIn).toBe(false);
        expect(userStore.loginError).toBe(false);
        expect(userStore.forgotPasswordSuccess).toBe(false);
        expect(userStore.user).toBeUndefined();
        expect(userStore.contact).toBeUndefined();

        return initializePromise.then(() => {
            expect(userStore.loading).toBe(false);
        });
    });
});

test('Should show error when login is not working and error status is 401', () => {
    Requester.post.mockReturnValue(Promise.reject({status: 401}));

    const loginPromise = userStore.login('test', 'password');
    expect(userStore.loading).toBe(true);

    return loginPromise
        .then(() => {
            expect(Requester.post).toBeCalledWith('login_check_url', {username: 'test', password: 'password'});
            expect(initializer.initialize).not.toBeCalled();
            expect(userStore.loginError).toBe(true);
            expect(userStore.loggedIn).toBe(false);
            expect(userStore.loading).toBe(false);
        });
});

test('Should reset password', () => {
    Requester.post.mockReturnValue(Promise.resolve({}));

    const promise = userStore.forgotPassword('test');
    expect(userStore.loading).toBe(true);

    return promise.then(() => {
        expect(Requester.post).toBeCalledWith('forgot_password_reset_url', {user: 'test'});
        expect(userStore.forgotPasswordSuccess).toBe(true);
        expect(userStore.loggedIn).toBe(false);
        expect(userStore.loading).toBe(false);
    });
});

test('Should use different api to resend reset password', () => {
    Requester.post.mockReturnValue(Promise.resolve({}));
    userStore.setForgotPasswordSuccess(true);

    const promise = userStore.forgotPassword('test');
    expect(userStore.loading).toBe(true);

    return promise.then(() => {
        expect(Requester.post).toBeCalledWith('forgot_password_resend_url', {user: 'test'});
        expect(userStore.loading).toBe(false);
    });
});

test('Should logout', () => {
    userStore.setLoggedIn(true);
    Requester.get.mockReturnValue(Promise.resolve({}));

    const promise = userStore.logout();

    return promise.then(() => {
        expect(Requester.get).toBeCalledWith('logout_url');
        expect(userStore.loggedIn).toBe(false);
    });
});
