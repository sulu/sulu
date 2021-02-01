// @flow
import transformBytesToReadableString from '../transformBytesToReadableString';

test('Test example 0', () => {
    expect(transformBytesToReadableString(0)).toBe('0 Byte');
});

test('Test example MB', () => {
    expect(transformBytesToReadableString(12312312)).toBe('12.31 MB');
});

test('Test example KB', () => {
    expect(transformBytesToReadableString(55500)).toBe('55.50 KB');
});

test('Test example Bytes', () => {
    expect(transformBytesToReadableString(521)).toBe('521.00 Bytes');
});
