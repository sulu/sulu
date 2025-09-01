// @flow
import {extendObservable as mockExtendObservable} from 'mobx';
import SymfonyRouting from 'fos-jsrouting/router';
import BadgeStore from '../../stores/BadgeStore';
import Router from '../../../../services/Router';
import Requester from '../../../../services/Requester';

jest.mock('debounce', () => jest.fn((callback) => callback));

jest.mock('../../../../services/Requester', () => ({
    get: jest.fn(),
}));

Requester.handleResponseHooks = [];

// Need to use symbol here, because jest transforms {parent: {}} to {parent: [Getter/Setter]}
const mockTabViewRoute = (Symbol(): any);

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.attributes = {
        id: 5,
        locale: 'en',
    };

    mockExtendObservable(this, {
        route: {
            parent: mockTabViewRoute,
        },
    });
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
            resourceKey: 'Foo',
        },
        {
            id: 'resourceId',
            locale: 'locale',
        },
        mockTabViewRoute
    );

    expect(Requester.get).toBeCalledWith('foo?resourceId=5&locale=en&limit=0&resourceKey=Foo');

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
            resourceKey: 'Foo',
        },
        {
            id: 'resourceId',
            locale: 'locale',
        },
        mockTabViewRoute
    );

    expect(Requester.get).toBeCalledWith('foo?resourceId=5&locale=en&limit=0&resourceKey=Foo');

    return promise.then(() => {
        expect(badgeStore.value).toEqual('hello');
    });
});

test('Should load data if route changes', () => {
    SymfonyRouting.generate.mockImplementation((routeName, params) => {
        return routeName + '?' + Object.keys(params).map((key) => key + '=' + params[key]).join('&');
    });

    const promise = Promise.resolve('hello');
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    const router = new Router({});
    new BadgeStore(
        router,
        'foo',
        null,
        {
            limit: 0,
            resourceKey: 'Foo',
        },
        {
            id: 'resourceId',
            locale: 'locale',
        },
        mockTabViewRoute
    );

    expect(Requester.get).toHaveBeenNthCalledWith(1, 'foo?resourceId=5&locale=en&limit=0&resourceKey=Foo');

    router.route = ({property: 'value', parent: mockTabViewRoute}: any);

    expect(Requester.get).toHaveBeenNthCalledWith(2, 'foo?resourceId=5&locale=en&limit=0&resourceKey=Foo');
});

test('Should not load data if route changes to other parent', () => {
    SymfonyRouting.generate.mockImplementation((routeName, params) => {
        return routeName + '?' + Object.keys(params).map((key) => key + '=' + params[key]).join('&');
    });

    const promise = Promise.resolve('hello');
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    const router = new Router({});
    new BadgeStore(
        router,
        'foo',
        null,
        {
            limit: 0,
            resourceKey: 'Foo',
        },
        {
            id: 'resourceId',
            locale: 'locale',
        },
        mockTabViewRoute
    );

    expect(Requester.get).toHaveBeenCalledTimes(1);
    expect(Requester.get).toHaveBeenNthCalledWith(1, 'foo?resourceId=5&locale=en&limit=0&resourceKey=Foo');

    router.route = ({property: 'value', parent: {other: true}}: any);

    expect(Requester.get).toHaveBeenCalledTimes(1);
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
            resourceKey: 'Foo',
        },
        {
            id: 'resourceId',
            locale: 'locale',
        },
        mockTabViewRoute
    );

    expect(Requester.handleResponseHooks).toHaveLength(1);

    // Initial request
    expect(Requester.get).toHaveBeenCalledTimes(1);
    expect(Requester.get).toHaveBeenNthCalledWith(1, 'foo?resourceId=5&locale=en&limit=0&resourceKey=Foo');

    // Should not perform request because of a "GET" request
    const response1: any = {url: 'http://sulu.lo/admin/api/anything'};
    Requester.handleResponseHooks[0](
        response1,
        {method: 'GET'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(1);

    // Should perform request
    const response2: any = {url: 'http://sulu.lo/admin/api/anything'};
    Requester.handleResponseHooks[0](
        response2,
        {method: 'POST'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(2);
    expect(Requester.get).toHaveBeenNthCalledWith(2, 'foo?resourceId=5&locale=en&limit=0&resourceKey=Foo');

    // Should perform request
    const response3: any = {url: 'http://sulu.lo/admin/api/anything'};
    Requester.handleResponseHooks[0](
        response3,
        {method: 'PUT'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(3);
    expect(Requester.get).toHaveBeenNthCalledWith(3, 'foo?resourceId=5&locale=en&limit=0&resourceKey=Foo');

    // Should perform request
    const response4: any = {url: 'http://sulu.lo/admin/api/anything'};
    Requester.handleResponseHooks[0](
        response4,
        {method: 'PATCH'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(4);
    expect(Requester.get).toHaveBeenNthCalledWith(4, 'foo?resourceId=5&locale=en&limit=0&resourceKey=Foo');

    // Should perform request
    const response5: any = {url: 'http://sulu.lo/admin/api/anything'};
    Requester.handleResponseHooks[0](
        response5,
        {method: 'DELETE'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(5);
    expect(Requester.get).toHaveBeenNthCalledWith(5, 'foo?resourceId=5&locale=en&limit=0&resourceKey=Foo');

    // Should not perform request because of a collaboration request
    const response6: any = {url: 'http://sulu.lo/admin/api/collaborations?id=1234&resourceKey=pages'};
    Requester.handleResponseHooks[0](
        response6,
        {method: 'PUT'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(5);

    // Should not perform request because of a preview request
    const response7: any = {url: 'https://fullsulu.lo/admin/preview/update?id=1234&locale=en&provider=pages'};
    Requester.handleResponseHooks[0](
        response7,
        {method: 'POST'}
    );
    expect(Requester.get).toHaveBeenCalledTimes(5);

    badgeStore.destroy();
    expect(Requester.handleResponseHooks).toHaveLength(0);
});
