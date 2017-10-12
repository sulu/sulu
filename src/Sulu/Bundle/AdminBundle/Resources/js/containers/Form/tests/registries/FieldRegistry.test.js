/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import fieldRegistry from '../../registries/FieldRegistry';

beforeEach(() => {
    fieldRegistry.clear();
});

test('Clear all fields from FieldRegistry', () => {
    const component1 = () => (<h1>Test1</h1>);
    fieldRegistry.add('test1', component1);
    expect(Object.keys(fieldRegistry.fields)).toHaveLength(1);

    fieldRegistry.clear();
    expect(Object.keys(fieldRegistry.fields)).toHaveLength(0);
});

test('Add field to FieldRegistry', () => {
    const component1 = () => (<h1>Test1</h1>);
    const component2 = () => (<h1>Test2</h1>);
    fieldRegistry.add('test1', component1);
    fieldRegistry.add('test2', component2);

    expect(fieldRegistry.get('test1')).toBe(component1);
    expect(fieldRegistry.get('test2')).toBe(component2);
});

test('Add field with existing key should throw', () => {
    const component1 = () => (<h1>Test1</h1>);
    fieldRegistry.add('test1', component1);
    expect(() => fieldRegistry.add('test1', 'test1 react component')).toThrow(/test1/);
});
