// @flow
import listFieldFilterTypeRegistry from '../../registries/listFieldFilterTypeRegistry';
import AbstractFieldFilterType from '../../fieldFilterTypes/AbstractFieldFilterType';

beforeEach(() => {
    listFieldFilterTypeRegistry.clear();
});

test('Clear all filter types', () => {
    const Test1 = class Test1 extends AbstractFieldFilterType<*> {};
    listFieldFilterTypeRegistry.add('test1', Test1);
    expect(Object.keys(listFieldFilterTypeRegistry.fieldFilterTypes)).toHaveLength(1);

    listFieldFilterTypeRegistry.clear();
    expect(Object.keys(listFieldFilterTypeRegistry.fieldFilterTypes)).toHaveLength(0);
});

test('Add filter type', () => {
    const Test1 = class Test1 extends AbstractFieldFilterType<*> {};
    const Test2 = class Test1 extends AbstractFieldFilterType<*> {};
    listFieldFilterTypeRegistry.add('test1', Test1);
    listFieldFilterTypeRegistry.add('test2', Test2);

    expect(listFieldFilterTypeRegistry.get('test1')).toBe(Test1);
    expect(listFieldFilterTypeRegistry.get('test2')).toBe(Test2);
});

test('Add filter type with existing key should throw', () => {
    const Test1 = class Test1 extends AbstractFieldFilterType<*> {};
    listFieldFilterTypeRegistry.add('test1', Test1);
    expect(() => listFieldFilterTypeRegistry.add('test1', Test1)).toThrow(/test1/);
});

test('Get filter type of not existing key', () => {
    expect(() => listFieldFilterTypeRegistry.get('XXX')).toThrow();
});

test('Has a filter type with an existing key', () => {
    const Test1 = class Test1 extends AbstractFieldFilterType<*> {};
    listFieldFilterTypeRegistry.add('test1', Test1);
    expect(listFieldFilterTypeRegistry.has('test1')).toEqual(true);
});

test('Has a filter type with not existing key', () => {
    expect(listFieldFilterTypeRegistry.has('test')).toEqual(false);
});
