/* eslint-disable flowtype/require-valid-file-annotation */
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
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'}, {page: '1', sort: 'title'});
    expect(isObservable(router.route)).toBe(true);
    expect(router.route.view).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.query.page).toBe('1');
    expect(router.query.sort).toBe('title');
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?page=1&sort=title');
});

test('Navigate to route without parameters using state', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(router.route.name).toBe('page');
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
        },
    });

    const history = createHistory();
    const router = new Router(history);

    history.push('/pages/some-uuid/value?page=1&sort=date');
    expect(router.route.view).toBe('form');
    expect(router.route.options.type).toBe('page');
    expect(router.attributes.uuid).toBe('some-uuid');
    expect(router.attributes.test).toBe('value');
    expect(router.query.page).toBe('1');
    expect(router.query.sort).toBe('date');
    expect(history.location.pathname).toBe('/pages/some-uuid/value');
    expect(history.location.search).toBe('?page=1&sort=date');
});

test('Navigate to route changing only parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
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
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'}, {sort: 'date'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=date');

    router.navigate('page', {uuid: 'some-uuid'}, {sort: 'title'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=title');
});

test('Navigate to route by adding search parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'}, {sort: 'date'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=date');

    router.navigate('page', {uuid: 'some-uuid'}, {sort: 'date', order: 'asc'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=date&order=asc');
});

test('Navigate to route by removing search parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'}, {sort: 'date', order: 'asc'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=date&order=asc');

    router.navigate('page', {uuid: 'some-uuid'}, {sort: 'date'});
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?sort=date');
});

test('Navigate to route and let history react', () => {
    routeRegistry.getAll.mockReturnValue({
        home: {
            name: 'home',
            view: 'home',
            path: '/',
        },
        page: {
            name: 'page',
            view: 'page',
            path: '/page',
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
        },
    });

    const history = createHistory();
    history.push('/page');

    const router = new Router(history);

    expect(router.route.name).toBe('page');
});

test('Bound query parameter should update passed observable', () => {
    const value = observable();

    const history = createHistory();
    const router = new Router(history);

    router.bindQuery('page', value);
    router.navigate('list', {}, {page: 2});

    expect(value.get()).toBe(2);
});

test('Bound query parameter should update state in router', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
        },
    });

    const page = observable(1);

    const history = createHistory();
    const router = new Router(history);

    router.navigate('list', {}, {page: 1});
    router.bindQuery('page', page);
    expect(router.query.page).toBe('1');

    page.set(2);
    expect(router.query.page).toBe('2');
});

test('Bound query parameter should update state in router with other default query parameters', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
        },
    });

    const page = observable();
    const locale = observable('en');

    const history = createHistory();
    const router = new Router(history);

    router.bindQuery('page', page, '1');
    router.bindQuery('locale', locale);
    router.navigate('list');

    locale.set('de');
    expect(history.location.search).toBe('?locale=de');
    expect(router.query.locale).toBe('de');
});

test('Unbind query should remove query binding', () => {
    const value = observable();

    const history = createHistory();
    const router = new Router(history);

    router.bindQuery('remove', value);
    expect(router.queryBinds.has('remove')).toBe(true);

    router.unbindQuery('remove', value);
    expect(router.queryBinds.has('remove')).toBe(false);
});

test('Unbind query should not remove query binding if it is assigned to a new observable', () => {
    const history = createHistory();
    const router = new Router(history);

    const value = observable();
    router.bindQuery('remove', value);
    expect(router.queryBinds.get('remove')).toBe(value);

    const newValue = observable();
    router.bindQuery('remove', newValue);
    router.unbindQuery('remove', value);
    expect(router.queryBinds.get('remove')).toBe(newValue);
});

test('Do not add parameter to URL if undefined', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
        },
    });

    const value = observable();

    const history = createHistory();
    const router = new Router(history);

    router.bindQuery('page', value);
    history.push('/list');
    expect(history.location.search).toBe('');
});

test('Set state to undefined if parameter is removed from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
        },
    });

    const value = observable(5);

    const history = createHistory();
    const router = new Router(history);

    router.bindQuery('page', value);
    history.push('/list');
    expect(value.get()).toBe(undefined);
});

test('Bound query should update state to default value if removed from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
        },
    });

    const value = observable(5);

    const history = createHistory();
    const router = new Router(history);

    router.bindQuery('page', value, '1');
    history.push('/list');
    expect(value.get()).toBe('1');
});

test('Bound query should omit URL parameter if set to default value', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
        },
    });

    const value = observable(5);

    const history = createHistory();
    const router = new Router(history);
    router.navigate('list');

    router.bindQuery('page', value, '1');
    value.set('1');
    expect(history.location.search).toBe('');
});

test('Bound query should initially not be set to undefined in URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
        },
    });

    const value = observable();

    const history = createHistory();
    history.push('/list');
    const router = new Router(history);
    router.bindQuery('page', value, '1');

    expect(history.location.search).toBe('');
});

test('Bound query should be set to initial passed value from URL', () => {
    routeRegistry.getAll.mockReturnValue({
        list: {
            name: 'list',
            view: 'list',
            path: '/list',
        },
    });

    const value = observable();

    const history = createHistory();
    history.push('/list?page=2');
    const router = new Router(history);
    router.bindQuery('page', value, '1');

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
    });

    const detailRoute = extendObservable({
        name: 'sulu_snippet.form.detail',
        parent: formRoute,
        view: 'sulu_admin.form',
        path: '/detail',
        options: {
            tabTitle: 'Detail',
        },
    });

    const taxonomyRoute = extendObservable({
        name: 'sulu_snippet.form.taxonomy',
        parent: formRoute,
        view: 'sulu_admin.form',
        path: '/taxonomy',
        options: {
            tabTitle: 'Taxonomies',
        },
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

    expect(router.route.parent.view).toBe('sulu_admin.tab');
    expect(router.route.parent.options.resourceKey).toBe('snippet');
    expect(router.route.parent.children).toHaveLength(2);
    expect(router.route.parent.children[0]).toBe(router.route);
    expect(router.route.parent.children[1].options.tabTitle).toBe('Taxonomies');
});

test('Navigate to child route using URL', () => {
    const formRoute = extendObservable({
        name: 'sulu_snippet.form',
        view: 'sulu_admin.tab',
        path: '/snippets/:uuid',
        options: {
            resourceKey: 'snippet',
        },
    });

    const detailRoute = extendObservable({
        name: 'sulu_snippet.form.detail',
        parent: formRoute,
        view: 'sulu_admin.form',
        path: '/detail',
        options: {
            tabTitle: 'Detail',
        },
    });

    const taxonomyRoute = extendObservable({
        name: 'sulu_snippet.form.taxonomy',
        parent: formRoute,
        view: 'sulu_admin.form',
        path: '/taxonomy',
        options: {
            tabTitle: 'Taxonomies',
        },
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

    expect(router.route.parent.view).toBe('sulu_admin.tab');
    expect(router.route.parent.options.resourceKey).toBe('snippet');
    expect(router.route.parent.children).toHaveLength(2);
    expect(router.route.parent.children[0]).toBe(router.route);
    expect(router.route.parent.children[1].options.tabTitle).toBe('Taxonomies');
});
