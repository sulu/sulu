// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import SymfonyRouting from 'fos-jsrouting/router';
import Router from '../../../services/Router';
import Requester from '../../../services/Requester';
import Badge from '../Badge';

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

    const promise = Promise.resolve({data: 'foo'});
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];
    SymfonyRouting.generate.mockReturnValue('badge-url');

    const {unmount} = render(
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

    expect(SymfonyRouting.generate).toHaveBeenCalledWith('foo', {
        entityId: 5,
        limit: 0,
        locale: 'en',
    });
    expect(Requester.get).toHaveBeenCalledWith('badge-url');
    expect(Requester.handleResponseHooks).toHaveLength(1);

    unmount();

    expect(Requester.handleResponseHooks).toHaveLength(0);
});

test('Should pass correct props to badge component', async() => {
    const router = new Router({});

    const promise = Promise.resolve('hello');
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];
    SymfonyRouting.generate.mockReturnValue('badge-url');

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

    expect(await screen.findByText('hello')).toBeInTheDocument();
});

test('Should not render Badge component if visibleCondition fails', async() => {
    const router = new Router({});

    const promise = Promise.resolve({data: 0});
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];
    SymfonyRouting.generate.mockReturnValue('badge-url');

    render(
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

    await waitFor(() => expect(promise).resolves.toEqual({data: 0}));

    expect(screen.queryByText('0')).not.toBeInTheDocument();
});
