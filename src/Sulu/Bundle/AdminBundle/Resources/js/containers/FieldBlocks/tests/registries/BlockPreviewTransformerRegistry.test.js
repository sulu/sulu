// @flow
import blockPreviewTransformerRegistry from '../../registries/BlockPreviewTransformerRegistry';

beforeEach(() => {
    blockPreviewTransformerRegistry.clear();
});

test('Clear all transformers', () => {
    const Test1 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    blockPreviewTransformerRegistry.add('test1', new Test1());
    expect(Object.keys(blockPreviewTransformerRegistry.blockPreviewTransformers)).toHaveLength(1);

    blockPreviewTransformerRegistry.clear();
    expect(Object.keys(blockPreviewTransformerRegistry.blockPreviewTransformers)).toHaveLength(0);
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
    blockPreviewTransformerRegistry.add('test1', new Test1());
    blockPreviewTransformerRegistry.add('test2', new Test2());

    expect(blockPreviewTransformerRegistry.get('test1')).toBeInstanceOf(Test1);
    expect(blockPreviewTransformerRegistry.get('test2')).toBeInstanceOf(Test2);
});

test('Add transformer with existing key should throw', () => {
    const Test1 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    blockPreviewTransformerRegistry.add('test1', new Test1());
    expect(() => blockPreviewTransformerRegistry.add('test1', new Test1())).toThrow(/test1/);
});

test('Get transformer of not existing key', () => {
    expect(() => blockPreviewTransformerRegistry.get('XXX')).toThrow();
});

test('Has a transformer with an existing key', () => {
    const Test1 = class Test1 {
        transform(value: *): * {
            return value;
        }
    };
    blockPreviewTransformerRegistry.add('test1', new Test1());
    expect(blockPreviewTransformerRegistry.has('test1')).toEqual(true);
});

test('Has a transformer with not existing key', () => {
    expect(blockPreviewTransformerRegistry.has('test')).toEqual(false);
});
