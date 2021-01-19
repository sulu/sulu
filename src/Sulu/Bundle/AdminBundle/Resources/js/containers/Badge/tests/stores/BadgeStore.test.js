// @flow
import SymfonyRouting from 'fos-jsrouting/router';
import BadgeStore from '../../stores/BadgeStore';
import Router from '../../../../services/Router';
import Requester from '../../../../services/Requester';

jest.mock('../../../../services/Requester', () => ({
    get: jest.fn(),
}));

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

    const router = new Router({});
    const badgeStore = new BadgeStore(
        router,
        'foo',
        '/value',
        'text != 0',
        {
            limit: 0,
            entityClass: 'Foo',
        },
        {
            id: 'entityId',
            locale: 'locale',
        }
    );

    expect(Requester.get).toBeCalledWith('foo?locale=en&entityId=5&limit=0&entityClass=Foo');

    return promise.then(() => {
        expect(badgeStore.text).toEqual('2');
    });
});

test('Should load data without datapath', () => {
    SymfonyRouting.generate.mockImplementation((routeName, params) => {
        return routeName + '?' + Object.keys(params).map((key) => key + '=' + params[key]).join('&');
    });

    const promise = Promise.resolve('hello');
    Requester.get.mockReturnValue(promise);

    const router = new Router({});
    const badgeStore = new BadgeStore(
        router,
        'foo',
        null,
        'text != 0',
        {
            limit: 0,
            entityClass: 'Foo',
        },
        {
            id: 'entityId',
            locale: 'locale',
        }
    );

    expect(Requester.get).toBeCalledWith('foo?locale=en&entityId=5&limit=0&entityClass=Foo');

    return promise.then(() => {
        expect(badgeStore.text).toEqual('hello');
    });
});

test('Should load data which does not pass the visibleCondition', () => {
    SymfonyRouting.generate.mockImplementation((routeName, params) => {
        return routeName + '?' + Object.keys(params).map((key) => key + '=' + params[key]).join('&');
    });

    const promise = Promise.resolve({value: 0});
    Requester.get.mockReturnValue(promise);

    const router = new Router({});
    const badgeStore = new BadgeStore(
        router,
        'foo',
        '/value',
        'text != 0',
        {
            limit: 0,
            entityClass: 'Foo',
        },
        {
            id: 'entityId',
            locale: 'locale',
        }
    );

    expect(Requester.get).toBeCalledWith('foo?locale=en&entityId=5&limit=0&entityClass=Foo');

    return promise.then(() => {
        expect(badgeStore.text).toEqual(null);
    });
});
