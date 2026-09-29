// @flow
import React from 'react';
import {extendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import toolbarStorePool, {DEFAULT_STORE_KEY} from '../stores/toolbarStorePool';
import withToolbar from '../withToolbar';

jest.mock('../stores/toolbarStorePool', () => ({
    setToolbarConfig: jest.fn(),
}));

function createRouter(updateRouteHookDisposer: () => void = jest.fn()) {
    return {
        addUpdateRouteHook: jest.fn().mockReturnValue(updateRouteHookDisposer),
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

    const router = createRouter();

    render(<ComponentWithToolbar router={router} />);
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenCalledWith(storeKey, {
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
    const Component = class Component extends React.Component<*> {
        componentWillUnmount = componentWillUnmount;

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({}));

    const updateRouteHookDisposer = jest.fn();
    const router = createRouter(updateRouteHookDisposer);

    const {unmount} = render(<ComponentWithToolbar router={router} />);
    expect(screen.getByText('Test')).toBeInTheDocument();

    unmount();
    expect(componentWillUnmount).toHaveBeenCalled();
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
    const router = createRouter(updateRouteHookDisposer);

    const {unmount} = render(<ComponentWithToolbar router={router} />);
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenCalledWith('default', config);

    unmount();
    expect(updateRouteHookDisposer).toHaveBeenCalledWith();
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {});
});

test('Dispose toolbar when a new view is rendered', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn();
    };

    const config = {};
    extendObservable(config, {items: []});
    const ComponentWithToolbar = withToolbar(Component, () => ({items: config.items.toJS()}), 'default');

    const router = createRouter();

    render(<ComponentWithToolbar router={router} />);
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: []});

    config.items.push({});
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: [{}]});

    router.addUpdateRouteHook.mock.calls[0][0]();
    config.items.push({});
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenLastCalledWith('default', {items: [{}]});
});

test('Recall toolbar-function when changing observable', () => {
    let componentInstance: any = null;
    const Component = class Component extends React.Component<*> {
        @observable test = true;

        componentDidMount() {
            componentInstance = this;
        }

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, function() {
        return {disableAll: this.test};
    });

    const router = createRouter();

    render(<ComponentWithToolbar router={router} />);

    expect(toolbarStorePool.setToolbarConfig).toHaveBeenCalledWith(DEFAULT_STORE_KEY, {
        disableAll: true,
    });

    componentInstance.test = false;
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenCalledWith(DEFAULT_STORE_KEY, {
        disableAll: false,
    });
});
