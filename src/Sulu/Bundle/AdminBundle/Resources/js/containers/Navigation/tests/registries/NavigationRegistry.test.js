// @flow
import navigationRegistry from '../../registries/NavigationRegistry';

beforeEach(() => {
    navigationRegistry.clear();
});

test('Set and clear all from NavigationRegistry', () => {
    navigationRegistry.set(
        [
            {
                id: '111',
                label: 'Test 1',
                icon: 'su-webspace',
                mainRoute: 'sulu_page.webspaces',
            },
            {
                id: '222',
                label: 'Test 2',
                icon: 'su-webspace',
                mainRoute: 'sulu_page.webspaces',
            },
            {
                id: '333',
                label: 'Test 3',
                icon: 'su-webspace',
                mainRoute: 'sulu_page.webspaces',
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
            label: 'Test 1',
            icon: 'su-webspace',
            mainRoute: 'sulu_page.webspaces',
        },
        {
            id: '222',
            label: 'Test 2',
            icon: 'su-webspace',
            mainRoute: 'sulu_page.webspaces',
        },
        {
            id: '333',
            label: 'Test 3',
            icon: 'su-webspace',
            mainRoute: 'sulu_page.webspaces',
        },
    ];

    navigationRegistry.set(items);

    expect(navigationRegistry.getAll()).toBe(items);
});

test('Get should return the correct item', () => {
    const items = [
        {
            id: '111',
            label: 'Test 1',
            icon: 'su-webspace',
            mainRoute: 'sulu_page.webspaces',
        },
        {
            id: '222',
            label: 'Test 2',
            icon: 'su-webspace',
            mainRoute: 'sulu_page.webspaces',
        },
        {
            id: '333',
            label: 'Test 3',
            icon: 'su-webspace',
            mainRoute: 'sulu_page.webspaces',
            items: [
                {
                    id: '444',
                    label: 'Test 4',
                    icon: 'su-webspace',
                    mainRoute: 'sulu_page.webspaces',
                },
                {
                    id: '555',
                    label: 'Test 5',
                    icon: 'su-webspace',
                    mainRoute: 'sulu_page.webspaces',
                },
            ],
        },
    ];

    navigationRegistry.set(items);

    expect(navigationRegistry.get('111')).toBe(items[0]);
    expect(navigationRegistry.get('555')).toBe(items[2].items[1]);
});
