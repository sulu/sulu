// @flow
import React from 'react';
import sidebarViewRegistry from '../../registries/SidebarViewRegistry';

class Component extends React.Component<*> {
    render() {
        return <h1>{this.props.title}</h1>;
    }
}

beforeEach(() => {
    sidebarViewRegistry.clear();
});

test('Find out if the sidebar-view-registry has a named view', () => {
    expect(sidebarViewRegistry.has('test')).toEqual(false);

    sidebarViewRegistry.add('test', Component);
    expect(sidebarViewRegistry.has('test')).toEqual(true);
});

test('Get named view from sidebar-view-registry', () => {
    sidebarViewRegistry.add('test', Component);
    expect(sidebarViewRegistry.get('test')).toEqual(Component);
});
