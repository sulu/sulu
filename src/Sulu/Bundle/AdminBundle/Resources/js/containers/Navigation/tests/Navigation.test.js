// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import Navigation from '../Navigation';
import Router, {Route} from '../../../services/Router';
import type {NavigationItem} from '../types';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

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

test('Should render navigation', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'sulu_admin.form_tab',
        path: '/form',
        type: 'form_tab',
    });

    const navigation = render(
        <Navigation
            appVersion="666"
            onLogout={jest.fn()}
            onNavigate={jest.fn()}
            onPinToggle={jest.fn()}
            onProfileClick={jest.fn()}
            pinned={false}
            router={router}
            suluVersion="2.0.0-RC1"
        />
    );

    expect(navigation).toMatchSnapshot();
});

test('Should render navigation without appVersion', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'sulu_admin.form_tab',
        path: '/form',
        type: 'form_tab',
    });

    const navigation = render(
        <Navigation
            appVersion={null}
            onLogout={jest.fn()}
            onNavigate={jest.fn()}
            onPinToggle={jest.fn()}
            onProfileClick={jest.fn()}
            pinned={false}
            router={router}
            suluVersion="2.0.0-RC1"
        />
    );

    expect(navigation).toMatchSnapshot();
});

test('Should call the navigation callback, pin callback and router navigate', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'sulu_admin.form_tab',
        path: '/form',
        type: 'form_tab',
    });
    const handleNavigate = jest.fn();
    const handlePin = jest.fn();

    const navigation = mount(
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

    navigation.find('Item').at(4).find('.title').simulate('click');
    expect(router.navigate).toHaveBeenCalledWith('returned_main_route');
    expect(handleNavigate).toHaveBeenCalledWith('returned_main_route');

    navigation.find('.pin').simulate('click');
    expect(handlePin).toBeCalled();
});
