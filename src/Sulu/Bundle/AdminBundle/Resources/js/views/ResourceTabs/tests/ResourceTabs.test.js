/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import {observable} from 'mobx';
import React from 'react';
import ResourceTabs from '../ResourceTabs';
import ResourceStore from '../../../stores/ResourceStore';

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'tabTitle1':
                return 'Tab Titel 1';
            case 'tabTitle2':
                return 'Tab Titel 2';
        }
    },
}));

jest.mock('../../../stores/ResourceStore');

test('Should render the child components after the tabs', () => {
    const route = {
        options: {
            resourceKey: 'test',
        },
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
    const router = {
        attributes: {
            id: 1,
        },
        route,
    };

    const Child = () => (<h1>Child</h1>);

    expect(render(<ResourceTabs router={router} route={route}>{() => (<Child />)}</ResourceTabs>)).toMatchSnapshot();
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
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const router = {
        attributes: {
            id: 1,
        },
        route,
    };

    const Child = () => (<h1>Child</h1>);

    expect(render(<ResourceTabs router={router} route={route}>{() => (<Child route={childRoute2} />)}</ResourceTabs>))
        .toMatchSnapshot();
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
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = {
        navigate: jest.fn(),
        route,
        attributes,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs router={router} route={route}>{() => (<Child />)}</ResourceTabs>);

    resourceTabs.find('Tab button').at(1).simulate('click');

    expect(router.navigate).toBeCalledWith('route2', attributes);
});

test('Should create a ResourceStore on mount and destroy it on unmount', () => {
    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
        },
    };
    const router = {
        route,
        attributes: {
            id: 5,
        },
    };

    const resourceTabs = mount(<ResourceTabs router={router} route={route}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).not.toBeDefined();

    resourceTabs.unmount();
    expect(ResourceStore.mock.instances[0].destroy).toBeCalled();
});

test('Should create a ResourceStore with locale on mount if locales have been passed in route options', () => {
    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: ['de', 'en'],
        },
    };
    const router = {
        route,
        attributes: {
            id: 5,
        },
    };

    const resourceTabs = mount(<ResourceTabs router={router} route={route}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).toBeDefined();

    resourceTabs.unmount();
    expect(ResourceStore.mock.instances[0].destroy).toBeCalled();
});

test('Should create a ResourceStore with locale on mount if locales have been passed as observable array', () => {
    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: observable(['de', 'en']),
        },
    };
    const router = {
        route,
        attributes: {
            id: 5,
        },
    };

    const resourceTabs = mount(<ResourceTabs router={router} route={route}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).toBeDefined();

    resourceTabs.unmount();
    expect(ResourceStore.mock.instances[0].destroy).toBeCalled();
});

test('Should pass the ResourceStore and locales to child components', () => {
    const locales = observable(['de', 'en']);
    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales,
        },
    };
    const router = {
        route,
        attributes: {
            id: 5,
        },
    };

    const ChildComponent = jest.fn(() => null);
    const resourceTabs = mount(
        <ResourceTabs
            locales={[]}
            router={router}
            route={route}
        >
            {(props) => (<ChildComponent {...props} />)}
        </ResourceTabs>
    ).instance();

    expect(ChildComponent.mock.calls[0][0].resourceStore).toBe(resourceTabs.resourceStore);
    expect(ChildComponent.mock.calls[0][0].locales).toBe(locales);
});

test('Should pass locales from route options instead of props to child components', () => {
    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: ['de', 'en'],
        },
    };
    const router = {
        route,
        attributes: {
            id: 5,
        },
    };

    const ChildComponent = jest.fn(() => null);
    const resourceTabs = mount(
        <ResourceTabs locales={['fr', 'nl']} router={router} route={route}>
            {(props) => (<ChildComponent {...props} />)}
        </ResourceTabs>
    ).instance();

    expect(ChildComponent.mock.calls[0][0].resourceStore).toBe(resourceTabs.resourceStore);
    expect(ChildComponent.mock.calls[0][0].locales).toEqual(['de', 'en']);
});
