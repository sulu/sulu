// @flow
import userStore from '../UserStore';
import Requester from '../../../services/Requester';
import initializer from '../../../services/Initializer';
import localizationStore from '../../../stores/LocalizationStore';

jest.mock('../../../services/Requester', () => ({
    get: jest.fn(),
    post: jest.fn(),
}));

jest.mock('../../../services/Initializer', () => ({
    initialize: jest.fn(),
}));

jest.mock('../../../stores/LocalizationStore', () => ({
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
    userStore.setResetSuccess(true);
    userStore.setUser(user);
    userStore.setContact(contact);
    userStore.setPersistentSetting('something', 'somevalue');

    expect(userStore.loggedIn).toBe(true);
    expect(userStore.loading).toBe(true);
    expect(userStore.loginError).toBe(true);
    expect(userStore.resetSuccess).toBe(true);
    expect(userStore.user).toEqual(user);
    expect(userStore.contact).toEqual(contact);
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

test('Should set persistent setting', () => {
    userStore.setPersistentSetting('categories.sortColumn', 'name');
    expect(userStore.getPersistentSetting('categories.sortColumn')).toEqual('name');

    userStore.setPersistentSetting('test.object', {abc: 'DEF', abc2: 'DEF2'});
    expect(userStore.getPersistentSetting('test.object')).toEqual({abc: 'DEF', abc2: 'DEF2'});
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
        expect(initializer.initialize).toBeCalled();

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
        expect(initializer.initialize).toBeCalled();
        expect(userStore.loading).toBe(true);
        expect(userStore.loggedIn).toBe(false);
        expect(userStore.loginError).toBe(false);
        expect(userStore.resetSuccess).toBe(false);
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

    const promise = userStore.resetPassword('test');
    expect(userStore.loading).toBe(true);

    return promise.then(() => {
        expect(Requester.post).toBeCalledWith('reset_url', {user: 'test'});
        expect(userStore.resetSuccess).toBe(true);
        expect(userStore.loggedIn).toBe(false);
        expect(userStore.loading).toBe(false);
    });
});

test('Should use different api to resend reset password', () => {
    Requester.post.mockReturnValue(Promise.resolve({}));
    userStore.setResetSuccess(true);

    const promise = userStore.resetPassword('test');
    expect(userStore.loading).toBe(true);

    return promise.then(() => {
        expect(Requester.post).toBeCalledWith('reset_resend_url', {user: 'test'});
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
