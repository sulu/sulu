// @flow
import React from 'react';
import {extendObservable, observable} from 'mobx';
import {mount, render} from 'enzyme';
import toolbarStorePool, {DEFAULT_STORE_KEY} from '../stores/ToolbarStorePool';
import withToolbar from '../withToolbar';

jest.mock('../stores/ToolbarStorePool', () => ({
    setToolbarConfig: jest.fn(),
}));

test('Pass props to rendered component', () => {
    const Component = class Component extends React.Component<*> {
        render() {
            return <h1>{this.props.title}</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({}));

    expect(render(<ComponentWithToolbar title="Test" />)).toMatchSnapshot();
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

    const router = {
        addUpdateRouteHook: jest.fn(),
    };

    mount(<ComponentWithToolbar router={router} />);
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
    const Component = class Component extends React.Component<*> {
        componentWillUnmount = jest.fn();
        render = jest.fn();
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({}));

    const updateRouteHookDisposer = jest.fn();
    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(updateRouteHookDisposer),
    };

    const component = mount(<ComponentWithToolbar router={router} />);
    expect(component.instance().render).toBeCalled();

    const componentWillUnmount = component.instance().componentWillUnmount;
    component.unmount();
    expect(componentWillUnmount).toBeCalled();
});

test('Reset config of toolbarStore when component is unmounted', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn();
    };

    const config = {
        items: [],
    };
    const ComponentWithToolbar = withToolbar(Component, () => config, 'default');

    const updateRouteHookDisposer = jest.fn();
    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(updateRouteHookDisposer),
    };

    const component = mount(<ComponentWithToolbar router={router} />);
    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith('default', config);

    component.unmount();
    expect(updateRouteHookDisposer).toBeCalled();
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {});
});

test('Dispose toolbar when a new view is rendered', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn();
    };

    const config = {};
    extendObservable(config, {items: []});
    const ComponentWithToolbar = withToolbar(Component, () => ({items: config.items.toJS()}), 'default');

    const router = {
        addUpdateRouteHook: jest.fn(),
        route: {
            name: 'route1',
        },
    };

    mount(<ComponentWithToolbar router={router} />);
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: []});

    config.items.push({});
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: [{}]});

    router.addUpdateRouteHook.mock.calls[0][0]();
    config.items.push({});
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: [{}]});
});

test('Recall toolbar-function when changing observable', () => {
    const Component = class Component extends React.Component<*> {
        @observable test = true;

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, function() {
        return {disableAll: this.test};
    });

    const router = {
        addUpdateRouteHook: jest.fn(),
    };

    const component = mount(<ComponentWithToolbar router={router} />);

    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(DEFAULT_STORE_KEY, {
        disableAll: true,
    });

    component.instance().test = false;
    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(DEFAULT_STORE_KEY, {
        disableAll: false,
    });
});
