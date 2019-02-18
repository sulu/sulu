// @flow
import React from 'react';
import listAdapterRegistry from '../../registries/ListAdapterRegistry';
import AbstractAdapter from '../../adapters/AbstractAdapter';

beforeEach(() => {
    listAdapterRegistry.clear();
});

class LoadingStrategy {
    destroy = jest.fn();
    initialize = jest.fn();
    load = jest.fn();
    reset = jest.fn();
    setStructureStrategy = jest.fn();
}

class StructureStrategy {
    data: Array<Object>;
    visibleItems: Array<Object>;

    addItem = jest.fn();
    clear = jest.fn();
    findById = jest.fn();
    order = jest.fn();
    remove = jest.fn();
}

class TestAdapter extends AbstractAdapter {
    static LoadingStrategy = LoadingStrategy;

    static StructureStrategy = StructureStrategy;

    static icon = 'su-view';

    render() {
        return (
            <div>Test Adapter</div>
        );
    }
}

class TestAdapter2 extends AbstractAdapter {
    static LoadingStrategy = LoadingStrategy;

    static StructureStrategy = StructureStrategy;

    static icon = 'su-view2';

    render() {
        return (
            <div>Test Adapter 2</div>
        );
    }
}

test('Clear all adapters', () => {
    listAdapterRegistry.add('test1', TestAdapter);
    expect(Object.keys(listAdapterRegistry.adapters)).toHaveLength(1);

    listAdapterRegistry.clear();
    expect(Object.keys(listAdapterRegistry.adapters)).toHaveLength(0);
});

test('Add adapter', () => {
    listAdapterRegistry.add('test1', TestAdapter);
    listAdapterRegistry.add('test2', TestAdapter2);

    expect(listAdapterRegistry.get('test1')).toBe(TestAdapter);
    expect(listAdapterRegistry.get('test2')).toBe(TestAdapter2);
});

test('Add adapter with options', () => {
    listAdapterRegistry.add('test1', TestAdapter, {option1: 'value1'});
    listAdapterRegistry.add('test2', TestAdapter2, {option2: 'value2'});

    expect(listAdapterRegistry.get('test1')).toEqual(TestAdapter);
    expect(listAdapterRegistry.getOptions('test1')).toEqual({option1: 'value1'});
    expect(listAdapterRegistry.get('test2')).toEqual(TestAdapter2);
    expect(listAdapterRegistry.getOptions('test2')).toEqual({option2: 'value2'});
});

test('Add adapter with existing key should throw', () => {
    listAdapterRegistry.add('test1', TestAdapter);
    expect(() => listAdapterRegistry.add('test1', TestAdapter)).toThrow(/test1/);
});

test('Get adapter of not existing key', () => {
    expect(() => listAdapterRegistry.get('XXX')).toThrow();
});

test('Has a adapter with an existing key', () => {
    listAdapterRegistry.add('test1', TestAdapter);
    expect(listAdapterRegistry.has('test1')).toEqual(true);
});

test('Has a adapter with not existing key', () => {
    expect(listAdapterRegistry.has('test')).toEqual(false);
});
