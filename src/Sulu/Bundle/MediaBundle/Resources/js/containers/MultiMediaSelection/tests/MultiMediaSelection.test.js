// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {act, render} from '@testing-library/react';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import MultiMediaSelection from '../MultiMediaSelection';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../MultiMediaSelectionOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const MultiItemSelection: any = jest.fn(function MultiItemSelectionMock({children}) {
        return React.createElement('div', undefined, children);
    });

    MultiItemSelection.Item = jest.fn(function MultiItemSelectionItemMock({children}) {
        return React.createElement('div', undefined, children);
    });

    const CroppedText = function CroppedTextMock({children}) {
        return React.createElement('span', undefined, children);
    };

    const Icon = function IconMock({name}) {
        return React.createElement('span', {className: name});
    };

    return {
        CroppedText,
        Icon,
        MultiItemSelection,
    };
});

jest.mock('sulu-admin-bundle/stores', () => ({
    MultiSelectionStore: jest.fn(),
}));

const componentsMock: any = jest.requireMock('sulu-admin-bundle/components');
const MultiMediaSelectionOverlayMock: any = jest.requireMock('../../MultiMediaSelectionOverlay');
const MultiSelectionStoreMock: any = jest.requireMock('sulu-admin-bundle/stores').MultiSelectionStore;

const getStore = () => MultiSelectionStoreMock.mock.instances[MultiSelectionStoreMock.mock.instances.length - 1];

let initialStoreItems;

beforeEach(() => {
    jest.clearAllMocks();
    initialStoreItems = [];

    MultiSelectionStoreMock.mockImplementation(function(resourceKey, selectedIds) {
        this.loadItems = jest.fn();
        this.move = jest.fn((oldIndex, newIndex) => {
            const oldItems = this.items.slice();
            const movedItem = oldItems.splice(oldIndex, 1)[0];
            if (!movedItem) {
                return;
            }
            oldItems.splice(newIndex, 0, movedItem);
            this.items = oldItems;
        });
        this.removeById = jest.fn((id) => {
            this.items = this.items.filter((item) => item.id !== id);
        });
        this.set = jest.fn((items) => {
            this.items = items;
        });

        mockExtendObservable(this, {
            items: initialStoreItems.length
                ? initialStoreItems
                : selectedIds.map((id) => ({id, mimeType: 'image/jpeg', thumbnails: {}})),
            loading: false,
        });
    });
});

test('Render a MultiMediaSelection field', () => {
    initialStoreItems = [
        {id: 1, title: 'Media 1', thumbnails: {'sulu-25x25': 'http://lorempixel.com/25/25'}},
        {id: 2, title: 'Media 2', thumbnails: {'sulu-25x25': 'http://lorempixel.com/25/25'}},
        {id: 3, title: 'Media 3', thumbnails: {'sulu-25x25': 'http://lorempixel.com/25/25'}},
    ];

    const {asFragment} = render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MultiMediaSelection field with display options', () => {
    initialStoreItems = [
        {id: 1, title: 'Media 1', thumbnails: {'sulu-25x25': 'http://lorempixel.com/25/25'}},
    ];

    const {asFragment} = render(
        <MultiMediaSelection
            displayOptions={['top', 'left', 'right', 'bottom']}
            locale={observable.box('en')}
            onChange={jest.fn()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MultiMediaSelection field without thumbnails with MimeTypeIndicator', () => {
    initialStoreItems = [
        {id: 1, title: 'Media 1', mimeType: 'application/json'},
        {id: 2, title: 'Media 2', mimeType: 'application/pdf'},
        {id: 3, title: 'Media 3', mimeType: 'application/vnd.ms-excel'},
    ];

    const {asFragment} = render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('MultiMediaSelection should have 3 child items', () => {
    initialStoreItems = [
        {id: 1, title: 'Media 1', thumbnails: {'sulu-25x25': 'http://lorempixel.com/25/25'}},
        {id: 2, title: 'Media 2', thumbnails: {'sulu-25x25': 'http://lorempixel.com/25/25'}},
        {id: 3, title: 'Media 3', thumbnails: {'sulu-25x25': 'http://lorempixel.com/25/25'}},
    ];

    render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);
    expect(componentsMock.MultiItemSelection.Item).toHaveBeenCalledTimes(3);
});

test('Clicking add media should open overlay', () => {
    render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    expect(getLatestMockProps(MultiMediaSelectionOverlayMock).open).toEqual(false);
    getLatestMockProps(componentsMock.MultiItemSelection).leftButton.onClick();
    expect(getLatestMockProps(MultiMediaSelectionOverlayMock).open).toEqual(true);
});

test('Should remove media from selection store', () => {
    render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    getLatestMockProps(componentsMock.MultiItemSelection).onItemRemove(1);
    expect(getStore().removeById).toBeCalledWith(1);
});

test('Should move media inside selection store', () => {
    render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    getLatestMockProps(componentsMock.MultiItemSelection).onItemsSorted(1, 3);
    expect(getStore().move).toBeCalledWith(1, 3);
});

test('Should add selected media to selection store on confirm', () => {
    const thumbnails = {
        'sulu-240x': 'http://lorempixel.com/240/100',
        'sulu-25x25': 'http://lorempixel.com/25/25',
    };
    const medias = [
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails,
        },
        {
            id: 2,
            title: 'Title 2',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails,
        },
    ];

    render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    getLatestMockProps(componentsMock.MultiItemSelection).leftButton.onClick();
    getLatestMockProps(MultiMediaSelectionOverlayMock).onConfirm(medias);

    expect(getStore().set).toBeCalledWith(medias);
    expect(getLatestMockProps(MultiMediaSelectionOverlayMock).open).toBe(false);
});

test('Should call onChange handler if selection store changes', () => {
    const changeSpy = jest.fn();

    render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    const store = getStore();
    act(() => {
        store.items.push({id: 99, mimeType: 'image/jpeg', thumbnails: {}});
    });
    expect(changeSpy).toBeCalledWith({ids: [55, 99]});

    act(() => {
        store.items.splice(0, 1);
    });
    expect(changeSpy).toBeCalledWith({ids: [99]});
});

test('Should call onChange handler if displayOption changes', () => {
    const changeSpy = jest.fn();

    render(
        <MultiMediaSelection
            displayOptions={['left']}
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    getLatestMockProps(componentsMock.MultiItemSelection).rightButton.onClick('left');
    expect(changeSpy).toBeCalledWith({displayOption: 'left', ids: [55]});
});

test('Should not call onChange callback if component props change', () => {
    const changeSpy = jest.fn();

    const {rerender} = render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    rerender(
        <MultiMediaSelection
            disabled={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );
    expect(changeSpy).not.toBeCalled();
});

test('Should not call onChange callback if unrelated observable changes', () => {
    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    const store = getStore();
    act(() => {
        store.items.push({id: 99, mimeType: 'image/jpeg', thumbnails: {}});
    });
    expect(changeSpy).toBeCalledWith({ids: [55, 99]});
    expect(changeSpy).toHaveBeenCalledTimes(1);

    act(() => {
        unrelatedObservable.set(55);
    });
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should call onItemClick handler if item is clicked', () => {
    const itemClickSpy = jest.fn();

    render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            value={{displayOption: undefined, ids: [55, 99]}}
        />
    );

    getLatestMockProps(componentsMock.MultiItemSelection).onItemClick(
        55,
        {id: 55, mimeType: 'image/jpeg', thumbnails: {}}
    );
    expect(itemClickSpy).toHaveBeenLastCalledWith(
        55,
        {id: 55, mimeType: 'image/jpeg', thumbnails: {}}
    );

    getLatestMockProps(componentsMock.MultiItemSelection).onItemClick(
        99,
        {id: 99, mimeType: 'image/jpeg', thumbnails: {}}
    );
    expect(itemClickSpy).toHaveBeenLastCalledWith(
        99,
        {id: 99, mimeType: 'image/jpeg', thumbnails: {}}
    );
});

test('Pass correct props to MultiItemSelection component', () => {
    render(
        <MultiMediaSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} sortable={false} />
    );

    expect(getLatestMockProps(componentsMock.MultiItemSelection).disabled).toEqual(true);
    expect(getLatestMockProps(componentsMock.MultiItemSelection).sortable).toEqual(false);
});
