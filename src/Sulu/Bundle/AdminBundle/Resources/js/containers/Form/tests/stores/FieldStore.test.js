/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import fieldStore from '../../stores/FieldStore';

beforeEach(() => {
    fieldStore.clear();
});

test('Clear all fields from FieldStore', () => {
    const component1 = () => (<h1>Test1</h1>);
    fieldStore.add('test1', component1);
    expect(Object.keys(fieldStore.fields)).toHaveLength(1);

    fieldStore.clear();
    expect(Object.keys(fieldStore.fields)).toHaveLength(0);
});

test('Add field to FieldStore', () => {
    const component1 = () => (<h1>Test1</h1>);
    const component2 = () => (<h1>Test2</h1>);
    fieldStore.add('test1', component1);
    fieldStore.add('test2', component2);

    expect(fieldStore.get('test1')).toBe(component1);
    expect(fieldStore.get('test2')).toBe(component2);
});

test('Add field with existing key should throw', () => {
    const component1 = () => (<h1>Test1</h1>);
    fieldStore.add('test1', component1);
    expect(() => fieldStore.add('test1', 'test1 react component')).toThrow(/test1/);
});
