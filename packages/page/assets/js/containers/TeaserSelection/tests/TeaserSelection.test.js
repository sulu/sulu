// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {observable} from 'mobx';
import {MultiListOverlay} from 'sulu-admin-bundle/containers';
import MultiItemSelection from 'sulu-admin-bundle/components/MultiItemSelection';
import findMockCallArg from 'sulu-admin-bundle/utils/TestHelper/findMockCallArg';
import getMockCallArg from 'sulu-admin-bundle/utils/TestHelper/getMockCallArg';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import TeaserSelection from '../TeaserSelection';
import TeaserStore from '../stores/TeaserStore';
import Item from '../Item';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/containers/MultiListOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/components/MultiItemSelection', () => {
    const MultiItemSelectionMock: any = jest.fn(({children}) => <div>{children}</div>);
    MultiItemSelectionMock.Item = jest.fn(({children}) => <div>{children}</div>);

    return MultiItemSelectionMock;
});

jest.mock('../Item', () => jest.fn(() => null));
jest.mock('../stores/TeaserStore', () => jest.fn());

jest.mock('../registries/teaserProviderRegistry', () => ({
    keys: ['pages', 'articles'],
    get: jest.fn((key) => {
        switch (key) {
            case 'pages':
                return {title: 'Pages'};
            case 'articles':
                return {title: 'Articles'};
            case 'contacts':
                return {title: 'Contacts'};
        }
    }),
}));

const TeaserStoreMock: any = TeaserStore;
const MultiItemSelectionMock: any = MultiItemSelection;
const MultiListOverlayMock: any = MultiListOverlay;
const ItemMock: any = Item;

function getStore() {
    return TeaserStoreMock.mock.instances[TeaserStoreMock.mock.instances.length - 1];
}

function getMultiItemSelectionProps() {
    return getLatestMockProps(MultiItemSelectionMock);
}

function getMultiItemSelectionItemProps(index: number) {
    const itemCount = React.Children.count(getMultiItemSelectionProps().children);
    const callIndex = MultiItemSelectionMock.Item.mock.calls.length - itemCount + index;

    return getMockCallArg(MultiItemSelectionMock.Item, callIndex, 0);
}

function getItemProps(index: number) {
    const itemCount = React.Children.count(getMultiItemSelectionProps().children);
    const callIndex = ItemMock.mock.calls.length - itemCount + index;

    return getMockCallArg(ItemMock, callIndex, 0);
}

function getMultiListOverlayProps(resourceKey: string) {
    return findMockCallArg(MultiListOverlayMock, ([props]) => props.resourceKey === resourceKey);
}

function renderTeaserSelection(props: Object = {}) {
    const teaserSelectionRef: any = React.createRef();
    const view = render(
        <TeaserSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            ref={teaserSelectionRef}
            {...props}
        />
    );

    if (!teaserSelectionRef.current) {
        throw new Error('TeaserSelection ref was not set');
    }

    return {
        teaserSelection: teaserSelectionRef.current,
        ...view,
    };
}

beforeEach(() => {
    jest.clearAllMocks();
    TeaserSelection.Item.mediaUrl = '/admin/media/:id?format=sulu-25x25';
});

test('Render loading teaser selection', () => {
    const value = {
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
        ],
        presentAs: '',
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
        this.loading = true;
    });

    const {asFragment} = renderTeaserSelection({value});

    expect(asFragment()).toMatchSnapshot();
});

test('Render teaser selection with presentations', () => {
    const value = {
        presentAs: 'test-2',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
        ],
    };

    const presentations = [
        {
            label: 'Test 1',
            value: 'test-1',
        },
        {
            label: 'Test 2',
            value: 'test-2',
        },
    ];

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    const {asFragment} = renderTeaserSelection({presentations, value});

    expect(asFragment()).toMatchSnapshot();
});

test('Render teaser selection with data', () => {
    const value = {
        presentAs: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    const {teaserSelection, asFragment} = renderTeaserSelection({value});
    teaserSelection.teaserStore.loading = false;

    expect(asFragment()).toMatchSnapshot();
});

test('Render MultiItemSelection disabled when disabled flag is set', () => {
    renderTeaserSelection({disabled: true});

    expect(getMultiItemSelectionProps().disabled).toEqual(true);
});

test('Avoid that MultiListOverlay loads the preSelectedItems from start', () => {
    renderTeaserSelection({disabled: true});

    expect(MultiListOverlayMock).toHaveBeenCalledTimes(2);
    expect(getMultiListOverlayProps('pages').preloadSelectedItems).toEqual(false);
    expect(getMultiListOverlayProps('articles').preloadSelectedItems).toEqual(false);
});

test('Call onChange when presentation is changed', () => {
    const changeSpy = jest.fn();

    const presentations = [
        {
            label: 'Test 1',
            value: 'test-1',
        },
        {
            label: 'Test 2',
            value: 'test-2',
        },
    ];

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
    });

    renderTeaserSelection({onChange: changeSpy, presentations, value: undefined});

    act(() => {
        getMultiItemSelectionProps().rightButton.onClick('test-2');
    });

    expect(changeSpy).toBeCalledWith({
        presentAs: 'test-2',
        items: [],
    });
});

test('Add passed data to TeaserStore', () => {
    const value = {
        presentAs: '',
        items: [
            {
                description: 'Description 1',
                id: 2,
                title: 'Title 1',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 3,
                title: 'Title 2',
                type: 'contacts',
            },
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({value});

    expect(getStore().add).toBeCalledTimes(2);
    expect(getStore().add).toBeCalledWith('pages', 2);
    expect(getStore().add).toBeCalledWith('contacts', 3);
});

test('Load combined data from TeaserStore and props', () => {
    const value = {
        presentAs: '',
        items: [
            {
                description: 'Edited Page Description',
                id: 2,
                title: 'Edited Page Title',
                type: 'pages',
            },
            {
                description: undefined,
                id: 3,
                title: undefined,
                type: 'contacts',
            },
            {
                id: 4,
                type: 'contacts',
            },
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();

        this['findById'] = jest.fn((type, id) => {
            if (type === 'pages' && id === 2) {
                return {
                    description: 'Page Description',
                    id: 2,
                    mediaId: 8,
                    title: 'Page',
                    type: 'pages',
                };
            }

            if (type === 'contacts' && id === 3) {
                return {
                    description: 'Contact Description 1',
                    id: 3,
                    title: 'Contact 1',
                    type: 'contacts',
                };
            }

            if (type === 'contacts' && id === 4) {
                return {
                    description: 'Contact Description 2',
                    id: 4,
                    title: 'Contact 2',
                    type: 'contacts',
                };
            }

            throw new Error('This case should not happen!');
        });
    });

    const {asFragment} = renderTeaserSelection({value});

    expect(asFragment()).toMatchSnapshot();
});

test('Opening different adding overlays and close them without any action', () => {
    renderTeaserSelection({value: undefined});

    expect(getMultiItemSelectionProps().leftButton.options).toEqual([
        {label: 'Pages', value: 'pages'},
        {label: 'Articles', value: 'articles'},
    ]);

    expect(getMultiListOverlayProps('pages').open).toEqual(false);
    expect(getMultiListOverlayProps('articles').open).toEqual(false);

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('articles');
    });

    expect(getMultiListOverlayProps('pages').open).toEqual(false);
    expect(getMultiListOverlayProps('articles').open).toEqual(true);

    act(() => {
        getMultiListOverlayProps('articles').onClose();
        getMultiItemSelectionProps().leftButton.onClick('pages');
    });

    expect(getMultiListOverlayProps('pages').open).toEqual(true);
    expect(getMultiListOverlayProps('articles').open).toEqual(false);
});

test('Adding a teaser element', () => {
    const changeSpy = jest.fn();

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
    });

    renderTeaserSelection({onChange: changeSpy, value: undefined});

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('pages');
    });

    expect(getMultiListOverlayProps('pages').open).toEqual(true);

    act(() => {
        getMultiListOverlayProps('pages').onConfirm([{id: 6}, {id: 5}]);
    });

    expect(getMultiListOverlayProps('pages').open).toEqual(false);

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [{id: 6, type: 'pages'}, {id: 5, type: 'pages'}],
    });

    expect(getStore().add).toBeCalledWith('pages', 6);
    expect(getStore().add).toBeCalledWith('pages', 5);
});

test('Adding two different kind of teasers', () => {
    const changeSpy = jest.fn();

    const value = {
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 8, type: 'pages'},
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({onChange: changeSpy, value});

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('articles');
    });

    expect(getMultiListOverlayProps('articles').open).toEqual(true);

    act(() => {
        getMultiListOverlayProps('articles').onConfirm([{id: 6}]);
    });

    expect(getMultiListOverlayProps('articles').open).toEqual(false);

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 8, type: 'pages'},
            {id: 6, type: 'articles'},
        ],
    });

    expect(getStore().add).toBeCalledWith('articles', 6);
});

test('Adding a teaser item along with other teaser items which has already been added', () => {
    const changeSpy = jest.fn();

    const value = {
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({onChange: changeSpy, value});

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('pages');
    });

    expect(getMultiListOverlayProps('pages').open).toEqual(true);

    act(() => {
        getMultiListOverlayProps('pages').onConfirm([{id: 5}, {id: 6}]);
    });

    expect(getMultiListOverlayProps('pages').open).toEqual(false);

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 6, type: 'pages'},
        ],
    });

    expect(getStore().add).toBeCalledWith('pages', 6);
});

test('Removing by unselecting element in teaser selection', () => {
    const changeSpy = jest.fn();

    const value = {
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 8, type: 'pages'},
            {id: 5, type: 'articles'},
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({onChange: changeSpy, value});

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('pages');
    });

    expect(getMultiListOverlayProps('pages').open).toEqual(true);

    act(() => {
        getMultiListOverlayProps('pages').onConfirm([{id: 6}]);
    });

    expect(getMultiListOverlayProps('pages').open).toEqual(false);

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [{id: 5, type: 'articles'}, {id: 6, type: 'pages'}],
    });

    expect(getStore().add).toBeCalledWith('pages', 6);
    expect(getStore().add).toBeCalledWith('pages', 5);
});

test('Preselecting correct items', () => {
    const value = {
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 8, type: 'pages'},
            {id: 5, type: 'articles'},
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({onChange: jest.fn(), value});

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('pages');
    });

    expect(getMultiListOverlayProps('pages').open).toEqual(true);
    expect(getMultiListOverlayProps('pages').preSelectedItems)
        .toEqual([{id: 5, type: 'pages'}, {id: 8, type: 'pages'}]);
});

test('Open and close items when clicking on the pen icon', () => {
    const value = {
        presentAs: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({onChange: jest.fn(), value});

    expect(getItemProps(0).editing).toEqual(false);
    expect(getItemProps(1).editing).toEqual(false);

    act(() => {
        getMultiItemSelectionItemProps(0).onEdit('pages;2');
    });

    expect(getItemProps(0).editing).toEqual(true);
    expect(getItemProps(1).editing).toEqual(false);

    act(() => {
        getMultiItemSelectionItemProps(1).onEdit('pages;6');
    });

    expect(getItemProps(0).editing).toEqual(true);
    expect(getItemProps(1).editing).toEqual(true);

    act(() => {
        getItemProps(0).onCancel('pages', 2);
    });

    expect(getItemProps(0).editing).toEqual(false);
    expect(getItemProps(1).editing).toEqual(true);
});

test('Call onChange with new values when apply button is clicked', () => {
    const changeSpy = jest.fn();

    const value = {
        presentAs: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
            {
                description: 'Description 3',
                id: 6,
                title: 'Title 3',
                type: 'contacts',
            },
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({onChange: changeSpy, value});

    act(() => {
        getMultiItemSelectionItemProps(1).onEdit('pages;6');
    });

    act(() => {
        getItemProps(1).onApply({
            description: 'Edited Description 2',
            id: 6,
            title: 'Edited Title 2',
            type: 'pages',
        });
    });

    expect(changeSpy).toBeCalledWith({
        presentAs: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Edited Description 2',
                id: 6,
                title: 'Edited Title 2',
                type: 'pages',
            },
            {
                description: 'Description 3',
                id: 6,
                title: 'Title 3',
                type: 'contacts',
            },
        ],
    });
});

test('Call onChange with new values after one item is removed', () => {
    const changeSpy = jest.fn();

    const value = {
        presentAs: '',
        items: [
            {
                description: 'Contact',
                id: 6,
                title: 'Contact',
                type: 'contacts',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
            {
                description: 'Description 3',
                id: 7,
                title: 'Title 3',
                type: 'pages',
            },
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({onChange: changeSpy, value});

    act(() => {
        getMultiItemSelectionItemProps(1).onRemove('pages;6');
    });

    expect(changeSpy).toBeCalledWith({
        presentAs: '',
        items: [
            {
                description: 'Contact',
                id: 6,
                title: 'Contact',
                type: 'contacts',
            },
            {
                description: 'Description 3',
                id: 7,
                title: 'Title 3',
                type: 'pages',
            },
        ],
    });
});

test('Call onChange with new values after items are sorted', () => {
    const changeSpy = jest.fn();

    const value = {
        presentAs: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
            {
                description: 'Description 3',
                id: 9,
                title: 'Title 3',
                type: 'pages',
            },
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({onChange: changeSpy, value});

    act(() => {
        getMultiItemSelectionProps().onItemsSorted(2, 1);
    });

    expect(changeSpy).toBeCalledWith({
        presentAs: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 3',
                id: 9,
                title: 'Title 3',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
        ],
    });
});

test('Call onItemClick when an item is clicked', () => {
    const itemClickSpy = jest.fn();

    const item1 = {
        description: 'Description',
        edited: true,
        id: 2,
        title: 'Title',
        type: 'pages',
    };

    const item2 = {
        description: 'Description 2',
        edited: true,
        id: 6,
        title: 'Title 2',
        type: 'pages',
    };

    const value = {
        presentAs: '',
        items: [
            item1,
            item2,
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({onChange: jest.fn(), onItemClick: itemClickSpy, value});

    act(() => {
        getMultiItemSelectionItemProps(0).onClick('pages;2', item1);
    });
    expect(itemClickSpy).toHaveBeenLastCalledWith('pages;2', item1);

    act(() => {
        getMultiItemSelectionItemProps(1).onClick('pages;6', item2);
    });
    expect(itemClickSpy).toHaveBeenLastCalledWith('pages;6', item2);
});

test('Call not onItemClick when an item is clicked in edit mode', () => {
    const itemClickSpy = jest.fn();

    const item1 = {
        description: 'Description',
        edited: true,
        id: 2,
        title: 'Title',
        type: 'pages',
    };

    const item2 = {
        description: 'Description 2',
        edited: true,
        id: 6,
        title: 'Title 2',
        type: 'pages',
    };

    const value = {
        presentAs: '',
        items: [
            item1,
            item2,
        ],
    };

    TeaserStoreMock.mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    renderTeaserSelection({onChange: jest.fn(), onItemClick: itemClickSpy, value});

    act(() => {
        getMultiItemSelectionItemProps(0).onEdit('pages;2');
    });

    expect(getItemProps(0).editing).toEqual(true);
    expect(getMultiItemSelectionItemProps(0).onClick).toBe(undefined);
    expect(itemClickSpy).toHaveBeenCalledTimes(0);
});

test('Call destroy of TeaserStore when unmounted', () => {
    TeaserStoreMock.mockImplementation(function() {
        this.destroy = jest.fn();
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = jest.fn();
    });

    const {teaserSelection, unmount} = renderTeaserSelection();

    const teaserStore = teaserSelection.teaserStore;
    unmount();

    expect(teaserStore.destroy).toBeCalledWith();
});
