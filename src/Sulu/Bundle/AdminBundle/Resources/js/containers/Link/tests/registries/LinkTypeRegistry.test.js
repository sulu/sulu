// @flow
import React from 'react';
import linkTypeRegistry from '../../registries/linkTypeRegistry';

beforeEach(() => {
    linkTypeRegistry.clear();
});

test('Clear all information from linkTypeRegistry', () => {
    const Component = () => (<div />);

    linkTypeRegistry.add('test1', Component, 'Test1');
    expect(Object.keys(linkTypeRegistry.titles)).toHaveLength(1);
    expect(Object.keys(linkTypeRegistry.overlays)).toHaveLength(1);
    expect(Object.keys(linkTypeRegistry.options)).toHaveLength(1);

    linkTypeRegistry.clear();
    expect(Object.keys(linkTypeRegistry.titles)).toHaveLength(0);
    expect(Object.keys(linkTypeRegistry.overlays)).toHaveLength(0);
    expect(Object.keys(linkTypeRegistry.options)).toHaveLength(0);
});

test('Add internal link type to LinkTypeRegistry', () => {
    const Component = () => (<div />);

    const options = {
        displayProperties: ['title'],
        emptyText: 'empty',
        icon: 'icon',
        listAdapter: 'listAdapter',
        overlayTitle: 'overlayTitle',
        resourceKey: 'resourceKey',
        targets: ['_blank', '_self', '_parent', '_top'],
    };

    linkTypeRegistry.add('test1', Component, 'Test1', options);
    linkTypeRegistry.add('test2', Component, 'Test2');

    expect(linkTypeRegistry.getTitle('test1')).toBe('Test1');
    expect(linkTypeRegistry.getOverlay('test1')).toBe(Component);
    expect(linkTypeRegistry.getOptions('test1')).toBe(options);
    expect(linkTypeRegistry.getTitle('test2')).toBe('Test2');
    expect(linkTypeRegistry.getOverlay('test2')).toBe(Component);
    expect(linkTypeRegistry.getOptions('test2')).toBe(undefined);
});

test('Add internal link type with existing key should throw', () => {
    const Component = () => (<div />);

    linkTypeRegistry.add('test1', Component, 'Test1');
    expect(() => linkTypeRegistry.add('test1', Component, 'test1 react component')).toThrow(/test1/);
});

test('Get internal link title of not existing key', () => {
    expect(() => linkTypeRegistry.getTitle('XXX')).toThrow();
});

test('Get internal link overlay of not existing key', () => {
    expect(() => linkTypeRegistry.getOverlay('XXX')).toThrow();
});

test('Get internal link options of not existing key', () => {
    expect(() => linkTypeRegistry.getOptions('XXX')).toThrow();
});
