// @flow
import React from 'react';
import {render} from 'enzyme';
import Sidebar from '../Sidebar';
import sidebarStore from '../stores/SidebarStore';
import sidebarViewRegistry from '../registries/SidebarViewRegistry';

const component = (props) => (<h1>{props.title}</h1>);

jest.mock('../stores/SidebarStore', () => ({}));

jest.mock('../registries/SidebarViewRegistry', () => ({
    get: jest.fn(),
}));

test('Render correct sidebar view', () => {
    sidebarStore.view = 'preview';
    sidebarViewRegistry.get.mockReturnValue(component);

    expect(render(<Sidebar />)).toMatchSnapshot();
});

test('Render correct sidebar view with props', () => {
    sidebarStore.view = 'preview';
    sidebarStore.props = {title: 'Hello world'};
    sidebarViewRegistry.get.mockReturnValue(component);

    expect(render(<Sidebar />)).toMatchSnapshot();
});
