// @flow
import jexl from 'jexl';
import initializeJexl from '../initializeJexl';

initializeJexl();

test('Test jexl AND binary operator', () => {
    const context = {
        val1: 1,
        val2: 'foo',
    };

    expect(jexl.evalSync('1 == val1 AND "foo" == val2', context)).toBe(true);
    expect(jexl.evalSync('1 == val1 AND "bar" == val2', context)).toBe(false);
    expect(jexl.evalSync('2 == val1 AND "foo" == val2', context)).toBe(false);
    expect(jexl.evalSync('2 == val1 AND "bar" == val2', context)).toBe(false);

    expect(jexl.evalSync('1 == val1 and "foo" == val2', context)).toBe(true);
    expect(jexl.evalSync('1 == val1 and "bar" == val2', context)).toBe(false);
    expect(jexl.evalSync('2 == val1 and "foo" == val2', context)).toBe(false);
    expect(jexl.evalSync('2 == val1 and "bar" == val2', context)).toBe(false);
});

test('Test jexl OR binary operator', () => {
    const context = {
        val1: 1,
        val2: 'foo',
    };

    expect(jexl.evalSync('1 == val1 OR "foo" == val2', context)).toBe(true);
    expect(jexl.evalSync('1 == val1 OR "bar" == val2', context)).toBe(true);
    expect(jexl.evalSync('2 == val1 OR "foo" == val2', context)).toBe(true);
    expect(jexl.evalSync('2 == val1 OR "bar" == val2', context)).toBe(false);

    expect(jexl.evalSync('1 == val1 or "foo" == val2', context)).toBe(true);
    expect(jexl.evalSync('1 == val1 or "bar" == val2', context)).toBe(true);
    expect(jexl.evalSync('2 == val1 or "foo" == val2', context)).toBe(true);
    expect(jexl.evalSync('2 == val1 or "bar" == val2', context)).toBe(false);
});

test('Test jexl length transform', () => {
    const context = {
        stringVal: 'foo',
        arrayVal: ['bar', 'baz'],
    };

    expect(jexl.evalSync('stringVal|length', context)).toBe(3);
    expect(jexl.evalSync('arrayVal|length', context)).toBe(2);
});

test('Test jexl includes transform', () => {
    const context = {
        val: ['foo', 'bar', 'baz'],
    };

    expect(jexl.evalSync('val|includes("bar")', context)).toBe(true);
    expect(jexl.evalSync('val|includes("abc")', context)).toBe(false);
});

test('Test jexl values transform', () => {
    const context = {
        objectVal: {
            foo: 'abc',
            bar: 1,
            baz: true,
        },
        arrayVal: ['abc', 1, true],
    };

    expect(jexl.evalSync('objectVal|values', context)).toEqual(['abc', 1, true]);
    expect(jexl.evalSync('arrayVal|values', context)).toEqual(['abc', 1, true]);
});
