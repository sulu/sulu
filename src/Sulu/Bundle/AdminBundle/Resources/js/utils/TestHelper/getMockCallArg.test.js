// @flow
import findMockCallArg from './findMockCallArg';
import getLatestMockProps from './getLatestMockProps';
import getMockCallArg from './getMockCallArg';

test('getMockCallArg returns argument from latest call by default', () => {
    const mockFn = jest.fn();
    mockFn('first');
    mockFn('latest');

    expect(getMockCallArg(mockFn)).toEqual('latest');
});

test('getMockCallArg returns argument from selected call and argument index', () => {
    const mockFn = jest.fn();
    mockFn('first', 1);
    mockFn('second', 2);

    expect(getMockCallArg(mockFn, 0, 1)).toEqual(1);
    expect(getMockCallArg(mockFn, 1, 0)).toEqual('second');
    expect(getMockCallArg(mockFn, -2, 0)).toEqual('first');
});

test('getLatestMockProps returns argument from latest call', () => {
    const mockFn = jest.fn();
    mockFn('first', 1);
    mockFn('latest', 2);

    expect(getLatestMockProps(mockFn)).toEqual('latest');
    expect(getLatestMockProps(mockFn, 1)).toEqual(2);
});

test('findMockCallArg returns argument from latest call matching predicate', () => {
    const mockFn = jest.fn();
    mockFn({name: 'first'}, 'a');
    mockFn({name: 'latest'}, 'b');

    const props = findMockCallArg(mockFn, ([firstArg]) => firstArg.name === 'latest');
    const secondArg = findMockCallArg(mockFn, ([firstArg]) => firstArg.name === 'latest', 1);

    expect(props).toEqual({name: 'latest'});
    expect(secondArg).toEqual('b');
});

test('getMockCallArg throws for invalid mock function', () => {
    expect(() => getMockCallArg(undefined)).toThrow('Expected a jest mock function');
});

test('getMockCallArg throws for missing calls', () => {
    const mockFn = jest.fn();

    expect(() => getMockCallArg(mockFn)).toThrow('Expected mock to have been called at least once');
});

test('getMockCallArg throws for invalid call index', () => {
    const mockFn = jest.fn();
    mockFn('first');

    expect(() => getMockCallArg(mockFn, 1)).toThrow('Call index "1" does not exist');
});

test('getMockCallArg throws for invalid argument index', () => {
    const mockFn = jest.fn();
    mockFn('first');

    expect(() => getMockCallArg(mockFn, 0, 1)).toThrow('Argument index "1" does not exist on selected mock call');
});

test('findMockCallArg throws if no call matches predicate', () => {
    const mockFn = jest.fn();
    mockFn('first');

    expect(() => findMockCallArg(mockFn, () => false)).toThrow('No mock call matched the predicate');
});
