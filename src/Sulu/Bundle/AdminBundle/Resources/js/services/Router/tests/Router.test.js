// @flow
import 'url-search-params-polyfill';
import {createMemoryHistory} from 'history';
import {observable, isObservable} from 'mobx';
import Router from '../Router';
import Route from '../Route';
import routeRegistry from '../registries/routeRegistry';

window.addEventListener = jest.fn();

jest.mock('../registries/routeRegistry', () => {
    const getAllMock = jest.fn();

    return {
        getAll: getAllMock,
        get: jest.fn((key) => getAllMock()[key]),
    };
});

test('Navigate to route using state', () => {
    routeRegistry.getAll.mockReturnValue({
        test: new Route({
            name: 'test',
            path: '/test',
            type: 'form',
        }),
        page: new Route({
            name: 'page',
            options: {
                type: 'page',
            },
            path: '/pages/:uuid',
            type: 'form',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('test');
    router.navigate('page', {uuid: 'some-uuid'});
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(history.location.pathname).toBe('/pages/some-uuid');

    history.back();
    expect(history.location.pathname).toBe('/test');
});

test('Reset route using the reset method', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            path: '/pages',
            options: {
                type: 'page',
            },
            type: 'list',
        }),
    });

    const history = createMemoryHistory();
    history.replace('/pages/some-uuid');
    const router = new Router(history);

    router.reset();
    expect(history.location.pathname).toBe('/');
});

test('Reset route using the reset method with hash and search', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            path: '/pages',
            options: {
                type: 'page',
            },
            type: 'list',
        }),
    });

    const history = createMemoryHistory();
    history.replace('/pages/some-uuid?test=value');
    const router = new Router(history);

    router.reset();
    expect(history.location.pathname).toBe('/');
    expect(history.location.search).toBe('');
});

test('Redirect to route using state', () => {
    routeRegistry.getAll.mockReturnValue({
        test1: new Route({
            name: 'test1',
            type: 'test1',
            path: '/test1',
        }),
        test2: new Route({
            name: 'test2',
            type: 'test2',
            path: '/test2',
        }),
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('test1');
    router.navigate('test2');
    router.redirect('page', {uuid: 'some-uuid'});
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(history.location.pathname).toBe('/pages/some-uuid');

    history.back();
    expect(history.location.pathname).toBe('/test1');
});

test('Navigate to route with search parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid', page: 1, sort: 'title'});
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.page).toBe(1);
    expect(router.attributes.sort).toBe('title');
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?page=1&sort=title');
});

test('Navigate to route with object search parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
            attributeDefaults: {},
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate(
        'page',
        {uuid: 'some-uuid', page: 1, filter: {firstName: {eq: 'Max'}, lastName: {eq: 'Mustermann'}}}
    );
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.page).toBe(1);
    expect(router.attributes.filter).toEqual({firstName: {eq: 'Max'}, lastName: {eq: 'Mustermann'}});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?page=1&filter.firstName.eq=Max&filter.lastName.eq=Mustermann');
});

test('Navigate to route with array search parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
            attributeDefaults: {},
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate(
        'page',
        {uuid: 'some-uuid', page: 1, ids: [1, 2, 3]}
    );
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.page).toBe(1);
    expect(router.attributes.ids).toEqual([1, 2, 3]);
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?page=1&ids%5B0%5D=1&ids%5B1%5D=2&ids%5B2%5D=3');
});

test('Navigate to route with dates in array search parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
            attributeDefaults: {},
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate(
        'page',
        {uuid: 'some-uuid', page: 1, dates: [new Date('2020-03-10 00:00'), new Date('2020-03-20 12:00')]}
    );
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.page).toBe(1);
    expect(router.attributes.dates).toEqual([new Date('2020-03-10 00:00'), new Date('2020-03-20 12:00')]);
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?page=1&dates%5B0%5D=2020-03-10+00%3A00&dates%5B1%5D=2020-03-20+12%3A00');
});

test('Navigate to route with array nested in object search parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
            attributeDefaults: {},
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate(
        'page',
        {uuid: 'some-uuid', page: 1, filter: {ids: [1, 2, 3]}}
    );
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.page).toBe(1);
    expect(router.attributes.filter).toEqual({ids: [1, 2, 3]});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?page=1&filter.ids%5B0%5D=1&filter.ids%5B1%5D=2&filter.ids%5B2%5D=3');
});

test('Navigate to route with date search parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
            attributeDefaults: {},
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate(
        'page',
        {uuid: 'some-uuid', page: 1, from: new Date('2020-02-28 00:00')}
    );
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.page).toBe(1);
    expect(router.attributes.from).toEqual(new Date('2020-02-28 00:00'));
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?page=1&from=2020-02-28+00%3A00');
});

test('Navigate to route with string representing invalid date search parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
            attributeDefaults: {},
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate(
        'page',
        {uuid: 'some-uuid', page: 1, from: '2020-02-32'}
    );
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.page).toBe(1);
    expect(router.attributes.from).toEqual('2020-02-32');
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?page=1&from=2020-02-32');
});

test('Navigate to route without parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(router.route.name).toBe('page');
});

test('Navigate to route with default attribute', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list/:locale',
            attributeDefaults: {
                locale: 'en',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('list');
    expect(router.attributes.locale).toBe('en');
    expect(history.location.pathname).toBe('/list/en');
});

test('Navigate to route without default attribute when observable is changed', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list/:locale',
            attributeDefaults: {
                locale: 'en',
            },
        }),
    });

    const locale = observable.box();

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('locale', locale);

    router.handleNavigation('list', {}, router.navigate);
    expect(router.attributes.locale).toBe('en');
    expect(history.location.pathname).toBe('/list/en');

    locale.set('de');
    expect(router.attributes.locale).toBe('de');
    expect(history.location.pathname).toBe('/list/de');
});

test('Apply updateAttributesHooks before applying default attributes but after passed attributes', () => {
    routeRegistry.getAll.mockReturnValue({
        webspace_overview: new Route({
            name: 'webspace_overview',
            type: 'webspace_overview',
            path: '/webspace/:webspace/:locale',
            attributeDefaults: {
                webspace: 'webspace1',
                sortOrder: 'desc',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.addUpdateAttributesHook((route) => {
        if (route.type !== 'webspace_overview') {
            return {};
        }

        return {
            webspace: 'webspace2',
        };
    });

    router.addUpdateAttributesHook(() => {
        return {
            value: 'test',
        };
    });

    router.handleNavigation('webspace_overview', {locale: 'en'}, router.navigate);

    expect(router.attributes.webspace).toEqual('webspace2');
    expect(router.attributes.locale).toEqual('en');
    expect(router.attributes.sortOrder).toEqual('desc');
    expect(router.attributes.value).toEqual('test');
});

test('Apply attribute defaults if value of passed attribute is undefined', () => {
    routeRegistry.getAll.mockReturnValue({
        webspace_overview: new Route({
            name: 'webspace_overview',
            type: 'webspace_overview',
            path: '/webspace/:webspace/:locale',
            attributeDefaults: {
                webspace: 'webspace1',
                sortOrder: 'desc',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.handleNavigation('webspace_overview', {locale: 'en', webspace: undefined}, router.navigate);

    expect(router.attributes.webspace).toEqual('webspace1');
    expect(router.attributes.locale).toEqual('en');
});

test('Update observable attribute on route change', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list/:locale',
            attributeDefaults: {
                locale: 'en',
            },
        }),
    });

    const locale = observable.box();

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('locale', locale);

    router.navigate('list');
    expect(router.attributes.locale).toBe('en');
    expect(history.location.pathname).toBe('/list/en');

    history.push('/list/de');
    expect(router.attributes.locale).toBe('de');
    expect(history.location.pathname).toBe('/list/de');
});

test('Update date observable attribute on route change', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
            attributeDefaults: {},
        }),
    });

    const date = observable.box();

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('date', date);

    router.navigate('list', {date: new Date('2020-02-29 00:00')});
    expect(router.attributes.date).toEqual(new Date('2020-02-29 00:00'));

    history.push('/list?date=2020-02-29');
    expect(router.attributes.date).toEqual(new Date('2020-02-29 00:00'));
});

test('Update boolean observable attribute on route change', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
            attributeDefaults: {
                exclude: true,
            },
        }),
    });

    const exclude = observable.box();

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('exclude', exclude, true);

    router.navigate('list');
    expect(router.attributes.exclude).toBe(true);
    expect(exclude.get()).toBe(true);

    history.push('/list?exclude=false');
    expect(router.attributes.exclude).toBe(false);
    expect(exclude.get()).toBe(false);
});

test('Update attribute containing an observable array on route change', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
            attributeDefaults: {
                exclude: true,
            },
        }),
    });

    const filter = observable.box({
        accountId: observable([1, 2]),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('filter', filter);

    router.navigate('list', {filter: {accountId: [1, 2]}});
    expect(router.attributes.filter).toEqual({accountId: [1, 2]});
    expect(filter.get()).toEqual({accountId: [1, 2]});

    history.push('/list?filter.accountId%5B0%5D=2');
    expect(router.attributes.filter).toEqual({accountId: [2]});
    expect(filter.get()).toEqual({accountId: [2]});
});

test('Navigate to route using URL', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid/:test',
            options: {
                type: 'page',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    history.push('/pages/some-uuid/value');
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.test).toBe('value');
    expect(history.location.pathname).toBe('/pages/some-uuid/value');
});

test('Navigate to route using non-existant URL with attributes', () => {
    routeRegistry.getAll.mockReturnValue({});

    const history = createMemoryHistory();
    const router = new Router(history);

    history.push('/?token=some-uuid');
    expect(router.route).toEqual(undefined);
    expect(router.attributes.token).toBe('some-uuid');
});

test('Navigate to route using URL with search parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid/:test',
            options: {
                type: 'page',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    history.push('/pages/some-uuid/value?page=1&sort=date');
    expect(router.route.type).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.test).toBe('value');
    expect(router.attributes.page).toBe(1);
    expect(router.attributes.sort).toBe('date');
    expect(history.location.pathname).toBe('/pages/some-uuid/value');
    expect(history.location.search).toBe('?page=1&sort=date');
});

test('Navigate to route using a number with leading zeroes', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:code',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {code: '12345'});
    expect(history.location.pathname).toBe('/pages/12345');
    expect(router.attributes.code).toBe(12345);

    router.navigate('page', {code: '012345'});
    expect(history.location.pathname).toBe('/pages/012345');
    expect(router.attributes.code).toBe('012345');

    router.navigate('page', {code: '0.12345'});
    expect(history.location.pathname).toBe('/pages/0.12345');
    expect(router.attributes.code).toBe(0.12345);

    router.navigate('page', {code: '00.12345'});
    expect(history.location.pathname).toBe('/pages/00.12345');
    expect(router.attributes.code).toBe('00.12345');
});

test('Navigate to route changing only parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(history.location.pathname).toBe('/pages/some-uuid');

    router.navigate('page', {uuid: 'some-other-uuid'});
    expect(history.location.pathname).toBe('/pages/some-other-uuid');
});

test('Navigate to route by adding parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid/:value?',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(history.location.pathname).toBe('/pages/some-uuid');

    router.navigate('page', {uuid: 'some-uuid', value: 'test'});
    expect(history.location.pathname).toBe('/pages/some-uuid/test');
});

test('Navigate to route by removing parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid/:value?',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid', value: 'test'});
    expect(history.location.pathname).toBe('/pages/some-uuid/test');

    router.navigate('page', {uuid: 'some-uuid'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
});

test('Navigate to route changing only search parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid', sort: 'date'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=date');

    router.navigate('page', {uuid: 'some-uuid', sort: 'title'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=title');
});

test('Navigate to route by adding search parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid', sort: 'date'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=date');

    router.navigate('page', {uuid: 'some-uuid', sort: 'date', order: 'asc'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=date&order=asc');
});

test('Navigate to route by removing search parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid', sort: 'date', order: 'asc'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=date&order=asc');

    router.navigate('page', {uuid: 'some-uuid', sort: 'date'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=date');
});

test('Navigate to route and let history react', () => {
    routeRegistry.getAll.mockReturnValue({
        home: new Route({
            name: 'home',
            type: 'home',
            path: '/',
        }),
        page: new Route({
            name: 'page',
            type: 'page',
            path: '/page',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page');

    expect(history.location.pathname).toBe('/page');
});

test('Do not navigate if all parameters are equal', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    const expectedParameters = router.attributes;
    expect(router.attributes).toBe(expectedParameters);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(router.attributes).toBe(expectedParameters);
});

test('Use current route from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'page',
            path: '/page',
        }),
    });

    const history = createMemoryHistory();
    history.push('/page');

    const router = new Router(history);

    expect(router.route.name).toBe('page');
});

test('Binding should update passed observable', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const value = observable.box();

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('page', value);
    router.handleNavigation('list', {page: 2}, router.navigate);

    expect(value.get()).toBe(2);
});

test('Binding should update state in router', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const page = observable.box(1);

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('list', {page: 1});
    router.bind('page', page);
    expect(router.attributes.page).toBe(1);

    page.set(2);
    expect(router.attributes.page).toBe(2);
});

test('Binding should set default attribute', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'page',
            path: '/page/:locale',
        }),
    });

    const locale = observable.box();

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('locale', locale, 'en');
    router.handleNavigation('page', {}, router.navigate);
    expect(router.attributes.locale).toBe('en');
    expect(router.url).toBe('/page/en');
});

test('Binding should not touch observable value when default attribute is already set', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'page',
            path: '/page/:locale',
        }),
    });

    const locale = observable.box('en');
    let observableChanged = false;

    locale.intercept((change) => {
        observableChanged = true;
        return change;
    });

    const history = createMemoryHistory();
    const router = new Router(history);
    router.attributes.locale = undefined;

    router.bind('locale', locale, 'en');
    router.handleNavigation('page', {}, router.navigate);
    expect(router.attributes.locale).toBe('en');
    expect(observableChanged).toEqual(false);
});

test('Binding should update URL with fixed attributes', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'page',
            path: '/page/:uuid',
        }),
    });

    const uuid = observable.box(1);

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 1, locale: 'de'});
    router.bind('uuid', uuid);
    expect(router.attributes.uuid).toBe(1);
    expect(router.url).toBe('/page/1?locale=de');

    uuid.set(2);
    expect(router.attributes.uuid).toBe(2);
});

test('Binding should update URL with fixed attributes as string if not a number', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'page',
            path: '/page/:uuid',
        }),
    });

    const uuid = observable.box('old-uuid');

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid', locale: 'de'});
    router.bind('uuid', uuid);
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.url).toBe('/page/some-uuid?locale=de');

    uuid.set('another-uuid');
    expect(router.attributes.uuid).toBe('another-uuid');
});

test('Binding should update state in router with other default bindings', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const page = observable.box();
    const locale = observable.box('en');

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('page', page, '1');
    router.bind('locale', locale);
    router.handleNavigation('list', {}, router.navigate);

    locale.set('de');
    expect(history.location.search).toBe('?locale=de');
    expect(router.attributes.locale).toBe('de');
});

test('Do not add parameter to URL if undefined', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const value = observable.box();

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('page', value);
    history.push('/list');
    expect(history.location.search).toBe('');
});

test('Set state to undefined if parameter is removed from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const value = observable.box(5);

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('page', value);
    history.push('/list');
    expect(value.get()).toBe(undefined);
});

test('Bound query should update state to default value if removed from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const value = observable.box(5);

    const history = createMemoryHistory();
    const router = new Router(history);

    router.bind('page', value, '1');
    history.push('/list');
    expect(value.get()).toBe('1');
});

test('Bound query should omit URL parameter if set to default value', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const value = observable.box('5');

    const history = createMemoryHistory();
    const router = new Router(history);
    router.navigate('list');

    router.bind('page', value, '1');
    value.set('1');
    expect(history.location.search).toBe('');
});

test('Bound query should initially not be set to undefined in URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const value = observable.box();

    const history = createMemoryHistory();
    history.push('/list');
    const router = new Router(history);
    router.bind('page', value, '1');

    expect(history.location.search).toBe('');
});

test('Binding should be set to initial passed value from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const value = observable.box();

    const history = createMemoryHistory();
    history.push('/list?page=2');
    const router = new Router(history);
    router.bind('page', value, 1);

    expect(value.get()).toBe(2);
    expect(history.location.search).toBe('?page=2');
});

test('Binding should not be set to initial passed value from URL if values already match', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const value = {
        get: jest.fn().mockReturnValue(2),
        intercept: jest.fn(),
        observe: jest.fn(),
        set: jest.fn(),
    };

    const history = createMemoryHistory();
    history.push('/list?page=2');
    const router = new Router(history);
    router.bind('page', value, 1);

    expect(value.set).not.toBeCalled();
});

test('Binding should not be updated if only data type changes', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
        }),
    });

    const page = jest.fn(() => ({
        get: jest.fn(),
        set: jest.fn(),
        observe: jest.fn(),
        intercept: jest.fn(),
    }))();

    const history = createMemoryHistory();
    const router = new Router(history);
    router.bind('page', page);

    router.navigate('list', {page: 2});
    page.get.mockReturnValue(2);

    router.navigate('list', {page: '2'});
    expect(page.set.mock.calls).not.toContainEqual(['2']);
});

test('Binding should not be updated if the same object is set again', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
            attributeDefaults: {},
        }),
    });

    const filter = jest.fn(() => ({
        get: jest.fn().mockReturnValue({}),
        set: jest.fn(),
        observe: jest.fn(),
        intercept: jest.fn(),
    }))();

    const history = createMemoryHistory();
    const router = new Router(history);
    router.bind('filter', filter);

    router.navigate('list', {filter: {}});
    expect(filter.set.mock.calls).not.toContainEqual([{}]);
});

test('Binding should be updated if another object is set', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
            attributeDefaults: {},
        }),
    });

    const filter = jest.fn(() => ({
        get: jest.fn().mockReturnValue({}),
        set: jest.fn(),
        observe: jest.fn(),
        intercept: jest.fn(),
    }))();

    const history = createMemoryHistory();
    const router = new Router(history);
    router.bind('filter', filter);

    router.navigate('list', {filter: {test: {eq: 'Test'}}});
    expect(filter.set.mock.calls).toContainEqual([{test: {eq: 'Test'}}]);
});

test('Binding should not be updated if the same date is set again', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
            attributeDefaults: {},
        }),
    });

    const from = jest.fn(() => ({
        get: jest.fn().mockReturnValue(new Date('2020-02-02')),
        set: jest.fn(),
        observe: jest.fn(),
        intercept: jest.fn(),
    }))();

    const history = createMemoryHistory();
    const router = new Router(history);
    router.bind('from', from);

    router.navigate('list', {from: new Date('2020-02-02')});
    expect(from.set.mock.calls).not.toContainEqual([new Date('2020-02-02')]);
});

test('Binding should be updated if the date changes', () => {
    routeRegistry.getAll.mockReturnValue({
        list: new Route({
            name: 'list',
            type: 'list',
            path: '/list',
            attributeDefaults: {},
        }),
    });

    const from = jest.fn(() => ({
        get: jest.fn().mockReturnValue(new Date('2020-02-02')),
        set: jest.fn(),
        observe: jest.fn(),
        intercept: jest.fn(),
    }))();

    const history = createMemoryHistory();
    const router = new Router(history);
    router.bind('from', from);

    router.navigate('list', {from: new Date('2020-02-03')});
    expect(from.set.mock.calls).toContainEqual([new Date('2020-02-03')]);
});

test('Navigate to child route using state', () => {
    const formRoute = new Route({
        name: 'sulu_snippet.form',
        type: 'sulu_admin.tab',
        path: '/snippets/:uuid',
        options: {
            resourceKey: 'snippet',
        },
    });

    const detailsRoute = new Route({
        name: 'sulu_snippet.form.details',
        type: 'sulu_admin.form',
        path: '/snippets/:uuid/details',
        options: {
            tabTitle: 'Details',
        },
    });

    const taxonomyRoute = new Route({
        name: 'sulu_snippet.form.taxonomy',
        type: 'sulu_admin.form',
        path: '/snippets/:uuid/taxonomy',
        options: {
            tabTitle: 'Taxonomies',
        },
    });

    formRoute.children = [detailsRoute, taxonomyRoute];
    detailsRoute.parent = formRoute;
    taxonomyRoute.parent = formRoute;

    routeRegistry.getAll.mockReturnValue({
        'sulu_snippet.form': formRoute,
        'sulu_snippet.form.details': detailsRoute,
        'sulu_snippet.form.taxonomy': taxonomyRoute,
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('sulu_snippet.form.details', {uuid: 'some-uuid'});

    expect(router.route.type).toBe('sulu_admin.form');
    expect(router.route.options.tabTitle).toBe('Details');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(history.location.pathname).toBe('/snippets/some-uuid/details');

    const parent = router.route.parent;
    if (!parent) {
        throw new Error('Parent must be set!');
    }
    expect(parent.type).toBe('sulu_admin.tab');
    expect(parent.options.resourceKey).toBe('snippet');
    expect(parent.children).toHaveLength(2);
    expect(parent.children[0]).toBe(router.route);
    expect(parent.children[1].options.tabTitle).toBe('Taxonomies');
});

test('Navigate to child route using URL', () => {
    const formRoute = new Route({
        name: 'sulu_snippet.form',
        type: 'sulu_admin.tab',
        path: '/snippets/:uuid',
        options: {
            resourceKey: 'snippet',
        },
    });

    const detailsRoute = new Route({
        name: 'sulu_snippet.form.details',
        type: 'sulu_admin.form',
        path: '/snippets/:uuid/details',
        options: {
            tabTitle: 'Details',
        },
    });

    const taxonomyRoute = new Route({
        name: 'sulu_snippet.form.taxonomy',
        type: 'sulu_admin.form',
        path: '/snippets/:uuid/taxonomy',
        options: {
            tabTitle: 'Taxonomies',
        },
    });

    formRoute.children = [detailsRoute, taxonomyRoute];
    detailsRoute.parent = formRoute;
    taxonomyRoute.parent = formRoute;

    routeRegistry.getAll.mockReturnValue({
        'sulu_snippet.form': formRoute,
        'sulu_snippet.form.details': detailsRoute,
        'sulu_snippet.form.taxonomy': taxonomyRoute,
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    history.push('/snippets/some-uuid/details');

    expect(router.route.type).toBe('sulu_admin.form');
    expect(router.route.options.tabTitle).toBe('Details');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(history.location.pathname).toBe('/snippets/some-uuid/details');

    const parent = router.route.parent;
    if (!parent) {
        throw new Error('Parent must be set!');
    }
    expect(parent.type).toBe('sulu_admin.tab');
    expect(parent.options.resourceKey).toBe('snippet');
    expect(parent.children).toHaveLength(2);
    expect(parent.children[0]).toBe(router.route);
    expect(parent.children[1].options.tabTitle).toBe('Taxonomies');
});

test('Navigating should store the old route information', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
        }),
        snippet: new Route({
            name: 'snippet',
            type: 'form',
            path: '/snippets/:uuid',
            options: {
                type: 'page',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'page-uuid', locale: 'en'});
    router.navigate('snippet', {uuid: 'snippet-uuid', locale: 'de'});
    router.navigate('page', {uuid: 'other-page-uuid', locale: 'de'});
    router.navigate('page', {uuid: 'another-page-uuid', locale: 'de'});

    expect(router.attributesHistory['page']).toEqual([
        {
            uuid: 'page-uuid',
            locale: 'en',
        },
        {
            uuid: 'other-page-uuid',
            locale: 'de',
        },
    ]);

    expect(router.attributesHistory['snippet']).toEqual([
        {
            uuid: 'snippet-uuid',
            locale: 'de',
        },
    ]);
});

test('Navigating to route with defaults should store the old route information once', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:locale/:uuid',
            options: {
                type: 'page',
            },
            attributeDefaults: {
                locale: 'en',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'page-uuid'});
    router.navigate('page', {uuid: 'page-uuid', locale: 'de'});

    expect(router.attributesHistory['page']).toEqual([
        {
            uuid: 'page-uuid',
            locale: 'en',
        },
    ]);
});

test('Restore should navigate to the given route with the stored data', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
        }),
        snippet: new Route({
            name: 'snippet',
            type: 'form',
            path: '/snippets/:uuid',
            options: {
                type: 'page',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('snippet', {uuid: 'snippet-uuid', locale: 'de'});
    router.navigate('page', {uuid: 'another-page-uuid', locale: 'de'});

    expect(router.route.name).toEqual('page');

    router.restore('snippet');
    expect(router.route.name).toEqual('snippet');
    expect(router.attributes).toEqual({uuid: 'snippet-uuid', locale: 'de'});
});

test('Restore should navigate to the given route with passed data being merged', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
        }),
        snippet: new Route({
            name: 'snippet',
            type: 'form',
            path: '/snippets/:uuid/:test',
            options: {
                type: 'page',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('snippet', {uuid: 'uuid', test: 'other-test', locale: 'en', parameter: 'other-value'});
    router.navigate('snippet', {uuid: 'snippet-uuid', test: 'test', locale: 'de', parameter: 'value'});
    router.navigate('page', {uuid: 'other-page-uuid', locale: 'de'});

    expect(router.route.name).toEqual('page');

    router.restore('snippet', {test: 'new-test', locale: 'en'});
    expect(router.route.name).toEqual('snippet');
    expect(router.attributes).toEqual({uuid: 'snippet-uuid', test: 'new-test', locale: 'en', parameter: 'value'});
});

test('Restore should just navigate if no history is available', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.restore('page', {uuid: 'page-uuid', locale: 'de'});

    expect(router.route.name).toEqual('page');
    expect(router.attributes).toEqual({uuid: 'page-uuid', locale: 'de'});
});

test('Restore should not create a new history entry', () => {
    routeRegistry.getAll.mockReturnValue({
        page: new Route({
            name: 'page',
            type: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
        }),
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'page-uuid', locale: 'de'});
    router.navigate('page', {uuid: 'other-page-uuid', locale: 'de'});
    router.navigate('page', {uuid: 'another-page-uuid', locale: 'de'});
    expect(router.attributesHistory['page']).toHaveLength(2);

    router.restore('page');
    expect(router.attributesHistory['page']).toHaveLength(1);
});

test('Add and remove updateRouteHooks', () => {
    const history = createMemoryHistory();
    const router = new Router(history);

    const updateRouteHook1 = jest.fn();
    const updateRouteHook2 = jest.fn();

    const updateRouteHookDisposer1 = router.addUpdateRouteHook(updateRouteHook1);
    router.addUpdateRouteHook(updateRouteHook2);

    expect(router.updateRouteHooks[0]).toHaveLength(2);
    expect(router.updateRouteHooks[0][0]).toBe(updateRouteHook1);
    expect(router.updateRouteHooks[0][1]).toBe(updateRouteHook2);

    updateRouteHookDisposer1();
    expect(router.updateRouteHooks[0]).toHaveLength(1);
    expect(router.updateRouteHooks[0][0]).toBe(updateRouteHook2);
});

test('Add and remove updateRouteHooks with different priorities', () => {
    const history = createMemoryHistory();
    const router = new Router(history);

    const updateRouteHook1 = jest.fn();
    const updateRouteHook2 = jest.fn();

    const updateRouteHookDisposer1 = router.addUpdateRouteHook(updateRouteHook1, 1024);
    router.addUpdateRouteHook(updateRouteHook2, 512);

    expect(router.updateRouteHooks[1024]).toHaveLength(1);
    expect(router.updateRouteHooks[1024][0]).toBe(updateRouteHook1);
    expect(router.updateRouteHooks[512]).toHaveLength(1);
    expect(router.updateRouteHooks[512][0]).toBe(updateRouteHook2);

    updateRouteHookDisposer1();
    expect(router.updateRouteHooks[1024]).toHaveLength(0);
    expect(router.updateRouteHooks[512]).toHaveLength(1);
    expect(router.updateRouteHooks[512][0]).toBe(updateRouteHook2);
});

test('Cancel navigation if an updateRouteHook returns false', () => {
    const webspaceOverviewRoute = new Route({
        name: 'webspace_overview',
        type: 'webspace_overview',
        path: '/webspace/:webspace/:locale',
        attributeDefaults: {
            sortOrder: 'desc',
            webspace: 'webspace1',
        },
    });

    routeRegistry.getAll.mockReturnValue({
        webspace_overview: webspaceOverviewRoute,
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    const updateRouteHook1 = jest.fn().mockReturnValue(false);
    const updateRouteHook2 = jest.fn().mockReturnValue(true);

    router.addUpdateRouteHook(updateRouteHook1);
    router.addUpdateRouteHook(updateRouteHook2);

    router.navigate('webspace_overview', {locale: 'en'});

    expect(updateRouteHook1).toBeCalledWith(
        webspaceOverviewRoute,
        {locale: 'en', sortOrder: 'desc', webspace: 'webspace1'},
        router.navigate
    );
    expect(updateRouteHook2).not.toBeCalled();

    expect(router.route).toBe(undefined);
    expect(router.attributes).toEqual({});
    expect(history.location.pathname).toBe('/');
});

test('Consider priority when cancelling a navigation', () => {
    const webspaceOverviewRoute = new Route({
        name: 'webspace_overview',
        type: 'webspace_overview',
        path: '/webspace/:webspace/:locale',
        attributeDefaults: {
            sortOrder: 'desc',
            webspace: 'webspace1',
        },
    });

    routeRegistry.getAll.mockReturnValue({
        webspace_overview: webspaceOverviewRoute,
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    const updateRouteHook1 = jest.fn().mockReturnValue(true);
    const updateRouteHook2 = jest.fn().mockReturnValue(false);

    router.addUpdateRouteHook(updateRouteHook1, 512);
    router.addUpdateRouteHook(updateRouteHook2, 1024);

    router.navigate('webspace_overview', {locale: 'en'});

    expect(updateRouteHook2).toBeCalledWith(
        webspaceOverviewRoute,
        {locale: 'en', sortOrder: 'desc', webspace: 'webspace1'},
        router.navigate
    );
    expect(updateRouteHook1).not.toBeCalled();

    expect(router.route).toBe(undefined);
    expect(router.attributes).toEqual({});
    expect(history.location.pathname).toBe('/');
});

test('Navigate if all updateRouteHooks return true', () => {
    const webspaceOverviewRoute = new Route({
        name: 'webspace_overview',
        type: 'webspace_overview',
        path: '/webspace/:webspace/:locale',
        attributeDefaults: {
            sortOrder: 'desc',
            webspace: 'webspace1',
        },
    });

    routeRegistry.getAll.mockReturnValue({
        webspace_overview: webspaceOverviewRoute,
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    const updateRouteHook1 = jest.fn().mockReturnValue(true);
    const updateRouteHook2 = jest.fn().mockReturnValue(true);

    router.addUpdateRouteHook(updateRouteHook1);
    router.addUpdateRouteHook(updateRouteHook2);

    router.navigate('webspace_overview', {locale: 'en'});

    expect(updateRouteHook1).toBeCalledWith(
        webspaceOverviewRoute,
        {locale: 'en', sortOrder: 'desc', webspace: 'webspace1'},
        router.navigate
    );
    expect(updateRouteHook2).toBeCalledWith(
        webspaceOverviewRoute,
        {locale: 'en', sortOrder: 'desc', webspace: 'webspace1'},
        router.navigate
    );

    expect(router.route).toEqual(webspaceOverviewRoute);
    expect(router.attributes).toEqual({locale: 'en', sortOrder: 'desc', webspace: 'webspace1'});
    expect(history.location.pathname).toBe('/webspace/webspace1/en');
});

test('Redirect if all updateRouteHooks return true', () => {
    const webspaceOverviewRoute = new Route({
        name: 'webspace_overview',
        type: 'webspace_overview',
        path: '/webspace/:webspace/:locale',
        attributeDefaults: {
            sortOrder: 'desc',
            webspace: 'webspace1',
        },
    });

    routeRegistry.getAll.mockReturnValue({
        webspace_overview: webspaceOverviewRoute,
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    const updateRouteHook1 = jest.fn().mockReturnValue(true);
    const updateRouteHook2 = jest.fn().mockReturnValue(true);

    router.addUpdateRouteHook(updateRouteHook1);
    router.addUpdateRouteHook(updateRouteHook2);

    router.redirect('webspace_overview', {locale: 'en'});

    expect(updateRouteHook1).toBeCalledWith(
        webspaceOverviewRoute,
        {locale: 'en', sortOrder: 'desc', webspace: 'webspace1'},
        router.redirect
    );
    expect(updateRouteHook2).toBeCalledWith(
        webspaceOverviewRoute,
        {locale: 'en', sortOrder: 'desc', webspace: 'webspace1'},
        router.redirect
    );

    expect(router.route).toEqual(webspaceOverviewRoute);
    expect(router.attributes).toEqual({locale: 'en', sortOrder: 'desc', webspace: 'webspace1'});
    expect(history.location.pathname).toBe('/webspace/webspace1/en');
});

test('Restore if all updateRouteHooks return true', () => {
    const webspaceOverviewRoute = new Route({
        name: 'webspace_overview',
        type: 'webspace_overview',
        path: '/webspace/:webspace/:locale',
        attributeDefaults: {
            sortOrder: 'desc',
            webspace: 'webspace1',
        },
    });

    routeRegistry.getAll.mockReturnValue({
        webspace_overview: webspaceOverviewRoute,
    });

    const history = createMemoryHistory();
    const router = new Router(history);

    const updateRouteHook1 = jest.fn().mockReturnValue(true);
    const updateRouteHook2 = jest.fn().mockReturnValue(true);

    router.addUpdateRouteHook(updateRouteHook1);
    router.addUpdateRouteHook(updateRouteHook2);

    router.restore('webspace_overview', {locale: 'en'});

    expect(updateRouteHook1).toBeCalledWith(
        webspaceOverviewRoute,
        {locale: 'en', sortOrder: 'desc', webspace: 'webspace1'},
        router.restore
    );
    expect(updateRouteHook2).toBeCalledWith(
        webspaceOverviewRoute,
        {locale: 'en', sortOrder: 'desc', webspace: 'webspace1'},
        router.restore
    );

    expect(router.route).toEqual(webspaceOverviewRoute);
    expect(router.attributes).toEqual({locale: 'en', sortOrder: 'desc', webspace: 'webspace1'});
    expect(history.location.pathname).toBe('/webspace/webspace1/en');
});

test('Ask for confirmation to close window if a updateRouteHooks prevents it', () => {
    const history = createMemoryHistory();
    const router = new Router(history);

    expect(window.addEventListener).toBeCalledWith('beforeunload', expect.anything());

    const updateRouteHook1 = jest.fn().mockReturnValue(true);
    const updateRouteHook2 = jest.fn().mockReturnValue(false);

    router.addUpdateRouteHook(updateRouteHook1);
    router.addUpdateRouteHook(updateRouteHook2);

    const event = {
        preventDefault: jest.fn(),
        returnValue: undefined,
    };

    window.addEventListener.mock.calls[0][1](event);

    expect(event.preventDefault).toBeCalledWith();
    expect(event.returnValue).toEqual(true);
});

test('Do not ask for confirmation to close window if no updateRouteHooks prevents it', () => {
    const history = createMemoryHistory();
    const router = new Router(history);

    expect(window.addEventListener).toBeCalledWith('beforeunload', expect.anything());

    const updateRouteHook1 = jest.fn().mockReturnValue(true);
    const updateRouteHook2 = jest.fn().mockReturnValue(true);

    router.addUpdateRouteHook(updateRouteHook1);
    router.addUpdateRouteHook(updateRouteHook2);

    const event = {
        preventDefault: jest.fn(),
        returnValue: undefined,
    };

    window.addEventListener.mock.calls[0][1](event);

    expect(event.preventDefault).not.toBeCalled();
    expect(event.returnValue).toEqual(undefined);
});
