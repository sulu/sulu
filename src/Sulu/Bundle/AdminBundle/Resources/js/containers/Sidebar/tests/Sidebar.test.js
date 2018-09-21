// @flow
import React from 'react';
import {render} from 'enzyme';
import Sidebar from '../Sidebar';
import sidebarStore from '../stores/SidebarStore';
import sidebarRegistry from '../registries/sidebarRegistry';

const component = (props) => (<h1>{props.title}</h1>);

jest.mock('../stores/SidebarStore', () => ({}));

jest.mock('../registries/SidebarRegistry', () => ({
    get: jest.fn(),
    isDisabled: jest.fn(),
}));

test('Render correct sidebar view', () => {
    sidebarStore.view = 'preview';
    sidebarRegistry.get.mockReturnValue(component);
    sidebarRegistry.isDisabled.mockReturnValue(false);

    expect(render(<Sidebar />)).toMatchSnapshot();
});

test('Render correct sidebar view with props', () => {
    sidebarStore.view = 'preview';
    sidebarStore.props = {title: 'Hello world'};
    sidebarRegistry.get.mockReturnValue(component);
    sidebarRegistry.isDisabled.mockReturnValue(false);

    const view = render(<Sidebar />);
    expect(view).toMatchSnapshot();
});

test('Return null if view is not set', () => {
    sidebarStore.view = null;
    sidebarStore.props = {};

    const view = render(<Sidebar />);
    expect(view).toMatchSnapshot();
});

test('Return null if view is disabled', () => {
    sidebarStore.view = 'default';
    sidebarStore.props = {};
    sidebarRegistry.isDisabled.mockReturnValue(true);

    expect(render(<Sidebar />)).toMatchSnapshot();
});
