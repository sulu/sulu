/* eslint-disable flowtype/require-valid-file-annotation */
import ToolbarStore from '../../stores/ToolbarStore';
import toolbarStorePool from '../../stores/ToolbarStorePool';

test('Create a toolbar instance', () => {
    const storeKey = 'testKey';

    toolbarStorePool.createStore(storeKey);
    expect(toolbarStorePool.hasStore(storeKey)).toBe(true);
});

test('getStore should return an toolbar instance', () => {
    const storeKey = 'testKey';

    toolbarStorePool.createStore(storeKey);
    expect(toolbarStorePool.getStore(storeKey)).toBeInstanceOf(ToolbarStore);
});

test('getStore should throw an error if the key is not defined', () => {
    const storeKey = 'testKey';
    const wrongKey = 'someRandomKey';

    toolbarStorePool.createStore(storeKey);
    expect(() => {
        toolbarStorePool.getStore(wrongKey);
    }).toThrow();
});

test('Set the config of the store', () => {
    const storeKey = 'testKey';

    toolbarStorePool.createStore(storeKey);
    toolbarStorePool.setToolbarConfig(storeKey, {
        items: [
            {
                type: 'button',
                value: 'Test',
                icon: 'test',
                onClick: () => {},
            },
        ],
    });

    expect(toolbarStorePool.getStore(storeKey).config.items[0].value).toBe('Test');
});

