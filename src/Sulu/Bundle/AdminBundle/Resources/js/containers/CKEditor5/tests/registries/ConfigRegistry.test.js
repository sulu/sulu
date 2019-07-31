// @flow
import configRegistry from '../../registries/ConfigRegistry';

beforeEach(() => {
    configRegistry.clear();
});

test('Add and clear Configs', () => {
    const config1 = jest.fn();
    const config2 = jest.fn();

    configRegistry.add(config1);
    configRegistry.add(config2);
    expect(configRegistry.configs).toEqual([config1, config2]);

    configRegistry.clear();
    expect(configRegistry.configs).toEqual([]);
});
