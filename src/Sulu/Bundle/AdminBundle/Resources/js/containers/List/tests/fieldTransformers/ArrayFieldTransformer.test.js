// @flow
import ArrayFieldTransformer from '../../fieldTransformers/ArrayFieldTransformer';

const arrayFieldTransformer = new ArrayFieldTransformer();

test('Test undefined', () => {
    expect(arrayFieldTransformer.transform(undefined)).toBe(null);
});

test('Test empty array', () => {
    expect(arrayFieldTransformer.transform([])).toBe('');
});

test('Test array', () => {
    expect(arrayFieldTransformer.transform(['a', 'b', 'c'])).toBe('a, b, c');
});
