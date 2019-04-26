// @flow
import teaserProviderRegistry from '../../registries/TeaserProviderRegistry';

const defaultTeaserProviderOptions = {
    displayProperties: [],
    listAdapter: '',
    overlayTitle: '',
    resourceKey: '',
    title: '',
};

beforeEach(() => {
    teaserProviderRegistry.clear();
});

test('Clear all teaserProviders', () => {
    const teaserProviderOptions = {...defaultTeaserProviderOptions};
    teaserProviderRegistry.add('test1', teaserProviderOptions);
    expect(Object.keys(teaserProviderRegistry.teaserProviders)).toHaveLength(1);

    teaserProviderRegistry.clear();
    expect(Object.keys(teaserProviderRegistry.teaserProviders)).toHaveLength(0);
});

test('Add teaserProvider', () => {
    const teaserProviderOptions1 = {...defaultTeaserProviderOptions};
    const teaserProviderOptions2 = {...defaultTeaserProviderOptions};
    teaserProviderRegistry.add('test1', teaserProviderOptions1);
    teaserProviderRegistry.add('test2', teaserProviderOptions2);

    expect(teaserProviderRegistry.get('test1')).toBe(teaserProviderOptions1);
    expect(teaserProviderRegistry.get('test2')).toBe(teaserProviderOptions2);
});

test('Add teaserProvider with existing key should throw', () => {
    const teaserProviderOptions = {...defaultTeaserProviderOptions};
    teaserProviderRegistry.add('test1', teaserProviderOptions);
    expect(() => teaserProviderRegistry.add('test1', teaserProviderOptions)).toThrow(/test1/);
});

test('Get teaserProvider with existing key', () => {
    const teaserProviderOptions = {...defaultTeaserProviderOptions};
    teaserProviderRegistry.add('test1', teaserProviderOptions);
    expect(teaserProviderRegistry.get('test1')).toBe(teaserProviderOptions);
});

test('Get teaserProvider of not existing key', () => {
    expect(() => teaserProviderRegistry.get('XXX')).toThrow();
});

test('Get existing keys in registry', () => {
    const teaserProviderOptions1 = {...defaultTeaserProviderOptions};
    const teaserProviderOptions2 = {...defaultTeaserProviderOptions};
    teaserProviderRegistry.add('test1', teaserProviderOptions1);
    teaserProviderRegistry.add('test2', teaserProviderOptions2);

    expect(teaserProviderRegistry.keys).toEqual(['test1', 'test2']);
});
