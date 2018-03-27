/* eslint-disable flowtype/require-valid-file-annotation */
import navigationRegistry from '../../registries/NavigationRegistry';

beforeEach(() => {
    navigationRegistry.clear();
});

test('Set and clear all from NavigationRegistry', () => {
    navigationRegistry.set(
        [
            {
                id: '111',
            },
            {
                id: '222',
            },
            {
                id: '333',
            },
        ]
    );
    expect(navigationRegistry.navigationItems).toHaveLength(3);

    navigationRegistry.clear();
    expect(navigationRegistry.navigationItems).toHaveLength(0);
});

test('Set and get all from NavigationRegistry', () => {
    const items = [
        {
            id: '111',
        },
        {
            id: '222',
        },
        {
            id: '333',
        },
    ];

    navigationRegistry.set(items);

    expect(navigationRegistry.get()).toBe(items);
});
