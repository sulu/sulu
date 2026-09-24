// @flow
import pluginRegistry from '../../registries/pluginRegistry';

beforeEach(() => {
    pluginRegistry.clear();
});

test('Add and clear plugins', () => {
    const plugin1 = class {};
    const plugin2 = class {};

    pluginRegistry.add(plugin1);
    pluginRegistry.add(plugin2);
    expect(pluginRegistry.getPlugins([])).toEqual([plugin1, plugin2]);

    pluginRegistry.clear();
    expect(pluginRegistry.getPlugins([])).toEqual([]);
});

test('Return plugins registered without a key for every config', () => {
    const plugin = class {};

    pluginRegistry.add(plugin);

    expect(pluginRegistry.getPlugins([])).toEqual([plugin]);
    expect(pluginRegistry.getPlugins(['table'])).toEqual([plugin]);
});

test('Return a plugin only if one of its keys is enabled', () => {
    const tablePlugin = class {};
    const listPlugin = class {};

    pluginRegistry.add(tablePlugin, 'table');
    pluginRegistry.add(listPlugin, ['ul', 'ol']);

    expect(pluginRegistry.getPlugins([])).toEqual([]);
    expect(pluginRegistry.getPlugins(['table'])).toEqual([tablePlugin]);
    expect(pluginRegistry.getPlugins(['ol'])).toEqual([listPlugin]);
    expect(pluginRegistry.getPlugins(['table', 'ul'])).toEqual([tablePlugin, listPlugin]);
});

test('Return a plugin registered for multiple enabled keys only once', () => {
    const listPlugin = class {};

    pluginRegistry.add(listPlugin, ['ul', 'ol']);

    expect(pluginRegistry.getPlugins(['ul', 'ol'])).toEqual([listPlugin]);
});

test('Return the keys plugins have been registered for', () => {
    pluginRegistry.add(class {});
    pluginRegistry.add(class {}, 'table');
    pluginRegistry.add(class {}, ['ul', 'ol']);

    expect(pluginRegistry.keys).toEqual(['table', 'ul', 'ol']);
});
