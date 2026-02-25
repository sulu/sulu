// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Navigation from '../Navigation';
import Router, {Route} from '../../../services/Router';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';
import type {NavigationItem} from '../types';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('../../../components/Navigation', () => {
    const React = require('react');

    const Navigation: any = jest.fn(function NavigationMock({children}) {
        return React.createElement('div', undefined, children);
    });

    Navigation.Item = jest.fn(function NavigationItemMock({children}) {
        return React.createElement('div', undefined, children);
    });

    return Navigation;
});

jest.mock('../registries/navigationRegistry', () => ({
    get: jest.fn().mockReturnValue(
        ({
            id: '111-111',
            title: 'Test Navigation',
            label: '',
            icon: 'su-options',
            view: 'returned_main_route',
            visible: true,
        }: NavigationItem)
    ),
    getAll: jest.fn().mockReturnValue(([
        {
            id: '111-111',
            title: 'Test Navigation',
            label: '',
            icon: 'su-options',
            view: 'sulu_admin.form_tab',
            visible: true,
        },
        {
            id: '222-222',
            title: 'Test Navigation 2',
            label: '',
            icon: 'su-article',
            view: 'sulu_article.list',
            childViews: ['sulu_article.form', 'sulu_article.form'],
            visible: true,
        },
        {
            id: '111-222',
            title: 'Hidden Navigation Item',
            label: '',
            icon: 'su-options',
            view: 'sulu_admin.form_tab',
            visible: false,
        },
        {
            id: '333-333',
            title: 'Test Navigation with Children',
            label: '',
            icon: 'su-options',
            visible: true,
            items: [
                {
                    id: '333-child1',
                    title: 'Test Navigation Child 1',
                    label: '',
                    icon: 'su-options',
                    view: 'sulu_admin.form_tab',
                    visible: true,
                },
                {
                    id: '333-child2',
                    title: 'Test Navigation Child 2',
                    label: '',
                    icon: 'su-article',
                    view: 'sulu_article.list',
                    childViews: ['sulu_article.form', 'sulu_article.form'],
                    visible: true,
                },
                {
                    id: '333-child3',
                    title: 'Test Navigation Child 1',
                    label: '',
                    icon: 'su-options',
                    view: 'sulu_admin.form_tab',
                    visible: false,
                },
            ],
        },
    ]: Array<NavigationItem>)),
}));

const navigationComponent = ((jest.requireMock('../../../components/Navigation'): any): {
    Item: {mock: {calls: Array<[Object]>}, ...},
    mock: {calls: Array<[Object]>},
    ...
});

function createRouter() {
    const router = new Router({});
    router.route = new Route({
        name: 'sulu_admin.form_tab',
        path: '/form',
        type: 'form_tab',
    });

    return router;
}

function getLatestNavigationProps(): any {
    return getLatestMockProps(navigationComponent);
}

test('Should render navigation', () => {
    const {asFragment} = render(
        <Navigation
            appVersion="666"
            onLogout={jest.fn()}
            onNavigate={jest.fn()}
            onPinToggle={jest.fn()}
            onProfileClick={jest.fn()}
            pinned={false}
            router={createRouter()}
            suluVersion="2.0.0-RC1"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render navigation without appVersion', () => {
    const {asFragment} = render(
        <Navigation
            appVersion={null}
            onLogout={jest.fn()}
            onNavigate={jest.fn()}
            onPinToggle={jest.fn()}
            onProfileClick={jest.fn()}
            pinned={false}
            router={createRouter()}
            suluVersion="2.0.0-RC1"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should call the navigation callback, pin callback and router navigate', () => {
    const router = createRouter();
    const handleNavigate = jest.fn();
    const handlePin = jest.fn();

    render(
        <Navigation
            appVersion={null}
            onLogout={jest.fn()}
            onNavigate={handleNavigate}
            onPinToggle={handlePin}
            onProfileClick={jest.fn()}
            pinned={false}
            router={router}
            suluVersion="2.0.0-RC1"
        />
    );

    getLatestNavigationProps().onItemClick('111-111');
    expect(router.navigate).toHaveBeenCalledWith('returned_main_route');
    expect(handleNavigate).toHaveBeenCalledWith('returned_main_route');

    getLatestNavigationProps().onPinToggle();
    expect(handlePin).toBeCalled();
});
