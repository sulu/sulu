/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import {extendObservable, observable} from 'mobx';
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
            case 'tabTitle3':
                return 'Tab Titel 3';
            case 'tabTitle4':
                return 'Tab Titel 4';
        }
    },
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn());

beforeEach(() => {
    ResourceStore.mockReset();
});

test('Should render the tab title from the ResourceStore as configured in the route', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
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
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    resourceTabs.instance().resourceStore.data = {test1: 'value1'};
    resourceTabs.update();

    expect(resourceTabs.find('ResourceTabs > h1').text()).toEqual('value1');
});

test('Should not render the tab title from the ResourceStore if no titleProperty is set', () => {
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
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    resourceTabs.instance().resourceStore.data = {test1: 'value1'};
    resourceTabs.update();

    expect(resourceTabs.find('ResourceTabs > h1')).toHaveLength(0);
});

test('Should render the tab title from the resourceStore as configured in the props', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
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
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router} titleProperty="test2">
            {() => (<Child />)}
        </ResourceTabs>
    );

    resourceTabs.instance().resourceStore.data = {test1: 'value1', test2: 'value2'};
    resourceTabs.update();

    expect(resourceTabs.find('ResourceTabs > h1').text()).toEqual('value2');
});

test('Should not render the tab title on the first tab', () => {
    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
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
        route: route.children[0],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router} titleProperty="test2">
            {() => (<Child route={route.children[0]} />)}
        </ResourceTabs>
    );

    resourceTabs.instance().resourceStore.data = {test1: 'value1', test2: 'value2'};
    setTimeout(() => {
        resourceTabs.update();
        expect(resourceTabs.find('ResourceTabs > h1')).toHaveLength(0);
    });
});

test('Should not render the tab title on the first tab when tabOrder is defined', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
        },
        children: [
            {
                name: 'Tab 2',
                options: {
                    tabOrder: 2,
                    tabTitle: 'tabTitle2',
                },
            },
            {
                name: 'Tab 1',
                options: {
                    tabOrder: 1,
                    tabTitle: 'tabTitle1',
                },
            },
        ],
    };
    const router = {
        attributes: {
            id: 1,
        },
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router}>
            {() => (<Child route={route.children[1]} />)}
        </ResourceTabs>
    );

    resourceTabs.instance().resourceStore.data = {test1: 'value1'};
    setTimeout(() => {
        resourceTabs.update();
        expect(resourceTabs.find('ResourceTabs > h1')).toHaveLength(0);
        done();
    });
});

test('Should render the tab title on the first visible tab if the first tab is not visible', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabCondition: 'test == 1',
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
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router}>
            {() => (<Child route={route.children[1]} />)}
        </ResourceTabs>
    );

    resourceTabs.instance().resourceStore.data = {test1: 'value1'};
    setTimeout(() => {
        resourceTabs.update();
        expect(resourceTabs.find('ResourceTabs > h1').text()).toEqual('value1');
        done();
    });
});

test('Should render the child components after the tabs', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

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
        route: route.children[0],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    setTimeout(() => {
        expect(resourceTabs.find('Loader')).toHaveLength(0);
        expect(resourceTabs.find('ResourceTabs Tabs').render()).toMatchSnapshot();
        expect(resourceTabs.find('ResourceTabs Child').render()).toMatchSnapshot();
        done();
    });
});

test('Should render a loader if resourceStore was not initialized yet', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = false;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

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
        route: route.children[0],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    expect(resourceTabs.find('Loader')).toHaveLength(1);
    expect(resourceTabs.find('Child')).toHaveLength(0);
});

test('Should mark the currently active child route as selected tab', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

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
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router}>{() => (<Child route={route.children[1]} />)}</ResourceTabs>
    );

    setTimeout(() => {
        expect(resourceTabs.find('ResourceTabs Tabs').render()).toMatchSnapshot();
        expect(resourceTabs.find('ResourceTabs Child').render()).toMatchSnapshot();
        done();
    });
});

test('Should consider the tabOrder option of the route', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'Tab 1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'Tab 2',
        options: {
            tabOrder: 40,
            tabTitle: 'tabTitle2',
        },
    };
    const childRoute3 = {
        name: 'Tab 3',
        options: {
            tabTitle: 'tabTitle3',
        },
    };
    const childRoute4 = {
        name: 'Tab 4',
        options: {
            tabOrder: -10,
            tabTitle: 'tabTitle4',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
            childRoute3,
            childRoute4,
        ],
    };

    const router = {
        attributes: {
            id: 1,
        },
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router}>{() => (<Child route={route.children[1]} />)}</ResourceTabs>
    );

    setTimeout(() => {
        resourceTabs.update();
        expect(resourceTabs.find('ResourceTabs Tab')).toHaveLength(4);
        expect(resourceTabs.find('ResourceTabs Tab').at(0).text()).toEqual('Tab Titel 4');
        expect(resourceTabs.find('ResourceTabs Tab').at(1).text()).toEqual('Tab Titel 1');
        expect(resourceTabs.find('ResourceTabs Tab').at(2).text()).toEqual('Tab Titel 3');
        expect(resourceTabs.find('ResourceTabs Tab').at(3).text()).toEqual('Tab Titel 2');
        done();
    });
});

test('Should hide tabs which do not match the tab condition', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'Tab 1',
        options: {
            tabCondition: 'test == 1',
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'Tab 2',
        options: {
            tabCondition: 'test == 2',
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
        redirect: jest.fn(),
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router}>{() => (<Child route={route.children[1]} />)}</ResourceTabs>
    );

    resourceTabs.instance().resourceStore.data = {test: 1};

    setTimeout(() => {
        resourceTabs.update();
        expect(resourceTabs.find('ResourceTabs Tab')).toHaveLength(1);
        expect(resourceTabs.find('ResourceTabs Tab').text()).toEqual('Tab Titel 1');

        resourceTabs.instance().resourceStore.data.test = 2;
        setTimeout(() => {
            resourceTabs.update();
            expect(resourceTabs.find('ResourceTabs Tab')).toHaveLength(1);
            expect(resourceTabs.find('ResourceTabs Tab').text()).toEqual('Tab Titel 2');
            done();
        });
    });
});

test('Should redirect to first child route if no tab is active by default', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
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

    const attributes = {
        id: 1,
    };

    const router = {
        attributes,
        redirect: jest.fn(),
        route,
    };

    const Child = () => (<h1>Child</h1>);
    mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    setTimeout(() => {
        expect(router.redirect).toBeCalledWith('route1', attributes);
        done();
    });
});

test('Should redirect to first visible child route if no tab is active', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabCondition: 'test == 1',
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabCondition: 'test == 2',
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

    const attributes = {
        id: 1,
    };

    const router = {
        attributes,
        redirect: jest.fn(),
        route,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);
    resourceTabs.instance().resourceStore.data = {test: 2};

    setTimeout(() => {
        expect(router.redirect).toBeCalledWith('route2', attributes);
        done();
    });
});

test('Should redirect to first visible child route if invisible tab is active', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabCondition: 'test == 1',
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabCondition: 'test == 2',
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

    const attributes = {
        id: 1,
    };

    const router = {
        attributes,
        redirect: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);
    resourceTabs.instance().resourceStore.data = {test: 2};

    setTimeout(() => {
        expect(router.redirect).toBeCalledWith('route2', attributes);
        done();
    });
});

test('Should redirect to highest prioritized tab if no tab is active', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
            tabPriority: 100,
        },
    };
    const childRoute3 = {
        name: 'route3',
        options: {
            tabTitle: 'tabTitle3',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
            childRoute3,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = {
        attributes,
        redirect: jest.fn(),
        route,
    };

    const Child = () => (<h1>Child</h1>);
    mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    setTimeout(() => {
        expect(router.redirect).toBeCalledWith('route2', attributes);
        done();
    });
});

test('Should not redirect to first child route if resourceStore is not initialized', (done) => {
    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
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

    const attributes = {
        id: 1,
    };

    const router = {
        attributes,
        redirect: jest.fn(),
        route,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);
    resourceTabs.instance().resourceStore.initialized = false;

    setTimeout(() => {
        expect(router.redirect).not.toBeCalledWith('route1', attributes);
        done();
    });
});

test('Should not redirect to first child route if resourceStore is currently loading', (done) => {
    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
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

    const attributes = {
        id: 1,
    };

    const router = {
        attributes,
        redirect: jest.fn(),
        route,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);
    resourceTabs.instance().resourceStore.loading = true;

    setTimeout(() => {
        expect(router.redirect).not.toBeCalledWith('route1', attributes);
        done();
    });
});

test('Should not redirect if a tab is already active', () => {
    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
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

    const attributes = {
        id: 1,
    };

    const router = {
        attributes,
        redirect: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    expect(router.redirect).not.toBeCalled();
});

test('Should navigate and reload ResourceStore to child route if tab is clicked', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

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
        attributes,
        navigate: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    setTimeout(() => {
        resourceTabs.update();
        resourceTabs.find('Tab button').at(1).simulate('click');
        expect(router.navigate).toBeCalledWith('route2', attributes);
        expect(resourceTabs.instance().resourceStore.load).toBeCalledWith();
        done();
    });
});

test('Should navigate to child route if tab is clicked with hidden tabs', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabCondition: 'test == 2',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {},
    };
    const childRoute3 = {
        name: 'route3',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
            childRoute3,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = {
        attributes,
        navigate: jest.fn(),
        redirect: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);
    resourceTabs.instance().resourceStore.data = {test: 1};

    setTimeout(() => {
        resourceTabs.update();
        resourceTabs.find('Tab button').at(0).simulate('click');
        expect(router.navigate).toBeCalledWith('route2', attributes);
        expect(resourceTabs.instance().resourceStore.load).toBeCalledWith();
        done();
    });
});

test('Should create a ResourceStore on mount and destroy it on unmount', () => {
    ResourceStore.mockImplementation(function() {
        this.destroy = jest.fn();
        this.initialized = true;
    });

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

    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).not.toBeDefined();

    resourceTabs.unmount();
    expect(ResourceStore.mock.instances[0].destroy).toBeCalled();
});

test('Should create a ResourceStore with locale on mount if locales have been passed in route options', () => {
    ResourceStore.mockImplementation(function() {
        this.destroy = jest.fn();
        this.initialized = true;
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: ['de', 'en'],
        },
    };
    const router = {
        attributes: {
            id: 5,
        },
        bind: jest.fn(),
        route,
    };

    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).toBeDefined();

    resourceTabs.unmount();
    expect(ResourceStore.mock.instances[0].destroy).toBeCalled();
});

test('Should create a ResourceStore with locale on mount if locales have been passed as observable array', () => {
    ResourceStore.mockImplementation(function() {
        this.destroy = jest.fn();
        this.initialized = true;
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: observable(['de', 'en']),
        },
    };
    const router = {
        attributes: {
            id: 5,
        },
        bind: jest.fn(),
        route,
    };

    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(router.bind).toBeCalled();
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).toBeDefined();

    resourceTabs.unmount();
    expect(ResourceStore.mock.instances[0].destroy).toBeCalled();
});

test('Should pass the ResourceStore and locales to child components', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
    });

    const locales = observable(['de', 'en']);
    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales,
        },
    };
    const router = {
        attributes: {
            id: 5,
        },
        bind: jest.fn(),
        route,
    };

    const ChildComponent = jest.fn(() => null);
    const resourceTabs = mount(
        <ResourceTabs
            locales={[]}
            route={route}
            router={router}
        >
            {(props) => (<ChildComponent {...props} />)}
        </ResourceTabs>
    ).instance();

    expect(ChildComponent.mock.calls[0][0].resourceStore).toBe(resourceTabs.resourceStore);
    expect(ChildComponent.mock.calls[0][0].locales).toBe(locales);
});

test('Should pass locales from route options instead of props to child components', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: ['de', 'en'],
        },
    };
    const router = {
        attributes: {
            id: 5,
        },
        bind: jest.fn(),
        route,
    };

    const ChildComponent = jest.fn(() => null);
    const resourceTabs = mount(
        <ResourceTabs locales={['fr', 'nl']} route={route} router={router}>
            {(props) => (<ChildComponent {...props} />)}
        </ResourceTabs>
    ).instance();

    expect(ChildComponent.mock.calls[0][0].resourceStore).toBe(resourceTabs.resourceStore);
    expect(ChildComponent.mock.calls[0][0].locales).toEqual(['de', 'en']);
});

test('Should throw an error when no resourceKey is defined in the route options', () => {
    const route = {
        options: {},
    };

    const router = {
        route,
        attributes: {
            id: 5,
        },
    };

    expect(() => render(<ResourceTabs route={route} router={router} />)).toThrow(/mandatory "resourceKey" option/);
});
