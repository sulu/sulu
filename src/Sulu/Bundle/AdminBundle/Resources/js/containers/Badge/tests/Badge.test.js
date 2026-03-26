// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import Router from '../../../services/Router';
import Requester from '../../../services/Requester';
import Badge from '../Badge';
import BadgeStore from '../stores/BadgeStore';

jest.mock('../../../services/Requester', () => ({
    get: jest.fn(),
}));

Requester.handleResponseHooks = [];

const tabViewRoute: any = {};

jest.mock('../../../services/Router', () => jest.fn(function() {
    this.attributes = {
        id: 5,
        locale: 'en',
    };

    this.route = {
        parent: tabViewRoute,
    };
}));

test('Should create new BadgeStore', () => {
    const router = new Router({});
    const badgeRef = React.createRef();

    const promise = Promise.resolve({data: 'foo'});
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    render(
        <Badge
            dataPath="/data"
            ref={badgeRef}
            requestParameters={{
                limit: 0,
            }}
            routeName="foo"
            router={router}
            routerAttributesToRequest={{
                id: 'entityId',
                locale: 'locale',
            }}
            tabViewRoute={tabViewRoute}
            visibleCondition="value != 0"
        />
    );

    const store = badgeRef.current?.store;
    if (!store) {
        throw new Error('Expected badge store instance');
    }

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
    expect(store.tabViewRoute).toBe(tabViewRoute);

    return promise.then(() => {
        expect(store.value).toBe('foo');
    });
});

test('Should pass correct props to badge component', () => {
    const router = new Router({});

    const promise = Promise.resolve('hello');
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    render(
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
            tabViewRoute={tabViewRoute}
            visibleCondition="value != 0"
        />
    );

    return promise.then(async() => {
        await waitFor(() => {
            expect(screen.getByText('hello')).toBeInTheDocument();
        });
    });
});

test('Should not render Badge component if visibleCondition fails', () => {
    const router = new Router({});

    const promise = Promise.resolve({data: 0});
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    const {container} = render(
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
            tabViewRoute={tabViewRoute}
            visibleCondition="value != 0"
        />
    );

    return promise.then(async() => {
        await waitFor(() => {
            expect(container).toBeEmptyDOMElement();
        });
    });
});
