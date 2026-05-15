// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
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
    sidebarStore.props = {};
    sidebarRegistry.get.mockReturnValue(component);
    sidebarRegistry.isDisabled.mockReturnValue(false);

    expect(render(<Sidebar />).container).not.toBeEmptyDOMElement();
    expect(screen.getByRole('heading', {level: 1})).toBeInTheDocument();
});

test('Render correct sidebar view with props', () => {
    sidebarStore.view = 'preview';
    sidebarStore.props = {title: 'Hello world'};
    sidebarRegistry.get.mockReturnValue(component);
    sidebarRegistry.isDisabled.mockReturnValue(false);

    render(<Sidebar />);
    expect(screen.getByRole('heading', {level: 1})).toHaveTextContent('Hello world');
});

test('Return null if view is not set', () => {
    sidebarStore.view = null;
    sidebarStore.props = {};

    expect(render(<Sidebar />).container).toBeEmptyDOMElement();
});

test('Return null if view is disabled', () => {
    sidebarStore.view = 'default';
    sidebarStore.props = {};
    sidebarRegistry.isDisabled.mockReturnValue(true);

    expect(render(<Sidebar />).container).toBeEmptyDOMElement();
});
