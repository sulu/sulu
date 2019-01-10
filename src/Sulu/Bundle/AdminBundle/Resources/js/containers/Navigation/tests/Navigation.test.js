// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import Navigation from '../Navigation';
import Router from '../../../services/Router';

jest.mock('../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('../registries/NavigationRegistry', () => ({
    get: jest.fn().mockReturnValue(
        {
            id: '111-111',
            title: 'Test Navigation',
            icon: 'su-options',
            mainRoute: 'returned_main_route',
        }
    ),
    getAll: jest.fn().mockReturnValue([
        {
            id: '111-111',
            title: 'Test Navigation',
            icon: 'su-options',
            mainRoute: 'sulu_admin.form_tab',
        },
        {
            id: '222-222',
            title: 'Test Navigation 2',
            icon: 'su-article',
            mainRoute: 'sulu_article.datagrid',
            childRoutes: ['sulu_article.form', 'sulu_article.form'],
        },
        {
            id: '333-333',
            title: 'Test Navigation with Children',
            icon: 'su-options',
            items: [
                {
                    id: '333-child1',
                    title: 'Test Navigation Child 1',
                    icon: 'su-options',
                    mainRoute: 'sulu_admin.form_tab',
                },
                {
                    id: '333-child2',
                    title: 'Test Navigation Child 2',
                    icon: 'su-article',
                    mainRoute: 'sulu_article.datagrid',
                    childRoutes: ['sulu_article.form', 'sulu_article.form'],
                },
            ],
        },
    ]),
}));

test('Should render navigation', () => {
    const router = new Router({});
    router.route = {
        name: 'sulu_admin.form_tab',
        view: 'form_tab',
        attributeDefaults: {},
        children: [],
        options: {},
        parent: undefined,
        path: '/form',
        rerenderAttributes: [],
    };

    const navigation = render(
        <Navigation
            appVersion="666"
            onLogout={jest.fn()}
            onNavigate={jest.fn()}
            onPinToggle={jest.fn()}
            pinned={false}
            router={router}
            suluVersion="2.0.0-RC1"
        />
    );

    expect(navigation).toMatchSnapshot();
});

test('Should render navigation without appVersion', () => {
    const router = new Router({});
    router.route = {
        name: 'sulu_admin.form_tab',
        view: 'form_tab',
        attributeDefaults: {},
        children: [],
        options: {},
        parent: undefined,
        path: '/form',
        rerenderAttributes: [],
    };

    const navigation = render(
        <Navigation
            appVersion={null}
            onLogout={jest.fn()}
            onNavigate={jest.fn()}
            onPinToggle={jest.fn()}
            pinned={false}
            router={router}
            suluVersion="2.0.0-RC1"
        />
    );

    expect(navigation).toMatchSnapshot();
});


test('Should call the navigation callback, pin callback and router navigate', () => {
    const router = new Router({});
    router.route = {
        name: 'sulu_admin.form_tab',
        view: 'form_tab',
        attributeDefaults: {},
        children: [],
        options: {},
        parent: undefined,
        path: '/form',
        rerenderAttributes: [],
    };
    const handleNavigate = jest.fn();
    const handlePin = jest.fn();

    const navigation = mount(
        <Navigation
            appVersion={null}
            onLogout={jest.fn()}
            onNavigate={handleNavigate}
            onPinToggle={handlePin}
            pinned={false}
            router={router}
            suluVersion="2.0.0-RC1"
        />
    );

    navigation.find('Item').at(4).find('.title').simulate('click');
    expect(router.navigate).toHaveBeenCalledWith('returned_main_route');
    expect(handleNavigate).toHaveBeenCalledWith('returned_main_route');

    navigation.find('Button.pin').simulate('click');
    expect(handlePin).toBeCalled();
});
