// @flow
import React from 'react';
import {extendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import sidebarStore from '../stores/sidebarStore';
import withSidebar from '../withSidebar';

jest.mock('../stores/sidebarStore', () => ({
    setConfig: jest.fn(),
    clearConfig: jest.fn(),
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

    const ComponentWithSidebar = withSidebar(Component, () => {
        return null;
    });

    const {asFragment} = render(<ComponentWithSidebar router={createRouter()} title="Test" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Bind sidebar method to component instance', () => {
    const Component = class Component extends React.Component<*> {
        sidebarView = 'preview';

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithSidebar = withSidebar(Component, function() {
        return {
            view: this.sidebarView,
        };
    });

    const router = createRouter();

    render(<ComponentWithSidebar router={router} />);
    expect(sidebarStore.setConfig).toHaveBeenCalledWith({
        view: 'preview',
    });
});

test('Call life-cycle events of rendered component', () => {
    const componentWillUnmountSpy = jest.fn();
    const Component = class Component extends React.Component<*> {
        componentWillUnmount = componentWillUnmountSpy;

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithSidebar = withSidebar(Component, () => {
        return null;
    });

    const router = createRouter();

    const {unmount} = render(<ComponentWithSidebar router={router} />);
    expect(screen.getByText('Test')).toBeInTheDocument();

    unmount();
    expect(componentWillUnmountSpy).toHaveBeenCalled();
});

test('Reset config of toolbarStore when component is unmounted', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn();
    };

    const ComponentWithToolbar = withSidebar(Component, () => ({view: 'test1'}));

    const updateRouteHookDisposer = jest.fn();
    const router = createRouter(updateRouteHookDisposer);

    const {unmount} = render(<ComponentWithToolbar router={router} />);
    expect(sidebarStore.setConfig).toHaveBeenCalledWith({view: 'test1'});

    unmount();
    expect(updateRouteHookDisposer).toHaveBeenCalledWith();
    expect(sidebarStore.clearConfig).toHaveBeenCalledWith();
});

test('Dispose toolbar when a new view is rendered', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn();
    };

    const config = {};
    extendObservable(config, {view: 'test1'});
    const ComponentWithSidebar = withSidebar(Component, () => ({view: config.view}));

    const router = createRouter();

    render(<ComponentWithSidebar router={router} />);
    expect(sidebarStore.setConfig).toHaveBeenLastCalledWith({view: 'test1'});

    config.view = 'test2';
    expect(sidebarStore.setConfig).toHaveBeenLastCalledWith({view: 'test2'});

    router.addUpdateRouteHook.mock.calls[0][0]();
    config.view = 'test3';
    expect(sidebarStore.setConfig).toHaveBeenLastCalledWith({view: 'test2'});
});

test('Recall sidebar-function when changing observable', () => {
    let componentInstance: any = null;
    const Component = class Component extends React.Component<*> {
        @observable sidebarView = 'preview';

        componentDidMount() {
            componentInstance = this;
        }

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithSidebar = withSidebar(Component, function() {
        return {view: this.sidebarView};
    });

    const router = createRouter();

    render(<ComponentWithSidebar router={router} />);

    expect(sidebarStore.setConfig).toHaveBeenCalledWith({
        view: 'preview',
    });

    componentInstance.sidebarView = 'test';
    expect(sidebarStore.setConfig).toHaveBeenCalledWith({
        view: 'test',
    });
});
