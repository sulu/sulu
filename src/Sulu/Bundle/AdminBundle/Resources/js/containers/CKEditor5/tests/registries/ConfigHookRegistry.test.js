// @flow
import configHookRegistry from '../../registries/ConfigHookRegistry';

beforeEach(() => {
    configHookRegistry.clear();
});

test('Add and clear ConfigHooks', () => {
    const hook1 = jest.fn();
    const hook2 = jest.fn();

    configHookRegistry.add(hook1);
    configHookRegistry.add(hook2);
    expect(configHookRegistry.configHooks).toEqual([hook1, hook2]);

    configHookRegistry.clear();
    expect(configHookRegistry.configHooks).toEqual([]);
});
