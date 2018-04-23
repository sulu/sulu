// @flow
import StringFieldTransformer from '../../fieldTransformers/StringFieldTransformer';

const stringFieldTransformer = new StringFieldTransformer();

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

test('Test undefined', () => {
    expect(stringFieldTransformer.transform(undefined)).toBe(undefined);
});

test('Test string', () => {
    expect(stringFieldTransformer.transform('Test1')).toBe('Test1');
});

test('Test number', () => {
    expect(stringFieldTransformer.transform(5)).toBe(5);
});
