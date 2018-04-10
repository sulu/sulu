// @flow
import datagridFieldTransformerRegistry from '../../registries/DatagridFieldTransformerRegistry';

beforeEach(() => {
    datagridFieldTransformerRegistry.clear();
});

test('Clear all transformers', () => {
    const Test1 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    datagridFieldTransformerRegistry.add('test1', new Test1());
    expect(Object.keys(datagridFieldTransformerRegistry.fieldTransformers)).toHaveLength(1);

    datagridFieldTransformerRegistry.clear();
    expect(Object.keys(datagridFieldTransformerRegistry.fieldTransformers)).toHaveLength(0);
});

test('Add transformer', () => {
    const Test1 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    const Test2 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    datagridFieldTransformerRegistry.add('test1', new Test1());
    datagridFieldTransformerRegistry.add('test2', new Test2());

    expect(datagridFieldTransformerRegistry.get('test1')).toBeInstanceOf(Test1);
    expect(datagridFieldTransformerRegistry.get('test2')).toBeInstanceOf(Test2);
});

test('Add transformer with existing key should throw', () => {
    const Test1 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    datagridFieldTransformerRegistry.add('test1', new Test1());
    expect(() => datagridFieldTransformerRegistry.add('test1', new Test1())).toThrow(/test1/);
});

test('Get transformer of not existing key', () => {
    expect(() => datagridFieldTransformerRegistry.get('XXX')).toThrow();
});

test('Has a transformer with an existing key', () => {
    const Test1 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    datagridFieldTransformerRegistry.add('test1', new Test1());
    expect(datagridFieldTransformerRegistry.has('test1')).toEqual(true);
});

test('Has a transformer with not existing key', () => {
    expect(datagridFieldTransformerRegistry.has('test')).toEqual(false);
});
