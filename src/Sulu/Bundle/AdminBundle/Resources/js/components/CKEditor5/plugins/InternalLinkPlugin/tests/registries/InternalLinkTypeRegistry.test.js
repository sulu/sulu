// @flow
import internalLinkTypeRegistry from '../../registries/InternalLinkTypeRegistry';

beforeEach(() => {
    internalLinkTypeRegistry.clear();
});

test('Clear all information from InternalLinkTypeRegistry', () => {
    internalLinkTypeRegistry.add('test1', 'Test1');
    expect(Object.keys(internalLinkTypeRegistry.titles)).toHaveLength(1);

    internalLinkTypeRegistry.clear();
    expect(Object.keys(internalLinkTypeRegistry.titles)).toHaveLength(0);
});

test('Add internal link type title to InternalLinkTypeRegistry', () => {
    internalLinkTypeRegistry.add('test1', 'Test1');
    internalLinkTypeRegistry.add('test2', 'Test2');

    expect(internalLinkTypeRegistry.getTitle('test1')).toBe('Test1');
    expect(internalLinkTypeRegistry.getTitle('test2')).toBe('Test2');
});

test('Add internal link type with existing key should throw', () => {
    internalLinkTypeRegistry.add('test1', 'Test1');
    expect(() => internalLinkTypeRegistry.add('test1', 'test1 react component')).toThrow(/test1/);
});

test('Get internal link type title with existing key', () => {
    internalLinkTypeRegistry.add('test1', 'Test1');
    expect(internalLinkTypeRegistry.getTitle('test1')).toBe('Test1');
});

test('Get internal link title of not existing key', () => {
    expect(() => internalLinkTypeRegistry.getTitle('XXX')).toThrow();
});
