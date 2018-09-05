// @flow
import buildQueryString from '../buildQueryString';

test('Should return empty string if all values are undefined', () => {
    expect(buildQueryString({value1: undefined, value2: undefined})).toEqual('');
});

test('Should return empty string if nothing is given', () => {
    expect(buildQueryString()).toEqual('');
});

test('Should omit undefined parameters', () => {
    expect(buildQueryString({value1: 'value1', value2: undefined, value3: 'value3'}))
        .toEqual('?value1=value1&value3=value3');
});
