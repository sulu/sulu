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
    const handleNavigate = jest.fn();

    const navigation = render(<Navigation router={router} onNavigate={handleNavigate} />);
    expect(navigation).toMatchSnapshot();
});

test('Should call the navigation callback and router navigate', () => {
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

    const navigation = mount(<Navigation router={router} onNavigate={handleNavigate} />);
    navigation.find('Item').at(4).find('.title').simulate('click');
    expect(router.navigate).toHaveBeenCalledWith('returned_main_route');
    expect(handleNavigate).toHaveBeenCalledWith('returned_main_route');
});
