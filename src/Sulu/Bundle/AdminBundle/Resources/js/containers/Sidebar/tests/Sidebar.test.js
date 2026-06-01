// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Sidebar from '../Sidebar';
import sidebarStore from '../stores/sidebarStore';
import sidebarRegistry from '../registries/sidebarRegistry';

const component = (props) => (<h1>{props.title}</h1>);

jest.mock('../stores/sidebarStore', () => ({}));

jest.mock('../registries/sidebarRegistry', () => ({
    get: jest.fn(),
    isDisabled: jest.fn(),
}));

test('Render correct sidebar view', () => {
    sidebarStore.view = 'preview';
    sidebarRegistry.get.mockReturnValue(component);
    sidebarRegistry.isDisabled.mockReturnValue(false);

    const {container} = render(<Sidebar />);
    expect(container).toMatchSnapshot();
});

test('Render correct sidebar view with props', () => {
    sidebarStore.view = 'preview';
    sidebarStore.props = {title: 'Hello world'};
    sidebarRegistry.get.mockReturnValue(component);
    sidebarRegistry.isDisabled.mockReturnValue(false);

    const {container} = render(<Sidebar />);
    expect(container).toMatchSnapshot();
});

test('Return null if view is not set', () => {
    sidebarStore.view = null;
    sidebarStore.props = {};

    const {container} = render(<Sidebar />);
    expect(container).toMatchSnapshot();
});

test('Return null if view is disabled', () => {
    sidebarStore.view = 'default';
    sidebarStore.props = {};
    sidebarRegistry.isDisabled.mockReturnValue(true);

    const {container} = render(<Sidebar />);
    expect(container).toMatchSnapshot();
});
