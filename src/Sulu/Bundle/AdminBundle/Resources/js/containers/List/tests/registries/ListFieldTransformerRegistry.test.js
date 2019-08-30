// @flow
import listFieldTransformerRegistry from '../../registries/listFieldTransformerRegistry';

beforeEach(() => {
    listFieldTransformerRegistry.clear();
});

test('Clear all transformers', () => {
    const Test1 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    listFieldTransformerRegistry.add('test1', new Test1());
    expect(Object.keys(listFieldTransformerRegistry.fieldTransformers)).toHaveLength(1);

    listFieldTransformerRegistry.clear();
    expect(Object.keys(listFieldTransformerRegistry.fieldTransformers)).toHaveLength(0);
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
    listFieldTransformerRegistry.add('test1', new Test1());
    listFieldTransformerRegistry.add('test2', new Test2());

    expect(listFieldTransformerRegistry.get('test1')).toBeInstanceOf(Test1);
    expect(listFieldTransformerRegistry.get('test2')).toBeInstanceOf(Test2);
});

test('Add transformer with existing key should throw', () => {
    const Test1 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    listFieldTransformerRegistry.add('test1', new Test1());
    expect(() => listFieldTransformerRegistry.add('test1', new Test1())).toThrow(/test1/);
});

test('Get transformer of not existing key', () => {
    expect(() => listFieldTransformerRegistry.get('XXX')).toThrow();
});

test('Has a transformer with an existing key', () => {
    const Test1 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    listFieldTransformerRegistry.add('test1', new Test1());
    expect(listFieldTransformerRegistry.has('test1')).toEqual(true);
});

test('Has a transformer with not existing key', () => {
    expect(listFieldTransformerRegistry.has('test')).toEqual(false);
});
