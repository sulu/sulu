// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Navigation from '../Navigation';
import Router, {Route} from '../../../services/Router';
import type {NavigationItem} from '../types';

let mockNavigationProps: Object = {};
let mockNavigationItemProps: Array<Object> = [];

const mockReact = require('react');

jest.mock('../../../utils/Translator');

jest.mock('../../../services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('../../../components/Navigation', () => {
    const NavigationMock: any = jest.fn((props) => {
        mockNavigationProps = props;

        return mockReact.createElement(
            'nav',
            {},
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'pin',
                    onClick: props.onPinToggle,
                    type: 'button',
                },
                'Pin'
            ),
            props.children
        );
    });

    NavigationMock.Item = jest.fn((props) => {
        mockNavigationItemProps.push(props);

        return mockReact.createElement(
            'div',
            {
                'data-active': props.active ? 'true' : 'false',
                'data-testid': 'navigation-item',
            },
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'item-' + props.value,
                    onClick: () => mockNavigationProps.onItemClick(props.value),
                    type: 'button',
                },
                props.value
            ),
            props.children
        );
    });

    return NavigationMock;
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

beforeEach(() => {
    jest.clearAllMocks();
    mockNavigationProps = {};
    mockNavigationItemProps = [];
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

function renderNavigation(props: Object = {}) {
    return render(
        <Navigation
            appVersion="666"
            onLogout={jest.fn()}
            onNavigate={jest.fn()}
            onPinToggle={jest.fn()}
            onProfileClick={jest.fn()}
            pinned={false}
            router={createRouter()}
            suluVersion="2.0.0-RC1"
            {...props}
        />
    );
}

function getNavigationItemProps(value) {
    const itemProps = mockNavigationItemProps.find((item) => item.value === value);

    if (!itemProps) {
        throw new Error('Navigation item "' + value + '" was not rendered.');
    }

    return itemProps;
}

test('Should render navigation', () => {
    renderNavigation();

    expect(mockNavigationProps).toEqual(expect.objectContaining({
        appVersion: '666',
        pinned: false,
        suluVersion: '2.0.0-RC1',
        suluVersionLink: 'https://github.com/sulu/sulu/releases',
        title: 'Sulu',
    }));
    expect(mockNavigationItemProps.map((item) => item.value)).toEqual([
        '111-111',
        '222-222',
        '333-333',
        '333-child1',
        '333-child2',
    ]);
    expect(getNavigationItemProps('111-111').active).toEqual(true);
    expect(getNavigationItemProps('333-child1').active).toEqual(true);
});

test('Should render navigation without appVersion', () => {
    renderNavigation({appVersion: null});

    expect(mockNavigationProps.appVersion).toBeNull();
});

test('Should call the navigation callback, pin callback and router navigate', async() => {
    const router = createRouter();
    const handleNavigate = jest.fn();
    const handlePin = jest.fn();

    renderNavigation({
        appVersion: null,
        onNavigate: handleNavigate,
        onPinToggle: handlePin,
        router,
    });

    await userEvent.click(screen.getByLabelText('item-111-111'));

    expect(router.navigate).toHaveBeenCalledWith('returned_main_route');
    expect(handleNavigate).toHaveBeenCalledWith('returned_main_route');

    await userEvent.click(screen.getByLabelText('pin'));

    expect(handlePin).toHaveBeenCalled();
});
