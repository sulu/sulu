// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import toolbarStorePool, {DEFAULT_STORE_KEY} from '../stores/toolbarStorePool';
import withToolbar from '../withToolbar';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../stores/toolbarStorePool', () => ({
    setToolbarConfig: jest.fn(),
}));

function createRouter() {
    return {
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
        attributes: {},
        route: {
            name: 'route1',
        },
    };
}

test('Pass props to rendered component', () => {
    const Component = class Component extends React.Component<*> {
        render() {
            return <h1>{this.props.title}</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({}));

    const {asFragment} = render(<ComponentWithToolbar router={createRouter()} title="Test" />);
    expect(asFragment()).toMatchSnapshot();
});

test('Bind toolbar method to component instance', () => {
    const storeKey = 'testKey';
    const clickSpy = jest.fn();

    const Component = class Component extends React.Component<*> {
        test = true;

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, function() {
        return {
            items: [
                {
                    disabled: this.test,
                    icon: 'su-save',
                    label: 'Save',
                    onClick: clickSpy,
                    type: 'button',
                },
            ],
        };
    }, storeKey);

    render(<ComponentWithToolbar router={createRouter()} />);

    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(storeKey, {
        items: [
            {
                label: 'Save',
                icon: 'su-save',
                disabled: true,
                onClick: clickSpy,
                type: 'button',
            },
        ],
    });
});

test('Call life-cycle events of rendered component', () => {
    const renderSpy = jest.fn();
    const unmountSpy = jest.fn();

    const Component = class Component extends React.Component<*> {
        componentWillUnmount() {
            unmountSpy();
        }

        render() {
            renderSpy();
            return null;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({}));

    const updateRouteHookDisposer = jest.fn();
    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(updateRouteHookDisposer),
        attributes: {},
        route: {
            name: 'route1',
        },
    };

    const {unmount} = render(<ComponentWithToolbar router={router} />);
    expect(renderSpy).toBeCalled();

    unmount();
    expect(unmountSpy).toBeCalled();
});

test('Reset config of toolbarStore when component is unmounted', () => {
    const Component = class Component extends React.Component<*> {
        render() {
            return null;
        }
    };

    const config = {
        items: [],
    };
    const ComponentWithToolbar = withToolbar(Component, () => config, 'default');

    const updateRouteHookDisposer = jest.fn();
    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(updateRouteHookDisposer),
        attributes: {},
        route: {
            name: 'route1',
        },
    };

    const {unmount} = render(<ComponentWithToolbar router={router} />);
    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith('default', config);

    unmount();
    expect(updateRouteHookDisposer).toBeCalledWith();
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {});
});

test('Dispose toolbar when a new view is rendered', () => {
    const Component = class Component extends React.Component<*> {
        render() {
            return null;
        }
    };

    const items = observable.array([]);
    const ComponentWithToolbar = withToolbar(Component, () => ({items: items.toJS()}), 'default');

    const router = createRouter();

    render(<ComponentWithToolbar router={router} />);
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: []});

    items.push({});
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: [{}]});

    getLatestMockProps(router.addUpdateRouteHook)({name: 'route2'}, {});
    items.push({});
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: [{}]});
});

test('Recall toolbar-function when changing observable', () => {
    const testState = observable.box(true);

    const Component = class Component extends React.Component<*> {
        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, function() {
        return {disableAll: testState.get()};
    });

    render(<ComponentWithToolbar router={createRouter()} />);

    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(DEFAULT_STORE_KEY, {
        disableAll: true,
    });

    testState.set(false);
    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(DEFAULT_STORE_KEY, {
        disableAll: false,
    });
});
