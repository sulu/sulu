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

test('Should create a new BadgeStore', () => {
    const router = new Router({});

    const promise = Promise.resolve('hello');
    Requester.get.mockReturnValue(promise);

    const badge = mount(
        <Badge
            attributesToRequest={{
                limit: 0,
            }}
            dataPath="/bar"
            routeName="foo"
            router={router}
            routerAttributesToRequest={{
                id: 'entityId',
                locale: 'locale',
            }}
            visibleCondition="text != 0"
        />
    );

    const store = badge.instance().store;

    expect(store).toBeInstanceOf(BadgeStore);
    expect(store.routeName).toBe('foo');
    expect(store.dataPath).toBe('/bar');
    expect(store.visibleCondition).toBe('text != 0');
    expect(store.attributesToRequest).toEqual({
        limit: 0,
    });
    expect(store.routerAttributesToRequest).toEqual({
        id: 'entityId',
        locale: 'locale',
    });
});
