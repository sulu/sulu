// @flow
import React from 'react';
import internalLinkTypeRegistry from '../../registries/InternalLinkTypeRegistry';

beforeEach(() => {
    internalLinkTypeRegistry.clear();
});

test('Clear all information from InternalLinkTypeRegistry', () => {
    const Component = () => (<div />);

    internalLinkTypeRegistry.add('test1', Component, 'Test1');
    expect(Object.keys(internalLinkTypeRegistry.titles)).toHaveLength(1);
    expect(Object.keys(internalLinkTypeRegistry.overlays)).toHaveLength(1);
    expect(Object.keys(internalLinkTypeRegistry.options)).toHaveLength(1);

    internalLinkTypeRegistry.clear();
    expect(Object.keys(internalLinkTypeRegistry.titles)).toHaveLength(0);
    expect(Object.keys(internalLinkTypeRegistry.overlays)).toHaveLength(0);
    expect(Object.keys(internalLinkTypeRegistry.options)).toHaveLength(0);
});

test('Add internal link type to InternalLinkTypeRegistry', () => {
    const Component = () => (<div />);

    const options = {
        displayProperties: ['title'],
        emptyText: 'empty',
        icon: 'icon',
        listAdapter: 'listAdapter',
        overlayTitle: 'overlayTitle',
        resourceKey:  'resourceKey',
    };

    internalLinkTypeRegistry.add('test1', Component, 'Test1', options);
    internalLinkTypeRegistry.add('test2', Component, 'Test2');

    expect(internalLinkTypeRegistry.getTitle('test1')).toBe('Test1');
    expect(internalLinkTypeRegistry.getOverlay('test1')).toBe(Component);
    expect(internalLinkTypeRegistry.getOptions('test1')).toBe(options);
    expect(internalLinkTypeRegistry.getTitle('test2')).toBe('Test2');
    expect(internalLinkTypeRegistry.getOverlay('test2')).toBe(Component);
    expect(internalLinkTypeRegistry.getOptions('test2')).toBe(undefined);
});

test('Add internal link type with existing key should throw', () => {
    const Component = () => (<div />);

    internalLinkTypeRegistry.add('test1', Component, 'Test1');
    expect(() => internalLinkTypeRegistry.add('test1', Component, 'test1 react component')).toThrow(/test1/);
});

test('Get internal link title of not existing key', () => {
    expect(() => internalLinkTypeRegistry.getTitle('XXX')).toThrow();
});

test('Get internal link overlay of not existing key', () => {
    expect(() => internalLinkTypeRegistry.getOverlay('XXX')).toThrow();
});

test('Get internal link options of not existing key', () => {
    expect(() => internalLinkTypeRegistry.getOptions('XXX')).toThrow();
});
