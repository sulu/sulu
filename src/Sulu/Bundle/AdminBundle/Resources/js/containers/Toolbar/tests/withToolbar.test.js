// @flow
import React from 'react';
import {extendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import toolbarStorePool, {DEFAULT_STORE_KEY} from '../stores/toolbarStorePool';
import viewRendererContext from '../../ViewRenderer/viewRendererContext';
import withToolbar from '../withToolbar';

jest.mock('../stores/toolbarStorePool', () => ({
    DEFAULT_STORE_KEY: 'default',
    hasStore: jest.fn().mockReturnValue(true),
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

test('Use the toolbar store key of the view renderer if none is passed', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn();
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({items: []}));

    render(
        <viewRendererContext.Provider value={{sidebarAvailable: false, toolbarStoreKey: 'overlay'}}>
            <ComponentWithToolbar router={createRouter()} />
        </viewRendererContext.Provider>
    );

    expect(toolbarStorePool.setToolbarConfig).toHaveBeenCalledWith('overlay', {items: []});
});

test('Prefer the passed toolbar store key over the one of the view renderer', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn();
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({items: []}), 'passed');

    render(
        <viewRendererContext.Provider value={{sidebarAvailable: false, toolbarStoreKey: 'overlay'}}>
            <ComponentWithToolbar router={createRouter()} />
        </viewRendererContext.Provider>
    );

    expect(toolbarStorePool.setToolbarConfig).toHaveBeenCalledWith('passed', {items: []});
    expect(toolbarStorePool.setToolbarConfig).not.toHaveBeenCalledWith('overlay', expect.anything());
});

test('Use the default toolbar store key if neither the view renderer nor the caller passes one', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn();
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({items: []}));

    render(<ComponentWithToolbar router={createRouter()} />);

    expect(toolbarStorePool.setToolbarConfig).toHaveBeenCalledWith(DEFAULT_STORE_KEY, {items: []});
});

test('Do not reset the toolbar config on unmount if its store was already destroyed', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn();
    };

    const ComponentWithToolbar = withToolbar(Component, () => ({items: []}), 'overlay');

    const {unmount} = render(<ComponentWithToolbar router={createRouter()} />);

    (toolbarStorePool.hasStore: any).mockReturnValueOnce(false);
    unmount();

    expect(toolbarStorePool.setToolbarConfig).not.toHaveBeenCalledWith('overlay', {});
});
