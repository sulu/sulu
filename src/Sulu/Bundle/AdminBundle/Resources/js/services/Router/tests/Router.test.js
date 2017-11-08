// @flow
import 'url-search-params-polyfill';
import createHistory from 'history/createMemoryHistory';
import {extendObservable, observable, isObservable} from 'mobx';
import Router from '../Router';
import routeRegistry from '../registries/RouteRegistry';

jest.mock('../registries/RouteRegistry', () => {
    const getAllMock = jest.fn();

    return {
        getAll: getAllMock,
        get: jest.fn((key) => getAllMock()[key]),
    };
});

test('Navigate to route using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.view).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(history.location.pathname).toBe('/pages/some-uuid');
});

test('Navigate to route with search parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
            options: {
                type: 'page',
            },
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid', page: '1', sort: 'title'});
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.view).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.page).toBe('1');
    expect(router.attributes.sort).toBe('title');
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?page=1&sort=title');
});

test('Navigate to route without parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(router.route.name).toBe('page');
});

test('Navigate to route with default attribute', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list/:locale',
            attributeDefaults: {
                locale: 'en',
            },
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('list');
    expect(router.attributes.locale).toBe('en');
    expect(history.location.pathname).toBe('/list/en');
});

test('Navigate to route without default attribute when observable is changed', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list/:locale',
            attributeDefaults: {
                locale: 'en',
            },
        },
    });

    const locale = observable();

    const history = createHistory();
    const router = new Router(history);

    router.bind('locale', locale);

    router.navigate('list');
    expect(router.attributes.locale).toBe('en');
    expect(history.location.pathname).toBe('/list/en');

    locale.set('de');
    expect(router.attributes.locale).toBe('de');
    expect(history.location.pathname).toBe('/list/de');
});

test('Update observable attribute on route change', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list/:locale',
            attributeDefaults: {
                locale: 'en',
            },
        },
    });

    const locale = observable();

    const history = createHistory();
    const router = new Router(history);

    router.bind('locale', locale);

    router.navigate('list');
    expect(router.attributes.locale).toBe('en');
    expect(history.location.pathname).toBe('/list/en');

    history.push('/list/de');
    expect(router.attributes.locale).toBe('de');
    expect(history.location.pathname).toBe('/list/de');
});

test('Navigate to route without default attribute when default observable is set', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list/:locale',
            attributeDefaults: {
                locale: 'en',
            },
        },
    });

    const locale = observable();

    const history = createHistory();
    const router = new Router(history);

    router.bind('locale', locale, 'de');

    router.navigate('list');
    expect(router.attributes.locale).toBe('de');
    expect(history.location.pathname).toBe('/list/de');
});

test('Navigate to route using URL', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid/:test',
            options: {
                type: 'page',
            },
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    const router = new Router(history);

    history.push('/pages/some-uuid/value');
    expect(router.route.view).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.test).toBe('value');
    expect(history.location.pathname).toBe('/pages/some-uuid/value');
});

test('Navigate to route using URL with search parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid/:test',
            options: {
                type: 'page',
            },
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    const router = new Router(history);

    history.push('/pages/some-uuid/value?page=1&sort=date');
    expect(router.route.view).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.test).toBe('value');
    expect(router.attributes.page).toBe('1');
    expect(router.attributes.sort).toBe('date');
    expect(history.location.pathname).toBe('/pages/some-uuid/value');
    expect(history.location.search).toBe('?page=1&sort=date');
});

test('Navigate to route changing only parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(history.location.pathname).toBe('/pages/some-uuid');

    router.navigate('page', {uuid: 'some-other-uuid'});
    expect(history.location.pathname).toBe('/pages/some-other-uuid');
});

test('Navigate to route by adding parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid/:value?',
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(history.location.pathname).toBe('/pages/some-uuid');

    router.navigate('page', {uuid: 'some-uuid', value: 'test'});
    expect(history.location.pathname).toBe('/pages/some-uuid/test');
});

test('Navigate to route by removing parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid/:value?',
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid', value: 'test'});
    expect(history.location.pathname).toBe('/pages/some-uuid/test');

    router.navigate('page', {uuid: 'some-uuid'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
});

test('Navigate to route changing only search parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
            attributeDefaults: {},
        },
    });

    const history = createHistory();
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
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
            attributeDefaults: {},
        },
    });

    const history = createHistory();
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
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
            attributeDefaults: {},
        },
    });

    const history = createHistory();
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
        home: {
            name: 'home',
            view: 'home',
            path: '/',
            attributeDefaults: {},
        },
        page: {
            name: 'page',
            view: 'page',
            path: '/page',
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page');

    expect(history.location.pathname).toBe('/page');
});

test('Do not navigate if all parameters are equal', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    const expectedParameters = router.attributes;
    expect(router.attributes).toBe(expectedParameters);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(router.attributes).toBe(expectedParameters);
});

test('Use current route from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'page',
            path: '/page',
            attributeDefaults: {},
        },
    });

    const history = createHistory();
    history.push('/page');

    const router = new Router(history);

    expect(router.route.name).toBe('page');
});

test('Binding should update passed observable', () => {
    const value = observable();

    const history = createHistory();
    const router = new Router(history);

    router.bind('page', value);
    router.navigate('list', {page: 2});

    expect(value.get()).toBe(2);
});

test('Binding should update state in router', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
            attributeDefaults: {},
        },
    });

    const page = observable(1);

    const history = createHistory();
    const router = new Router(history);

    router.navigate('list', {page: 1});
    router.bind('page', page);
    expect(router.attributes.page).toBe('1');

    page.set(2);
    expect(router.attributes.page).toBe('2');
});

test('Binding should set default attribute', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'page',
            path: '/page/:locale',
            attributeDefaults: {},
        },
    });

    const locale = observable();

    const history = createHistory();
    const router = new Router(history);

    router.bind('locale', locale, 'en');
    router.navigate('page');
    expect(router.attributes.locale).toBe('en');
    expect(router.url).toBe('/page/en');
});

test('Binding should update URL with fixed attributes', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'page',
            path: '/page/:uuid',
            attributeDefaults: {},
        },
    });

    const uuid = observable(1);

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 1, locale: 'de'});
    router.bind('uuid', uuid);
    expect(router.attributes.uuid).toBe('1');
    expect(router.url).toBe('/page/1?locale=de');

    uuid.set(2);
    expect(router.attributes.uuid).toBe('2');
});

test('Binding should update state in router with other default bindings', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
            attributeDefaults: {},
        },
    });

    const page = observable();
    const locale = observable('en');

    const history = createHistory();
    const router = new Router(history);

    router.bind('page', page, '1');
    router.bind('locale', locale);
    router.navigate('list');

    locale.set('de');
    expect(history.location.search).toBe('?locale=de');
    expect(router.attributes.locale).toBe('de');
});

test('Unbind should remove binding', () => {
    const value = observable();

    const history = createHistory();
    const router = new Router(history);

    router.bind('remove', value);
    expect(router.bindings.has('remove')).toBe(true);

    router.unbind('remove', value);
    expect(router.bindings.has('remove')).toBe(false);
});

test('Unbind query should not remove binding if it is assigned to a new observable', () => {
    const history = createHistory();
    const router = new Router(history);

    const value = observable();
    router.bind('remove', value);
    expect(router.bindings.get('remove')).toBe(value);

    const newValue = observable();
    router.bind('remove', newValue);
    router.unbind('remove', value);
    expect(router.bindings.get('remove')).toBe(newValue);
});

test('Do not add parameter to URL if undefined', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
            attributeDefaults: {},
        },
    });

    const value = observable();

    const history = createHistory();
    const router = new Router(history);

    router.bind('page', value);
    history.push('/list');
    expect(history.location.search).toBe('');
});

test('Set state to undefined if parameter is removed from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
            attributeDefaults: {},
        },
    });

    const value = observable(5);

    const history = createHistory();
    const router = new Router(history);

    router.bind('page', value);
    history.push('/list');
    expect(value.get()).toBe(undefined);
});

test('Bound query should update state to default value if removed from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
            attributeDefaults: {},
        },
    });

    const value = observable(5);

    const history = createHistory();
    const router = new Router(history);

    router.bind('page', value, '1');
    history.push('/list');
    expect(value.get()).toBe('1');
});

test('Bound query should omit URL parameter if set to default value', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
            attributeDefaults: {},
        },
    });

    const value = observable(5);

    const history = createHistory();
    const router = new Router(history);
    router.navigate('list');

    router.bind('page', value, '1');
    value.set('1');
    expect(history.location.search).toBe('');
});

test('Bound query should initially not be set to undefined in URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
            attributeDefaults: {},
        },
    });

    const value = observable();

    const history = createHistory();
    history.push('/list');
    const router = new Router(history);
    router.bind('page', value, '1');

    expect(history.location.search).toBe('');
});

test('Bound query should be set to initial passed value from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
            attributeDefaults: {},
        },
    });

    const value = observable();

    const history = createHistory();
    history.push('/list?page=2');
    const router = new Router(history);
    router.bind('page', value, '1');

    expect(value.get()).toBe('2');
    expect(history.location.search).toBe('?page=2');
});

test('Navigate to child route using state', () => {
    const formRoute = extendObservable({
        name: 'sulu_snippet.form',
        view: 'sulu_admin.tab',
        path: '/snippets/:uuid',
        options: {
            resourceKey: 'snippet',
        },
        attributeDefaults: {},
    });

    const detailRoute = extendObservable({
        name: 'sulu_snippet.form.detail',
        parent: formRoute,
        view: 'sulu_admin.form',
        path: '/detail',
        options: {
            tabTitle: 'Detail',
        },
        attributeDefaults: {},
    });

    const taxonomyRoute = extendObservable({
        name: 'sulu_snippet.form.taxonomy',
        parent: formRoute,
        view: 'sulu_admin.form',
        path: '/taxonomy',
        options: {
            tabTitle: 'Taxonomies',
        },
        attributeDefaults: {},
    });

    formRoute.children = [detailRoute, taxonomyRoute];

    routeRegistry.getAll.mockReturnValue({
        'sulu_snippet.form': formRoute,
        'sulu_snippet.form.detail': detailRoute,
        'sulu_snippet.form.taxonomy': taxonomyRoute,
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('sulu_snippet.form.detail', {uuid: 'some-uuid'});

    expect(router.route.view).toBe('sulu_admin.form');
    expect(router.route.options.tabTitle).toBe('Detail');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(history.location.pathname).toBe('/snippets/some-uuid/detail');

    const parent = router.route.parent;
    if (!parent) {
        throw new Error('Parent must be set!');
    }
    expect(parent.view).toBe('sulu_admin.tab');
    expect(parent.options.resourceKey).toBe('snippet');
    expect(parent.children).toHaveLength(2);
    expect(parent.children[0]).toBe(router.route);
    expect(parent.children[1].options.tabTitle).toBe('Taxonomies');
});

test('Navigate to child route using URL', () => {
    const formRoute = extendObservable({
        name: 'sulu_snippet.form',
        view: 'sulu_admin.tab',
        path: '/snippets/:uuid',
        options: {
            resourceKey: 'snippet',
        },
        attributeDefaults: {},
    });

    const detailRoute = extendObservable({
        name: 'sulu_snippet.form.detail',
        parent: formRoute,
        view: 'sulu_admin.form',
        path: '/detail',
        options: {
            tabTitle: 'Detail',
        },
        attributeDefaults: {},
    });

    const taxonomyRoute = extendObservable({
        name: 'sulu_snippet.form.taxonomy',
        parent: formRoute,
        view: 'sulu_admin.form',
        path: '/taxonomy',
        options: {
            tabTitle: 'Taxonomies',
        },
        attributeDefaults: {},
    });

    formRoute.children = [detailRoute, taxonomyRoute];

    routeRegistry.getAll.mockReturnValue({
        'sulu_snippet.form': formRoute,
        'sulu_snippet.form.detail': detailRoute,
        'sulu_snippet.form.taxonomy': taxonomyRoute,
    });

    const history = createHistory();
    const router = new Router(history);

    history.push('/snippets/some-uuid/detail');

    expect(router.route.view).toBe('sulu_admin.form');
    expect(router.route.options.tabTitle).toBe('Detail');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(history.location.pathname).toBe('/snippets/some-uuid/detail');

    const parent = router.route.parent;
    if (!parent) {
        throw new Error('Parent must be set!');
    }
    expect(parent.view).toBe('sulu_admin.tab');
    expect(parent.options.resourceKey).toBe('snippet');
    expect(parent.children).toHaveLength(2);
    expect(parent.children[0]).toBe(router.route);
    expect(parent.children[1].options.tabTitle).toBe('Taxonomies');
});
