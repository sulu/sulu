// @flow
import {mount} from 'enzyme';
import React from 'react';
import Router from '../../../services/Router';
import Requester from '../../../services/Requester';
import Badge from '../Badge';
import BadgeStore from '../stores/BadgeStore';

jest.mock('../../../services/Requester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../services/Router', () => jest.fn(function() {
    this.attributes = {
        id: 5,
        locale: 'en',
    };
}));

test('Should create new BadgeStore', () => {
    const router = new Router({});

    const promise = Promise.resolve({data: 'foo'});
    Requester.get.mockReturnValue(promise);

    const badge = mount(
        <Badge
            dataPath="/data"
            requestParameters={{
                limit: 0,
            }}
            routeName="foo"
            router={router}
            routerAttributesToRequest={{
                id: 'entityId',
                locale: 'locale',
            }}
            visibleCondition="value != 0"
        />
    );

    const store = badge.instance().store;

    expect(store).toBeInstanceOf(BadgeStore);
    expect(store.routeName).toBe('foo');
    expect(store.dataPath).toBe('/data');
    expect(store.requestParameters).toEqual({
        limit: 0,
    });
    expect(store.routerAttributesToRequest).toEqual({
        id: 'entityId',
        locale: 'locale',
    });

    return promise.then(() => {
        expect(store.value).toBe('foo');
    });
});

test('Should pass correct props to badge component', () => {
    const router = new Router({});

    const promise = Promise.resolve('hello');
    Requester.get.mockReturnValue(promise);

    const badge = mount(
        <Badge
            dataPath={null}
            requestParameters={{
                limit: 0,
            }}
            routeName="foo"
            router={router}
            routerAttributesToRequest={{
                id: 'entityId',
                locale: 'locale',
            }}
            visibleCondition="value != 0"
        />
    );

    return promise.then(() => {
        badge.update();
        expect(badge.children().find('Badge').length).toBe(1);
        expect(badge.children().find('Badge').text()).toBe('hello');
    });
});

test('Should not render Badge component if visibleCondition fails', () => {
    const router = new Router({});

    const promise = Promise.resolve({data: 0});
    Requester.get.mockReturnValue(promise);

    const badge = mount(
        <Badge
            dataPath="/data"
            requestParameters={{
                limit: 0,
            }}
            routeName="foo"
            router={router}
            routerAttributesToRequest={{
                id: 'entityId',
                locale: 'locale',
            }}
            visibleCondition="value != 0"
        />
    );

    return promise.then(() => {
        badge.update();
        expect(badge.children().find('Badge').length).toBe(0);
    });
});
