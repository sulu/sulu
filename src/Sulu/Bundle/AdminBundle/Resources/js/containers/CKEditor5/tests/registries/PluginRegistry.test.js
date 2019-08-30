// @flow
import pluginRegistry from '../../registries/pluginRegistry';

beforeEach(() => {
    pluginRegistry.clear();
});

test('Add and clear Plugins', () => {
    const plugin1 = class {};
    const plugin2 = class {};

    pluginRegistry.add(plugin1);
    pluginRegistry.add(plugin2);
    expect(pluginRegistry.plugins).toEqual([plugin1, plugin2]);

    pluginRegistry.clear();
    expect(pluginRegistry.plugins).toEqual([]);
});
