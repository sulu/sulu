// @flow
import React from 'react';
import {extendObservable, observable} from 'mobx';
import {render} from '@testing-library/react';
import sidebarStore from '../stores/sidebarStore';
import withSidebar from '../withSidebar';

jest.mock('../stores/sidebarStore', () => ({
    setConfig: jest.fn(),
    clearConfig: jest.fn(),
}));

test('Pass props to rendered component', () => {
    const Component = class Component extends React.Component<*> {
        render() {
            return <h1>{this.props.title}</h1>;
        }
    };

    const ComponentWithSidebar = withSidebar(Component, () => {
        return null;
    });

    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
    };

    const {container} = render(<ComponentWithSidebar router={router} title="Test" />);
    expect(container).toMatchSnapshot();
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

    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
    };

    render(<ComponentWithSidebar router={router} />);
    expect(sidebarStore.setConfig).toBeCalledWith({
        view: 'preview',
    });
});

test('Call life-cycle events of rendered component', () => {
    const renderSpy = jest.fn();
    const componentWillUnmountSpy = jest.fn();

    const Component = class Component extends React.Component<*> {
        componentWillUnmount() {
            componentWillUnmountSpy();
        }

        render() {
            renderSpy();

            return <h1>Test</h1>;
        }
    };

    const ComponentWithSidebar = withSidebar(Component, () => {
        return null;
    });

    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
    };

    const {unmount} = render(<ComponentWithSidebar router={router} />);
    expect(renderSpy).toBeCalled();

    unmount();
    expect(componentWillUnmountSpy).toBeCalled();
});

test('Reset config of toolbarStore when component is unmounted', () => {
    const Component = class Component extends React.Component<*> {
        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withSidebar(Component, () => ({view: 'test1'}));

    const updateRouteHookDisposer = jest.fn();
    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(updateRouteHookDisposer),
    };

    const {unmount} = render(<ComponentWithToolbar router={router} />);
    expect(sidebarStore.setConfig).toBeCalledWith({view: 'test1'});

    unmount();
    expect(updateRouteHookDisposer).toBeCalledWith();
    expect(sidebarStore.clearConfig).toBeCalledWith();
});

test('Dispose toolbar when a new view is rendered', () => {
    const Component = class Component extends React.Component<*> {
        render() {
            return <h1>Test</h1>;
        }
    };

    const config = {};
    extendObservable(config, {view: 'test1'});
    const ComponentWithSidebar = withSidebar(Component, () => ({view: config.view}));

    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
        route: {
            name: 'route1',
        },
    };

    render(<ComponentWithSidebar router={router} />);
    expect(sidebarStore.setConfig).toHaveBeenLastCalledWith({view: 'test1'});

    config.view = 'test2';
    expect(sidebarStore.setConfig).toHaveBeenLastCalledWith({view: 'test2'});

    router.addUpdateRouteHook.mock.calls[0][0]();
    config.view = 'test3';
    expect(sidebarStore.setConfig).toHaveBeenLastCalledWith({view: 'test2'});
});

test('Recall sidebar-function when changing observable', () => {
    const ref = React.createRef();

    const Component = class Component extends React.Component<*> {
        @observable sidebarView = 'preview';

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithSidebar = withSidebar(Component, function() {
        return {view: this.sidebarView};
    });

    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
    };

    render(<ComponentWithSidebar ref={ref} router={router} />);

    expect(sidebarStore.setConfig).toBeCalledWith({
        view: 'preview',
    });

    ref.current.sidebarView = 'test';
    expect(sidebarStore.setConfig).toBeCalledWith({
        view: 'test',
    });
});
