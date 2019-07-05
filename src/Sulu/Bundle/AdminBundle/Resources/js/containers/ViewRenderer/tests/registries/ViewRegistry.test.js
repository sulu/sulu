/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import viewRegistry from '../../registries/ViewRegistry';

beforeEach(() => {
    viewRegistry.clear();
});

test('Clear all view from ViewRegistry', () => {
    const component1 = () => (<h1>Test1</h1>);
    viewRegistry.add('test1', component1);
    expect(Object.keys(viewRegistry.views)).toHaveLength(1);

    viewRegistry.clear();
    expect(Object.keys(viewRegistry.views)).toHaveLength(0);
});

test('Add view to ViewRegistry', () => {
    const component1 = () => (<h1>Test1</h1>);
    const component2 = () => (<h1>Test2</h1>);
    viewRegistry.add('test1', component1);
    viewRegistry.add('test2', component2);

    expect(viewRegistry.get('test1')).toBe(component1);
    expect(viewRegistry.get('test2')).toBe(component2);
});

test('Get a view which does not exist should throw', () => {
    expect(() => viewRegistry.get('not_existing')).toThrow(/not_existing/);
});

test('Add view with existing key should throw', () => {
    const component1 = () => (<h1>Test1</h1>);
    viewRegistry.add('test1', component1);
    expect(() => viewRegistry.add('test1', 'test1 react component')).toThrow(/test1/);
});
