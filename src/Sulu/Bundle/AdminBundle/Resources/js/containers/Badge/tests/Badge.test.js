// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {extendObservable as mockExtendObservable} from 'mobx';
import Router from '../../../services/Router';
import Badge from '../Badge';
import BadgeStore from '../stores/BadgeStore';

const tabViewRoute: any = {};

jest.mock('../stores/BadgeStore', () => jest.fn(function(
    router,
    routeName,
    dataPath,
    requestParameters,
    routerAttributesToRequest,
    tabViewRoute
) {
    this.router = router;
    this.routeName = routeName;
    this.dataPath = dataPath;
    this.requestParameters = requestParameters;
    this.routerAttributesToRequest = routerAttributesToRequest;
    this.tabViewRoute = tabViewRoute;
    this.destroy = jest.fn();

    mockExtendObservable(this, {
        value: null,
    });
}));

jest.mock('../../../services/Router', () => jest.fn(function() {
    this.attributes = {
        id: 5,
        locale: 'en',
    };

    this.route = {
        parent: tabViewRoute,
    };
}));

const BadgeStoreMock = (BadgeStore: any);

const getBadgeStore = () => {
    const stores = BadgeStoreMock.mock.instances;
    if (stores.length === 0) {
        throw new Error('Expected BadgeStore to be instantiated');
    }

    return stores[stores.length - 1];
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should create new BadgeStore', () => {
    const router = new Router({});

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

    expect(BadgeStore).toBeCalledWith(
        router,
        'foo',
        '/data',
        {limit: 0},
        {id: 'entityId', locale: 'locale'},
        tabViewRoute
    );
});

test('Should pass correct props to badge component', () => {
    const router = new Router({});

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

    act(() => {
        getBadgeStore().value = 'hello';
    });

    expect(screen.getByText('hello')).toBeInTheDocument();
});

test('Should not render Badge component if visibleCondition fails', () => {
    const router = new Router({});

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

    act(() => {
        getBadgeStore().value = 0;
    });

    expect(container).toBeEmptyDOMElement();
});
