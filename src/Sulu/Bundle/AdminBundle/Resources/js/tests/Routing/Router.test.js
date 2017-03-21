/* eslint-disable flowtype/require-valid-file-annotation */
import createHistory from 'history/createMemoryHistory';
import Router from '../../Routing/Router';
import Route from '../../Routing/Route';

test('Navigate to route using state', () => {
    const history = createHistory();

    const router = new Router(history);
    router.add(new Route('page', 'form', '/pages/:uuid', {type: 'page'}));

    router.navigate('page', {uuid: 'some-uuid'});
    expect(router.currentRoute.view).toBe('form');
    expect(router.currentRoute.parameters.type).toBe('page');
    expect(router.currentParameters.uuid).toBe('some-uuid');
    expect(router.currentParameters.type).toBe('page');
    expect(history.location.pathname).toBe('/pages/some-uuid');
});

test('Navigate to route using URL', () => {
    const history = createHistory();

    const router = new Router(history);
    router.add(new Route('page', 'form', '/pages/:uuid/:test', {type: 'page'}));

    history.push('/pages/some-uuid/value');
    expect(router.currentRoute.view).toBe('form');
    expect(router.currentRoute.parameters.type).toBe('page');
    expect(router.currentParameters.uuid).toBe('some-uuid');
    expect(router.currentParameters.test).toBe('value');
    expect(router.currentParameters.type).toBe('page');
    expect(history.location.pathname).toBe('/pages/some-uuid/value');
});

test('Navigate to route changing only parameters', () => {
    const history = createHistory();

    const router = new Router(history);
    router.add(new Route('page', 'form', '/pages/:uuid'));

    router.navigate('page', {uuid: 'some-uuid'});
    expect(history.location.pathname).toBe('/pages/some-uuid');

    router.navigate('page', {uuid: 'some-other-uuid'});
    expect(history.location.pathname).toBe('/pages/some-other-uuid');
});
