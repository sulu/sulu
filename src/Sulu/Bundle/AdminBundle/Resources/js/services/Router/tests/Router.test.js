/* eslint-disable flowtype/require-valid-file-annotation */
import Router from '../Router';
import createHistory from 'history/createMemoryHistory';
import {isObservable} from 'mobx';
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
