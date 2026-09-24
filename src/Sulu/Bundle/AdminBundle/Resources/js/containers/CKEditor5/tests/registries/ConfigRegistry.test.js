// @flow
import configRegistry from '../../registries/configRegistry';

beforeEach(() => {
    configRegistry.clear();
});

test('Add and clear configs', () => {
    const config1 = jest.fn();
    const config2 = jest.fn();

    configRegistry.add(config1);
    configRegistry.add(config2);
    expect(configRegistry.getConfigs([])).toEqual([config1, config2]);

    configRegistry.clear();
    expect(configRegistry.getConfigs([])).toEqual([]);
});

test('Return configs registered without a key for every config', () => {
    const config = jest.fn();

    configRegistry.add(config);

    expect(configRegistry.getConfigs([])).toEqual([config]);
    expect(configRegistry.getConfigs(['table'])).toEqual([config]);
});

test('Return a config only if one of its keys is enabled', () => {
    const tableConfig = jest.fn();
    const listConfig = jest.fn();

    configRegistry.add(tableConfig, 'table');
    configRegistry.add(listConfig, ['ul', 'ol']);

    expect(configRegistry.getConfigs([])).toEqual([]);
    expect(configRegistry.getConfigs(['table'])).toEqual([tableConfig]);
    expect(configRegistry.getConfigs(['ul'])).toEqual([listConfig]);
});

test('Return configs with a higher priority first', () => {
    const lowConfig = jest.fn();
    const highConfig = jest.fn();
    const defaultConfig = jest.fn();

    configRegistry.add(lowConfig, undefined, 10);
    configRegistry.add(defaultConfig);
    configRegistry.add(highConfig, undefined, 20);

    expect(configRegistry.getConfigs([])).toEqual([highConfig, lowConfig, defaultConfig]);
});

test('Keep the registration order for configs with the same priority', () => {
    const config1 = jest.fn();
    const config2 = jest.fn();

    configRegistry.add(config1, undefined, 10);
    configRegistry.add(config2, undefined, 10);

    expect(configRegistry.getConfigs([])).toEqual([config1, config2]);
});

test('Return the keys configs have been registered for', () => {
    configRegistry.add(jest.fn());
    configRegistry.add(jest.fn(), 'table');
    configRegistry.add(jest.fn(), ['ul', 'ol']);

    expect(configRegistry.keys).toEqual(['table', 'ul', 'ol']);
});
