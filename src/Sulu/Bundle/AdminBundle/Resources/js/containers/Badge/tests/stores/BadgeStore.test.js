// @flow
import 'whatwg-fetch';
import SymfonyRouting from 'fos-jsrouting/router';
import BadgeStore from '../../stores/BadgeStore';
import Router from '../../../../services/Router';
import Requester from '../../../../services/Requester';

jest.mock('debounce', () => jest.fn((callback) => callback));

jest.mock('../../../../services/Requester', () => ({
    get: jest.fn(),
}));

Requester.handleResponseHooks = [];

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.attributes = {
        id: 5,
        locale: 'en',
    };
}));

test('Should load data using the Requester', () => {
    SymfonyRouting.generate.mockImplementation((routeName, params) => {
        return routeName + '?' + Object.keys(params).map((key) => key + '=' + params[key]).join('&');
    });

    const promise = Promise.resolve({value: 2});
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    const router = new Router({});
    const badgeStore = new BadgeStore(
        router,
        'foo',
        '/value',
        {
            limit: 0,
            entityClass: 'Foo',
        },
        {
            id: 'entityId',
            locale: 'locale',
        }
    );
    badgeStore.load();

    expect(Requester.get).toBeCalledWith('foo?entityId=5&locale=en&limit=0&entityClass=Foo');

    return promise.then(() => {
        expect(badgeStore.value).toEqual('2');
    });
});

test('Should load data without datapath', () => {
    SymfonyRouting.generate.mockImplementation((routeName, params) => {
        return routeName + '?' + Object.keys(params).map((key) => key + '=' + params[key]).join('&');
    });

    const promise = Promise.resolve('hello');
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    const router = new Router({});
    const badgeStore = new BadgeStore(
        router,
        'foo',
        null,
        {
            limit: 0,
            entityClass: 'Foo',
        },
        {
            id: 'entityId',
            locale: 'locale',
        }
    );
    badgeStore.load();

    expect(Requester.get).toBeCalledWith('foo?entityId=5&locale=en&limit=0&entityClass=Foo');

    return promise.then(() => {
        expect(badgeStore.value).toEqual('hello');
    });
});

test('Should load data on response hook callback', () => {
    SymfonyRouting.generate.mockImplementation((routeName, params) => {
        return routeName + '?' + Object.keys(params).map((key) => key + '=' + params[key]).join('&');
    });

    const promise = Promise.resolve('hello');
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    const router = new Router({});
    const badgeStore = new BadgeStore(
        router,
        'foo',
        null,
        {
            limit: 0,
            entityClass: 'Foo',
        },
        {
            id: 'entityId',
            locale: 'locale',
        }
    );
    badgeStore.load();

    expect(Requester.handleResponseHooks).toHaveLength(1);

    // Initial request
    expect(Requester.get).toHaveBeenCalledTimes(1);
    expect(Requester.get).toHaveBeenNthCalledWith(1, 'foo?entityId=5&locale=en&limit=0&entityClass=Foo');

    // Should not perform request because of a "GET" request
    const response1 = new Response();
    response1.url = 'http://sulu.lo/admin/api/anything';
    Requester.handleResponseHooks[0](
        response1,
        {method: 'GET'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(1);

    // Should perform request
    const response2 = new Response();
    response2.url = 'http://sulu.lo/admin/api/anything';
    Requester.handleResponseHooks[0](
        response2,
        {method: 'POST'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(2);
    expect(Requester.get).toHaveBeenNthCalledWith(2, 'foo?entityId=5&locale=en&limit=0&entityClass=Foo');

    // Should perform request
    const response3 = new Response();
    response3.url = 'http://sulu.lo/admin/api/anything';
    Requester.handleResponseHooks[0](
        response3,
        {method: 'PUT'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(3);
    expect(Requester.get).toHaveBeenNthCalledWith(3, 'foo?entityId=5&locale=en&limit=0&entityClass=Foo');

    // Should perform request
    const response4 = new Response();
    response4.url = 'http://sulu.lo/admin/api/anything';
    Requester.handleResponseHooks[0](
        response4,
        {method: 'PATCH'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(4);
    expect(Requester.get).toHaveBeenNthCalledWith(4, 'foo?entityId=5&locale=en&limit=0&entityClass=Foo');

    // Should perform request
    const response5 = new Response();
    response5.url = 'http://sulu.lo/admin/api/anything';
    Requester.handleResponseHooks[0](
        response5,
        {method: 'DELETE'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(5);
    expect(Requester.get).toHaveBeenNthCalledWith(5, 'foo?entityId=5&locale=en&limit=0&entityClass=Foo');

    // Should not perform request because of a collaboration request
    const response6 = new Response();
    response6.url = 'http://sulu.lo/admin/api/collaborations?id=1234&resourceKey=pages';
    Requester.handleResponseHooks[0](
        response6,
        {method: 'PUT'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(5);

    // Should not perform request because of a preview request
    const response7 = new Response();
    response7.url = 'https://fullsulu.lo/admin/preview/update?id=1234&locale=en&provider=pages';
    Requester.handleResponseHooks[0](
        response7,
        {method: 'POST'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(5);

    badgeStore.destroy();
    expect(Requester.handleResponseHooks).toHaveLength(0);
});
