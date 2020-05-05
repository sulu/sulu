// @flow
import conditionDataProviderRegistry from '../../registries/conditionDataProviderRegistry';

beforeEach(() => {
    conditionDataProviderRegistry.clear();
});

test('Clear all fields from conditionDataProviderRegistry', () => {
    conditionDataProviderRegistry.add(jest.fn());
    expect(conditionDataProviderRegistry.conditionDataProviders).toHaveLength(1);

    conditionDataProviderRegistry.clear();
    expect(conditionDataProviderRegistry.conditionDataProviders).toHaveLength(0);
});

test('Add field to conditionDataProviderRegistry', () => {
    const conditionDataProvider1 = jest.fn();
    const conditionDataProvider2 = jest.fn();
    conditionDataProviderRegistry.add(conditionDataProvider1);
    conditionDataProviderRegistry.add(conditionDataProvider2);

    expect(conditionDataProviderRegistry.getAll()).toEqual([conditionDataProvider1, conditionDataProvider2]);
});
