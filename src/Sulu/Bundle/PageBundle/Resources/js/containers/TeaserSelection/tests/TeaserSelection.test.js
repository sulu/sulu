// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {observable} from 'mobx';
import {MultiItemSelection} from 'sulu-admin-bundle/components';
import {MultiListOverlay} from 'sulu-admin-bundle/containers';
import TeaserSelection from '../TeaserSelection';
import TeaserStore from '../stores/TeaserStore';
import Item from '../Item';

jest.mock('sulu-media-bundle/containers/SingleMediaSelectionOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actual = jest.requireActual('sulu-admin-bundle/components');

    const MockMultiItemSelection: any = jest.fn((props) => (
        <actual.MultiItemSelection {...props} />
    ));

    MockMultiItemSelection.Item = jest.fn((props) => (
        <actual.MultiItemSelection.Item {...props} />
    ));

    return {
        ...actual,
        MultiItemSelection: MockMultiItemSelection,
    };
});

jest.mock('sulu-admin-bundle/containers/MultiListOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/containers/TextEditor', () => jest.fn(
    ({value}) => (<textarea onChange={jest.fn()} value={value} />))
);

jest.mock('../stores/TeaserStore', () => jest.fn());

jest.mock('../Item', () => {
    const React = require('react');
    const actual = jest.requireActual('../Item');

    return jest.fn((props) => <actual.default {...props} />);
});

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

const TeaserStoreMock = (TeaserStore: any);
const MultiItemSelectionMock = (MultiItemSelection: any);
const MultiItemSelectionItemMock = (MultiItemSelection.Item: any);
const MultiListOverlayMock = (MultiListOverlay: any);
const ItemMock = (Item: any);
const emptyFindById = () => undefined;

const getMockCallProps = (mockComponent) => mockComponent.mock.calls.map(([props]) => props);

const getLastMockCallProps = (mockComponent) => {
    const props = getMockCallProps(mockComponent);
    if (props.length === 0) {
        throw new Error('Expected mock component to be called');
    }

    return props[props.length - 1];
};

const getLastMockCallPropsMatching = (mockComponent, matcher) => {
    const props = getMockCallProps(mockComponent).filter(matcher);
    if (props.length === 0) {
        throw new Error('Expected matching mock component to be called');
    }

    return props[props.length - 1];
};

const getTeaserStore = () => {
    const stores = TeaserStoreMock.mock.instances;
    if (stores.length === 0) {
        throw new Error('Expected TeaserStore to be instantiated');
    }

    return stores[stores.length - 1];
};

const getMultiItemSelectionProps = () => getLastMockCallProps(MultiItemSelectionMock);
const getMultiItemSelectionItemPropsById = (id) => getLastMockCallPropsMatching(
    MultiItemSelectionItemMock,
    (props) => props.id === id
);
const getMultiListOverlayPropsByResourceKey = (resourceKey) => getLastMockCallPropsMatching(
    MultiListOverlayMock,
    (props) => props.resourceKey === resourceKey
);
const getItemProps = (id, type) => getLastMockCallPropsMatching(
    ItemMock,
    (props) => props.id === id && props.type === type
);

const renderTeaserSelection = (customProps: Object = {}) => {
    const props = {
        locale: observable.box('en'),
        onChange: jest.fn(),
        ...customProps,
    };

    return render(<TeaserSelection {...props} />);
};

beforeEach(() => {
    TeaserSelection.Item.mediaUrl = '/admin/media/:id?format=sulu-25x25';
    TeaserStoreMock.prototype.destroy = jest.fn();
    jest.clearAllMocks();
});

afterEach(() => {
    TeaserStoreMock.mock.instances.forEach((store) => {
        if (!store.destroy) {
            store.destroy = jest.fn();
        }
    });
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
        this.loading = true;
    });

    const view = renderTeaserSelection({value});

    expect(view.asFragment()).toMatchSnapshot();
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    const view = renderTeaserSelection({
        presentations,
        value,
    });

    expect(view.asFragment()).toMatchSnapshot();
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    const view = renderTeaserSelection({value});

    act(() => {
        getTeaserStore().loading = false;
    });

    expect(view.asFragment()).toMatchSnapshot();
});

test('Render MultiItemSelection disabled when disabled flag is set', () => {
    renderTeaserSelection({disabled: true});

    expect(getMultiItemSelectionProps().disabled).toEqual(true);
});

test('Avoid that MultiListOverlay loads the preSelectedItems from start', () => {
    renderTeaserSelection({disabled: true});

    expect(getMockCallProps(MultiListOverlayMock)).toHaveLength(2);
    expect(getMultiListOverlayPropsByResourceKey('pages').preloadSelectedItems).toEqual(false);
    expect(getMultiListOverlayPropsByResourceKey('articles').preloadSelectedItems).toEqual(false);
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
    });

    renderTeaserSelection({
        onChange: changeSpy,
        presentations,
        value: undefined,
    });

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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({value});

    expect(getTeaserStore().add).toBeCalledTimes(2);
    expect(getTeaserStore().add).toBeCalledWith('pages', 2);
    expect(getTeaserStore().add).toBeCalledWith('contacts', 3);
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();

        this['findById'] = (type, id) => {
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
        };
    });

    const view = renderTeaserSelection({value});

    expect(view.asFragment()).toMatchSnapshot();
});

test('Opening different adding overlays and close them without any action', () => {
    renderTeaserSelection({value: undefined});

    expect(getMultiItemSelectionProps().leftButton.options).toEqual([
        {label: 'Pages', value: 'pages'},
        {label: 'Articles', value: 'articles'},
    ]);

    expect(getMultiListOverlayPropsByResourceKey('pages').open).toEqual(false);
    expect(getMultiListOverlayPropsByResourceKey('articles').open).toEqual(false);

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('articles');
    });

    expect(getMultiListOverlayPropsByResourceKey('pages').open).toEqual(false);
    expect(getMultiListOverlayPropsByResourceKey('articles').open).toEqual(true);

    act(() => {
        getMultiListOverlayPropsByResourceKey('articles').onClose();
        getMultiItemSelectionProps().leftButton.onClick('pages');
    });

    expect(getMultiListOverlayPropsByResourceKey('pages').open).toEqual(true);
    expect(getMultiListOverlayPropsByResourceKey('articles').open).toEqual(false);
});

test('Adding a teaser element', () => {
    const changeSpy = jest.fn();

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
    });

    renderTeaserSelection({
        onChange: changeSpy,
        value: undefined,
    });

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('pages');
    });

    expect(getMultiListOverlayPropsByResourceKey('pages').open).toEqual(true);

    act(() => {
        getMultiListOverlayPropsByResourceKey('pages').onConfirm([{id: 6}, {id: 5}]);
    });

    expect(getMultiListOverlayPropsByResourceKey('pages').open).toEqual(false);

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [{id: 6, type: 'pages'}, {id: 5, type: 'pages'}],
    });

    expect(getTeaserStore().add).toBeCalledWith('pages', 6);
    expect(getTeaserStore().add).toBeCalledWith('pages', 5);
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({
        onChange: changeSpy,
        value,
    });

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('articles');
    });

    expect(getMultiListOverlayPropsByResourceKey('articles').open).toEqual(true);

    act(() => {
        getMultiListOverlayPropsByResourceKey('articles').onConfirm([{id: 6}]);
    });

    expect(getMultiListOverlayPropsByResourceKey('articles').open).toEqual(false);

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 8, type: 'pages'},
            {id: 6, type: 'articles'},
        ],
    });

    expect(getTeaserStore().add).toBeCalledWith('articles', 6);
});

test('Adding a teaser item along with other teaser items which has already been added', () => {
    const changeSpy = jest.fn();

    const value = {
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
        ],
    };

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({
        onChange: changeSpy,
        value,
    });

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('pages');
    });

    expect(getMultiListOverlayPropsByResourceKey('pages').open).toEqual(true);

    act(() => {
        getMultiListOverlayPropsByResourceKey('pages').onConfirm([{id: 5}, {id: 6}]);
    });

    expect(getMultiListOverlayPropsByResourceKey('pages').open).toEqual(false);

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 6, type: 'pages'},
        ],
    });

    expect(getTeaserStore().add).toBeCalledWith('pages', 6);
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({
        onChange: changeSpy,
        value,
    });

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('pages');
    });

    expect(getMultiListOverlayPropsByResourceKey('pages').open).toEqual(true);

    act(() => {
        getMultiListOverlayPropsByResourceKey('pages').onConfirm([{id: 6}]);
    });

    expect(getMultiListOverlayPropsByResourceKey('pages').open).toEqual(false);

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [{id: 5, type: 'articles'}, {id: 6, type: 'pages'}],
    });

    expect(getTeaserStore().add).toBeCalledWith('pages', 6);
    expect(getTeaserStore().add).toBeCalledWith('pages', 5);
});

test('Preselecting correct items', () => {
    const changeSpy = jest.fn();

    const value = {
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 8, type: 'pages'},
            {id: 5, type: 'articles'},
        ],
    };

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({
        onChange: changeSpy,
        value,
    });

    act(() => {
        getMultiItemSelectionProps().leftButton.onClick('pages');
    });

    expect(getMultiListOverlayPropsByResourceKey('pages').open).toEqual(true);
    expect(getMultiListOverlayPropsByResourceKey('pages').preSelectedItems)
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({value});

    expect(getItemProps(2, 'pages').editing).toEqual(false);
    expect(getItemProps(6, 'pages').editing).toEqual(false);

    act(() => {
        getMultiItemSelectionItemPropsById('pages;2').onEdit('pages;2');
    });

    expect(getItemProps(2, 'pages').editing).toEqual(true);
    expect(getItemProps(6, 'pages').editing).toEqual(false);

    act(() => {
        getMultiItemSelectionItemPropsById('pages;6').onEdit('pages;6');
    });

    expect(getItemProps(2, 'pages').editing).toEqual(true);
    expect(getItemProps(6, 'pages').editing).toEqual(true);

    act(() => {
        getItemProps(2, 'pages').onCancel('pages', 2);
    });

    expect(getItemProps(2, 'pages').editing).toEqual(false);
    expect(getItemProps(6, 'pages').editing).toEqual(true);
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({
        onChange: changeSpy,
        value,
    });

    act(() => {
        getMultiItemSelectionItemPropsById('pages;6').onEdit('pages;6');
    });

    act(() => {
        getItemProps(6, 'pages').onApply({
            description: 'Edited Description 2',
            id: 6,
            title: 'Edited Title 2',
            type: 'pages',
        });
    });

    expect(changeSpy).toBeCalledWith(
        {
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
        }
    );
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({
        onChange: changeSpy,
        value,
    });

    act(() => {
        getMultiItemSelectionItemPropsById('pages;6').onRemove('pages;6');
    });

    expect(changeSpy).toBeCalledWith(
        {
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
        }
    );
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({
        onChange: changeSpy,
        value,
    });

    act(() => {
        getMultiItemSelectionProps().onItemsSorted(2, 1);
    });

    expect(changeSpy).toBeCalledWith(
        {
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
        }
    );
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({
        onChange: jest.fn(),
        onItemClick: itemClickSpy,
        value,
    });

    act(() => {
        getMultiItemSelectionItemPropsById('pages;2').onClick('pages;2', item1);
    });

    expect(itemClickSpy).toHaveBeenLastCalledWith('pages;2', item1);

    act(() => {
        getMultiItemSelectionItemPropsById('pages;6').onClick('pages;6', item2);
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this['findById'] = emptyFindById;
    });

    renderTeaserSelection({
        onChange: jest.fn(),
        onItemClick: itemClickSpy,
        value,
    });

    act(() => {
        getMultiItemSelectionItemPropsById('pages;2').onEdit('pages;2');
    });

    expect(getItemProps(2, 'pages').editing).toEqual(true);
    expect(getMultiItemSelectionItemPropsById('pages;2').onClick).toBeUndefined();
    expect(itemClickSpy).toHaveBeenCalledTimes(0);
});

test('Call destroy of TeaserStore when unmounted', () => {
    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.destroy = jest.fn();
    });

    const view = renderTeaserSelection();

    const teaserStore = getTeaserStore();
    view.unmount();

    expect(teaserStore.destroy).toBeCalledWith();
});
