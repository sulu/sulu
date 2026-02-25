// @flow
import React from 'react';
import {extendObservable, observable} from 'mobx';
import {render} from '@testing-library/react';
import sidebarStore from '../stores/sidebarStore';
import withSidebar from '../withSidebar';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../stores/sidebarStore', () => ({
    setConfig: jest.fn(),
    clearConfig: jest.fn(),
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

    const ComponentWithSidebar = withSidebar(Component, () => null);
    const router = {addUpdateRouteHook: jest.fn(() => jest.fn())};

    const {asFragment} = render(<ComponentWithSidebar router={router} title="Test" />);
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
    const router = {addUpdateRouteHook: jest.fn(() => jest.fn())};

    render(<ComponentWithSidebar router={router} />);
    expect(sidebarStore.setConfig).toBeCalledWith({view: 'preview'});
});

test('Call life-cycle events of rendered component', () => {
    const renderSpy = jest.fn(() => null);
    const componentWillUnmountSpy = jest.fn();

    const Component = class Component extends React.Component<*> {
        componentWillUnmount = componentWillUnmountSpy;
        render = renderSpy;
    };

    const ComponentWithSidebar = withSidebar(Component, () => null);
    const router = {addUpdateRouteHook: jest.fn(() => jest.fn())};

    const view = render(<ComponentWithSidebar router={router} />);
    expect(renderSpy).toBeCalled();

    view.unmount();
    expect(componentWillUnmountSpy).toBeCalled();
});

test('Reset config of toolbarStore when component is unmounted', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn(() => null);
    };

    const ComponentWithToolbar = withSidebar(Component, () => ({view: 'test1'}));

    const updateRouteHookDisposer = jest.fn();
    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(updateRouteHookDisposer),
    };

    const view = render(<ComponentWithToolbar router={router} />);
    expect(sidebarStore.setConfig).toBeCalledWith({view: 'test1'});

    view.unmount();
    expect(updateRouteHookDisposer).toBeCalledWith();
    expect(sidebarStore.clearConfig).toBeCalledWith();
});

test('Dispose toolbar when a new view is rendered', () => {
    const Component = class Component extends React.Component<*> {
        render = jest.fn(() => null);
    };

    const config = {};
    extendObservable(config, {view: 'test1'});
    const ComponentWithSidebar = withSidebar(Component, () => ({view: config.view}));

    const router = {
        addUpdateRouteHook: jest.fn(() => jest.fn()),
        route: {
            name: 'route1',
        },
    };

    render(<ComponentWithSidebar router={router} />);
    expect(sidebarStore.setConfig).toHaveBeenLastCalledWith({view: 'test1'});

    config.view = 'test2';
    expect(sidebarStore.setConfig).toHaveBeenLastCalledWith({view: 'test2'});

    getLatestMockProps(router.addUpdateRouteHook)();
    config.view = 'test3';
    expect(sidebarStore.setConfig).toHaveBeenLastCalledWith({view: 'test2'});
});

test('Recall sidebar-function when changing observable', () => {
    const sidebarView = observable.box('preview');

    const Component = class Component extends React.Component<*> {
        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithSidebar = withSidebar(Component, () => ({view: sidebarView.get()}));
    const router = {addUpdateRouteHook: jest.fn(() => jest.fn())};

    render(<ComponentWithSidebar router={router} />);

    expect(sidebarStore.setConfig).toBeCalledWith({view: 'preview'});

    sidebarView.set('test');
    expect(sidebarStore.setConfig).toBeCalledWith({view: 'test'});
});
