/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import ResourceTabs from '../ResourceTabs';

jest.mock('../../../services/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'tabTitle1':
                return 'Tab Titel 1';
            case 'tabTitle2':
                return 'Tab Titel 2';
        }
    },
}));

test('Should render the child components after the tabs', () => {
    const route = {
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };
    const Child = () => (<h1>Child</h1>);

    expect(render(<ResourceTabs route={route}><Child /></ResourceTabs>)).toMatchSnapshot();
});

test('Should mark the currently active child route as selected tab', () => {
    const childRoute1 = {
        name: 'Tab 1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'Tab 2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const Child = () => (<h1>Child</h1>);

    expect(render(<ResourceTabs route={route}><Child route={childRoute2} /></ResourceTabs>)).toMatchSnapshot();
});

test('Should navigate to child route if tab is clicked', () => {
    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const childRoute2 = {
        name: 'route2',
        options: {},
    };
    const route = {
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        attribute: 'value',
    };
    const query = {
        query: 'value',
    };

    const router = {
        navigate: jest.fn(),
        attributes,
        query,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs router={router} route={route}><Child /></ResourceTabs>);

    resourceTabs.find('Tab button').at(1).simulate('click');

    expect(router.navigate).toBeCalledWith('route2', attributes, query);
});
