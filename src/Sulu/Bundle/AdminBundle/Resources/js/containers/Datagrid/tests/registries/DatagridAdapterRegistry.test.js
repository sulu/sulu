// @flow
import React from 'react';
import datagridAdapterRegistry from '../../registries/DatagridAdapterRegistry';
import AbstractAdapter from '../../adapters/AbstractAdapter';

beforeEach(() => {
    datagridAdapterRegistry.clear();
});

class LoadingStrategy {
    load = jest.fn();
    destroy = jest.fn();
    initialize = jest.fn();
    reset = jest.fn();
}

class StructureStrategy {
    data: Array<Object>;
    visibleItems: Array<Object>;

    clear = jest.fn();
    getData = jest.fn();
    findById = jest.fn();
    enhanceItem = jest.fn();
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
    datagridAdapterRegistry.add('test1', TestAdapter);
    expect(Object.keys(datagridAdapterRegistry.adapters)).toHaveLength(1);

    datagridAdapterRegistry.clear();
    expect(Object.keys(datagridAdapterRegistry.adapters)).toHaveLength(0);
});

test('Add adapter', () => {
    datagridAdapterRegistry.add('test1', TestAdapter);
    datagridAdapterRegistry.add('test2', TestAdapter2);

    expect(datagridAdapterRegistry.get('test1')).toBe(TestAdapter);
    expect(datagridAdapterRegistry.get('test2')).toBe(TestAdapter2);
});

test('Add adapter with existing key should throw', () => {
    datagridAdapterRegistry.add('test1', TestAdapter);
    expect(() => datagridAdapterRegistry.add('test1', TestAdapter)).toThrow(/test1/);
});

test('Get adapter of not existing key', () => {
    expect(() => datagridAdapterRegistry.get('XXX')).toThrow();
});

test('Has a adapter with an existing key', () => {
    datagridAdapterRegistry.add('test1', TestAdapter);
    expect(datagridAdapterRegistry.has('test1')).toEqual(true);
});

test('Has a adapter with not existing key', () => {
    expect(datagridAdapterRegistry.has('test')).toEqual(false);
});
