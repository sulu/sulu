// @flow
import SymfonyRouting from 'fos-jsrouting/router';
import {observable} from 'mobx';
import resourceRouteRegistry from '../../registries/resourceRouteRegistry';

test('Set and get endpoints for given key', () => {
    SymfonyRouting.generate.mockImplementation((routeName, {value}) => {
        return routeName + '?value=' + value;
    });

    resourceRouteRegistry.setEndpoints({
        snippets: {
            routes: {
                detail: 'get_snippet',
                list: 'get_snippets',
            },
        },
    });

    expect(resourceRouteRegistry.getDetailUrl('snippets', {value: 1})).toEqual('get_snippet?value=1');
    expect(resourceRouteRegistry.getListUrl('snippets', {value: 2})).toEqual('get_snippets?value=2');
});

test('Set and get endpoints for given key with date parameter', () => {
    SymfonyRouting.generate.mockImplementation((routeName, {value}) => {
        return routeName + '?value=' + value;
    });

    resourceRouteRegistry.setEndpoints({
        snippets: {
            routes: {
                detail: 'get_snippet',
                list: 'get_snippets',
            },
        },
    });

    expect(resourceRouteRegistry.getDetailUrl('snippets', {value: new Date('2013-12-24 00:00')}))
        .toEqual('get_snippet?value=2013-12-24 00:00');
    expect(resourceRouteRegistry.getListUrl('snippets', {value: new Date('2020-09-07 00:00')}))
        .toEqual('get_snippets?value=2020-09-07 00:00');
});

test('Set and get endpoints for given key with boxed observable parameter', () => {
    SymfonyRouting.generate.mockImplementation((routeName, {value}) => {
        return routeName + '?value=' + value;
    });

    resourceRouteRegistry.setEndpoints({
        snippets: {
            routes: {
                detail: 'get_snippet',
                list: 'get_snippets',
            },
        },
    });

    expect(resourceRouteRegistry.getDetailUrl('snippets', {value: observable.box('boxed-value')}))
        .toEqual('get_snippet?value=boxed-value');
    expect(resourceRouteRegistry.getListUrl('snippets', {value: observable.box('boxed-value')}))
        .toEqual('get_snippets?value=boxed-value');
});

test('Set and get endpoints for given key with date array parameter', () => {
    SymfonyRouting.generate.mockImplementation((routeName, {dates}) => {
        return routeName + '?dates=' + dates;
    });

    resourceRouteRegistry.setEndpoints({
        snippets: {
            routes: {
                detail: 'get_snippet',
                list: 'get_snippets',
            },
        },
    });

    expect(resourceRouteRegistry.getDetailUrl(
        'snippets',
        {dates: [new Date('2013-12-24 12:00'), new Date('2020-12-24 00:00')]})
    ).toEqual('get_snippet?dates=2013-12-24 12:00,2020-12-24 00:00');
    expect(resourceRouteRegistry.getListUrl(
        'snippets',
        {dates: [new Date('2020-09-07 00:00'), new Date('2020-11-05 00:00')]})
    ).toEqual('get_snippets?dates=2020-09-07 00:00,2020-11-05 00:00');
});

test('Set and get endpoints for given key with date in object parameter', () => {
    resourceRouteRegistry.setEndpoints({
        snippets: {
            routes: {
                detail: 'get_snippet',
                list: 'get_snippets',
            },
        },
    });

    resourceRouteRegistry.getDetailUrl('snippets', {value: {name: 'test', date: new Date('2020-09-07 00:00')}});
    expect(SymfonyRouting.generate).toBeCalledWith(
        'get_snippet',
        {'value': {'name': 'test', 'date': '2020-09-07 00:00'}}
    );

    resourceRouteRegistry.getListUrl('snippets', {value: {name: 'test', date: new Date('2020-09-07 00:00')}});
    expect(SymfonyRouting.generate).toBeCalledWith(
        'get_snippets',
        {'value': {'name': 'test', 'date': '2020-09-07 00:00'}}
    );
});

test('Throw exception when getting detail url for not existing key', () => {
    expect(() => resourceRouteRegistry.getDetailUrl('not-existing')).toThrow(/"not-existing"/);
});

test('Throw exception when getting list url for not existing key', () => {
    expect(() => resourceRouteRegistry.getListUrl('not-existing')).toThrow(/"not-existing"/);
});

test('Throw exception when getting detail url for not existing detail url', () => {
    resourceRouteRegistry.setEndpoints({
        existing: {
            routes: {},
        },
    });

    expect(() => resourceRouteRegistry.getDetailUrl('existing'))
        .toThrow(/no detail route for the resourceKey "existing"/);
});

test('Throw exception when getting detail url for not existing list url', () => {
    resourceRouteRegistry.setEndpoints({
        existing: {
            routes: {},
        },
    });

    expect(() => resourceRouteRegistry.getListUrl('existing'))
        .toThrow(/no list route for the resourceKey "existing"/);
});
