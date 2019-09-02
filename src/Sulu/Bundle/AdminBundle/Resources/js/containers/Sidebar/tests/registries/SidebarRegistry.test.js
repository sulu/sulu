// @flow
import React from 'react';
import sidebarRegistry from '../../registries/sidebarRegistry';

class Component extends React.Component<*> {
    render() {
        return <h1>{this.props.title}</h1>;
    }
}

beforeEach(() => {
    sidebarRegistry.clear();
});

test('Find out if the sidebar-view-registry has a named view', () => {
    expect(sidebarRegistry.has('test')).toEqual(false);

    sidebarRegistry.add('test', Component);
    expect(sidebarRegistry.has('test')).toEqual(true);
});

test('Get named view from sidebar-view-registry', () => {
    sidebarRegistry.add('test', Component);

    expect(sidebarRegistry.get('test')).toEqual(Component);
});

test('Add named view with existing key should throw an error', () => {
    sidebarRegistry.add('test', Component);

    expect(() => sidebarRegistry.add('test', () => <h1>Test</h1>)).toThrow(/test/);
});

test('Get not existing named view', () => {
    expect(() => sidebarRegistry.get('test')).toThrow(/test/);
});

test('Disable view', () => {
    sidebarRegistry.add('test', Component);
    expect(sidebarRegistry.isDisabled('test')).toBe(false);

    sidebarRegistry.disable('test');
    expect(sidebarRegistry.isDisabled('test')).toBe(true);
});
