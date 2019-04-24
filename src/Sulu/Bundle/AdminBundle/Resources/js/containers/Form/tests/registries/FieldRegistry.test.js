// @flow
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

test('Add a field with options to the FieldRegistry', () => {
    const component1 = () => (<h1>Test1</h1>);
    const component2 = () => (<h1>Test2</h1>);
    fieldRegistry.add('test1', component1, {option1: 'value1'});
    fieldRegistry.add('test2', component2, {option2: 'value2'});

    expect(fieldRegistry.get('test1')).toBe(component1);
    expect(fieldRegistry.get('test2')).toBe(component2);
    expect(fieldRegistry.getOptions('test1')).toEqual({option1: 'value1'});
    expect(fieldRegistry.getOptions('test2')).toEqual({option2: 'value2'});
});

test('Add field with existing key should throw', () => {
    const component1 = () => (<h1>Test1</h1>);
    const component2 = () => (<h1>Test2</h1>);
    fieldRegistry.add('test1', component1);
    expect(() => fieldRegistry.add('test1', component2)).toThrow(/test1/);
});

test('Get field with existing key', () => {
    const component1 = () => (<h1>Test1</h1>);
    fieldRegistry.add('test1', component1);
    expect(fieldRegistry.get('test1')).toBe(component1);
});

test('Get field of not existing key', () => {
    expect(() => fieldRegistry.get('XXX')).toThrow();
});

test('Has a field with an existing key', () => {
    fieldRegistry.add('test', () => null);
    expect(fieldRegistry.has('test')).toEqual(true);
});

test('Has a field with an not existing key', () => {
    expect(fieldRegistry.has('test')).toEqual(false);
});
