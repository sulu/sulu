// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, render} from 'enzyme';
import sidebarStore from '../stores/SidebarStore';
import withSidebar from '../withSidebar';

jest.mock('../stores/SidebarStore', () => ({
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

    expect(render(<ComponentWithSidebar title="Test" />)).toMatchSnapshot();
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

    mount(<ComponentWithSidebar />);
    expect(sidebarStore.setConfig).toBeCalledWith({
        view: 'preview',
    });
});

test('Call life-cycle events of rendered component', () => {
    const Component = class Component extends React.Component<*> {
        componentWillUnmount = jest.fn();
        render = jest.fn();
    };

    const ComponentWithSidebar = withSidebar(Component, () => {
        return null;
    });

    const component = mount(<ComponentWithSidebar />);
    expect(component.instance().render).toBeCalled();

    const componentWillUnmount = component.instance().componentWillUnmount;
    component.unmount();
    expect(componentWillUnmount).toBeCalled();
});

test('Recall sidebar-function when changing observable', () => {
    const Component = class Component extends React.Component<*> {
        @observable sidebarView = 'preview';

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithSidebar = withSidebar(Component, function() {
        return {view: this.sidebarView};
    });

    const component = mount(<ComponentWithSidebar />);

    expect(sidebarStore.setConfig).toBeCalledWith({
        view: 'preview',
    });

    component.instance().sidebarView = 'test';
    expect(sidebarStore.setConfig).toBeCalledWith({
        view: 'test',
    });
});

test('Throw error when component has property sidebarDisposer', () => {
    // catch error logging as described in https://github.com/facebook/react/issues/11098#issuecomment-335290556
    // until better solution is availble
    jest.spyOn(console, 'error').mockImplementation(() => {});

    const Component = class Component extends React.Component<*> {
        sidebarDisposer = true;

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithSidebar = withSidebar(Component, function() {
        return null;
    });

    expect(() => mount(<ComponentWithSidebar />))
        .toThrowError('Component passed to withSidebar cannot declare a property called "sidebarDisposer".');
});
