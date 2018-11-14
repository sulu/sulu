// @flow
import BytesFieldTransformer from '../../fieldTransformers/BytesFieldTransformer';

const bytesFieldTransformer = new BytesFieldTransformer();

test('Test undefined', () => {
    expect(bytesFieldTransformer.transform(undefined)).toBe(null);
});

test('Test example 0', () => {
    expect(bytesFieldTransformer.transform(0)).toBe('0 Byte');
});

test('Test example MB', () => {
    expect(bytesFieldTransformer.transform(12312312)).toBe('12.31 MB');
});

test('Test example KB', () => {
    expect(bytesFieldTransformer.transform(55500)).toBe('55.50 KB');
});

test('Test example Bytes', () => {
    expect(bytesFieldTransformer.transform(521)).toBe('521.00 Bytes');
});
