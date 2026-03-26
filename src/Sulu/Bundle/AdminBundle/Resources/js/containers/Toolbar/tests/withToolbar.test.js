// @flow
import React from 'react';
import {extendObservable, observable} from 'mobx';
import {act, render, screen} from '@testing-library/react';
import toolbarStorePool, {DEFAULT_STORE_KEY} from '../stores/toolbarStorePool';
import withToolbar from '../withToolbar';

jest.mock('../stores/toolbarStorePool', () => ({
    setToolbarConfig: jest.fn(),
}));

jest.mock('loglevel', () => ({
    info: jest.fn(),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass props to rendered component', () => {
    const Component = class Component extends React.Component<*> {
        render() {
            return <h1>{this.props.title}</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({}));

    render(
        <ComponentWithToolbar
            router={{addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn())}}
            title="Test"
        />
    );

    expect(screen.getByText('Test')).toBeInTheDocument();
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
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
    };

    render(<ComponentWithToolbar router={router} />);
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
    const componentWillUnmount = jest.fn();
    const componentRender = jest.fn();

    const Component = class Component extends React.Component<*> {
        componentWillUnmount() {
            componentWillUnmount();
        }

        render() {
            componentRender();
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({}));

    const updateRouteHookDisposer = jest.fn();
    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(updateRouteHookDisposer),
    };

    const {unmount} = render(<ComponentWithToolbar router={router} />);
    expect(componentRender).toBeCalled();

    unmount();
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

    const {unmount} = render(<ComponentWithToolbar router={router} />);
    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith('default', config);

    unmount();
    expect(updateRouteHookDisposer).toBeCalledWith();
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
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
        route: {
            name: 'route1',
        },
    };

    render(<ComponentWithToolbar router={router} />);
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: []});

    act(() => {
        config.items.push({});
    });
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: [{}]});

    act(() => {
        router.addUpdateRouteHook.mock.calls[0][0]();
    });
    act(() => {
        config.items.push({});
    });
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: [{}]});
});

test('Recall toolbar-function when changing observable', () => {
    let componentInstance: any;

    const Component = class Component extends React.Component<*> {
        @observable test = true;

        constructor(props) {
            super(props);
            componentInstance = this;
        }

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, function() {
        return {disableAll: this.test};
    });

    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
    };

    render(<ComponentWithToolbar router={router} />);

    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(DEFAULT_STORE_KEY, {
        disableAll: true,
    });

    act(() => {
        componentInstance.test = false;
    });
    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(DEFAULT_STORE_KEY, {
        disableAll: false,
    });
});
