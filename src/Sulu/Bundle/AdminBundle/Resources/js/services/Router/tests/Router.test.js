/* eslint-disable flowtype/require-valid-file-annotation */
import 'url-search-params-polyfill';
import createHistory from 'history/createMemoryHistory';
import {isObservable} from 'mobx';
import Router from '../Router';
import routeStore from '../stores/RouteStore';

jest.mock('../stores/RouteStore', () => {
    const getAllMock = jest.fn();

    return {
        getAll: getAllMock,
        get: jest.fn((key) => getAllMock()[key]),
    };
});

test('Navigate to route using state', () => {
    routeStore.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
            parameters: {
                type: 'page',
            },
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(isObservable(router.currentRoute)).toBe(true);
    expect(router.currentRoute.view).toBe('form');
    expect(router.currentRoute.parameters.type).toBe('page');
    expect(router.currentParameters.uuid).toBe('some-uuid');
    expect(router.currentParameters.type).toBe('page');
    expect(history.location.pathname).toBe('/pages/some-uuid');
});

test('Navigate to route with search parameters using state', () => {
    routeStore.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
            parameters: {
                type: 'page',
            },
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'}, {page: '1', sort: 'title'});
    expect(isObservable(router.currentRoute)).toBe(true);
    expect(router.currentRoute.view).toBe('form');
    expect(router.currentRoute.parameters.type).toBe('page');
    expect(router.currentParameters.uuid).toBe('some-uuid');
    expect(router.currentParameters.type).toBe('page');
    expect(router.currentSearchParameters.page).toBe('1');
    expect(router.currentSearchParameters.sort).toBe('title');
    expect(history.location.pathname).toBe('/pages/some-uuid');
    expect(history.location.search).toBe('?page=1&sort=title');
});

test('Navigate to route without parameters using state', () => {
    routeStore.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(router.currentRoute.name).toBe('page');
});

test('Navigate to route using URL', () => {
    routeStore.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid/:test',
            parameters: {
                type: 'page',
            },
        },
    });

    const history = createHistory();
    const router = new Router(history);

    history.push('/pages/some-uuid/value');
    expect(router.currentRoute.view).toBe('form');
    expect(router.currentRoute.parameters.type).toBe('page');
    expect(router.currentParameters.uuid).toBe('some-uuid');
    expect(router.currentParameters.test).toBe('value');
    expect(router.currentParameters.type).toBe('page');
    expect(history.location.pathname).toBe('/pages/some-uuid/value');
});

test('Navigate to route using URL with search parameters', () => {
    routeStore.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid/:test',
            parameters: {
                type: 'page',
            },
        },
    });

    const history = createHistory();
    const router = new Router(history);

    history.push('/pages/some-uuid/value?page=1&sort=date');
    expect(router.currentRoute.view).toBe('form');
    expect(router.currentRoute.parameters.type).toBe('page');
    expect(router.currentParameters.uuid).toBe('some-uuid');
    expect(router.currentParameters.test).toBe('value');
    expect(router.currentParameters.type).toBe('page');
    expect(router.currentSearchParameters.page).toBe('1');
    expect(router.currentSearchParameters.sort).toBe('date');
    expect(history.location.pathname).toBe('/pages/some-uuid/value');
    expect(history.location.search).toBe('?page=1&sort=date');
});

test('Navigate to route changing only parameters', () => {
    routeStore.getAll.mockReturnValue({
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
    routeStore.getAll.mockReturnValue({
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
    routeStore.getAll.mockReturnValue({
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
    routeStore.getAll.mockReturnValue({
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
    routeStore.getAll.mockReturnValue({
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
    routeStore.getAll.mockReturnValue({
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
    routeStore.getAll.mockReturnValue({
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
    routeStore.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'form',
            path: '/pages/:uuid',
        },
    });

    const history = createHistory();
    const router = new Router(history);

    router.navigate('page', {uuid: 'some-uuid'});
    const expectedParameters = router.currentParameters;
    expect(router.currentParameters).toBe(expectedParameters);

    router.navigate('page', {uuid: 'some-uuid'});
    expect(router.currentParameters).toBe(expectedParameters);
});

test('Use current route from URL', () => {
    routeStore.getAll.mockReturnValue({
        page: {
            name: 'page',
            view: 'page',
            path: '/page',
        },
    });

    const history = createHistory();
    history.push('/page');

    const router = new Router(history);

    expect(router.currentRoute.name).toBe('page');
});
