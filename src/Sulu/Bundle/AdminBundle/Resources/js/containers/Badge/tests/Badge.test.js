// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
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

const createBadgeProps = (props: Object = {}) => {
    const router = new Router({});

    return {
        dataPath: '/data',
        requestParameters: {
            limit: 0,
        },
        routeName: 'foo',
        router,
        routerAttributesToRequest: {
            id: 'entityId',
            locale: 'locale',
        },
        tabViewRoute,
        visibleCondition: 'value != 0',
        ...props,
    };
};

test('Should create new BadgeStore', () => {
    const promise = Promise.resolve({data: 'foo'});
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    const ref: any = React.createRef();
    render(<Badge {...createBadgeProps()} ref={ref} />);

    if (!ref.current) {
        throw new Error('Expected Badge ref to be set');
    }

    const store = ref.current.store;

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
    const promise = Promise.resolve('hello');
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    const badgeProps = createBadgeProps({dataPath: null});
    render(<Badge {...badgeProps} />);

    return promise.then(async() => {
        expect(await screen.findByText('hello')).toBeInTheDocument();
    });
});

test('Should not render Badge component if visibleCondition fails', () => {
    const promise = Promise.resolve({data: 0});
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    const badgeProps = createBadgeProps();
    render(<Badge {...badgeProps} />);

    return promise.then(() => {
        expect(screen.queryByText('0')).not.toBeInTheDocument();
    });
});
