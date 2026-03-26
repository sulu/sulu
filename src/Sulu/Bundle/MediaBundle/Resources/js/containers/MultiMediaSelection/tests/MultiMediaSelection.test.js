// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MultiSelectionStore from 'sulu-admin-bundle/stores/MultiSelectionStore';
import {MultiItemSelection} from 'sulu-admin-bundle/components';
import MultiMediaSelection from '../MultiMediaSelection';
import MultiMediaSelectionOverlay from '../../MultiMediaSelectionOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const CroppedText = jest.fn(({children}) => <>{children}</>);
    const Icon = jest.fn(({name}) => <span aria-label={name} />);
    const MultiItemSelection = jest.fn(({children}) => <div>{children}</div>);
    const MultiItemSelectionAny: any = MultiItemSelection;
    MultiItemSelectionAny.Item = jest.fn(({children}) => <div>{children}</div>);

    return {
        CroppedText,
        Icon,
        MultiItemSelection,
    };
});

jest.mock('../../MultiMediaSelectionOverlay', () => jest.fn(function() {
    return <div>single media selection overlay</div>;
}));

jest.mock('sulu-admin-bundle/stores/MultiSelectionStore', () => jest.fn());

function renderMultiMediaSelection(props: Object = {}) {
    return render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            {...props}
        />
    );
}

function getLatestOverlayProps() {
    const calls = ((MultiMediaSelectionOverlay: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getLatestMultiItemSelectionProps() {
    const calls = ((MultiItemSelection: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getMultiSelectionStoreInstance(index: number = 0) {
    const instances = ((MultiSelectionStore: any).mock.instances: any);
    return instances[index];
}

beforeEach(() => {
    jest.clearAllMocks();

    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
        this.items = [];
        this.loadItems = jest.fn();
        this.move = jest.fn();
        this.removeById = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });
});

test('Render a MultiMediaSelection field', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 2,
                title: 'Media 2',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 3,
                title: 'Media 3',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
        this.loadItems = jest.fn();
        this.move = jest.fn();
        this.removeById = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    const {container} = renderMultiMediaSelection();

    expect(container).toMatchSnapshot();
});

test('Render a MultiMediaSelection field with display options', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
        this.loadItems = jest.fn();
        this.move = jest.fn();
        this.removeById = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    const {container} = renderMultiMediaSelection({
        displayOptions: ['top', 'left', 'right', 'bottom'],
    });

    expect(container).toMatchSnapshot();
});

test('Render a MultiMediaSelection field with display options and selected icon', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
        this.loadItems = jest.fn();
        this.move = jest.fn();
        this.removeById = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    const {container} = renderMultiMediaSelection({
        displayOptions: ['top', 'left', 'right', 'bottom'],
        value: {displayOption: 'left', ids: []},
    });

    expect(container).toMatchSnapshot();
});

test('Render a MultiMediaSelection field without thumbnails with MimeTypeIndicator', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                mimeType: 'application/json',
            },
            {
                id: 2,
                title: 'Media 2',
                mimeType: 'application/pdf',
            },
            {
                id: 3,
                title: 'Media 3',
                mimeType: 'application/vnd.ms-excel',
            },
        ];
        this.loadItems = jest.fn();
        this.move = jest.fn();
        this.removeById = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    const {container} = renderMultiMediaSelection();

    expect(container).toMatchSnapshot();
});

test('The MultiMediaSelection should have 3 child-items', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 2,
                title: 'Media 2',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 3,
                title: 'Media 3',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
        this.loadItems = jest.fn();
        this.move = jest.fn();
        this.removeById = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    renderMultiMediaSelection();

    expect(((MultiItemSelection: any).Item: any).mock.calls).toHaveLength(3);
});

test('Clicking on the "add media" button should open up an overlay', () => {
    renderMultiMediaSelection();

    expect(getLatestOverlayProps().open).toEqual(false);
    getLatestMultiItemSelectionProps().leftButton.onClick();
    expect(getLatestOverlayProps().open).toEqual(true);
});

test('Should remove media from the selection store', () => {
    renderMultiMediaSelection();

    const mediaSelectionStore = getMultiSelectionStoreInstance();

    getLatestMultiItemSelectionProps().onItemRemove(1);
    expect(mediaSelectionStore.removeById).toBeCalledWith(1);
});

test('Should move media inside the selection store', () => {
    renderMultiMediaSelection();

    const mediaSelectionStore = getMultiSelectionStoreInstance();

    getLatestMultiItemSelectionProps().onItemsSorted(1, 3);
    expect(mediaSelectionStore.move).toBeCalledWith(1, 3);
});

test('Should add the selected medias to the selection store on confirm', () => {
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

    renderMultiMediaSelection();

    const mediaSelectionStore = getMultiSelectionStoreInstance();

    getLatestMultiItemSelectionProps().leftButton.onClick();
    getLatestOverlayProps().onConfirm(medias);

    expect(mediaSelectionStore.set).toBeCalledWith(medias);
    expect(getLatestOverlayProps().open).toBe(false);
});

test('Should call the onChange handler if selection store changes', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function(resourceKey, selectedIds) {
        this.loadItems = jest.fn();
        this.move = jest.fn();
        this.removeById = jest.fn();
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: selectedIds.map((id) => {
                return {id, thumbnails: {}, mimeType: 'image/jpeg'};
            }),
        });
    });

    const changeSpy = jest.fn();

    renderMultiMediaSelection({
        onChange: changeSpy,
        value: {displayOption: undefined, ids: [55]},
    });

    const mediaSelectionStore = getMultiSelectionStoreInstance();

    act(() => {
        mediaSelectionStore.items.push({id: 99, thumbnails: {}, mimeType: 'image/jpeg'});
    });
    expect(changeSpy).toBeCalledWith({ids: [55, 99]});

    act(() => {
        mediaSelectionStore.items.splice(0, 1);
    });
    expect(changeSpy).toBeCalledWith({ids: [99]});
});

test('Should call the onChange handler if the displayOption changes', () => {
    const changeSpy = jest.fn();

    renderMultiMediaSelection({
        displayOptions: ['left'],
        onChange: changeSpy,
        value: {displayOption: undefined, ids: [55]},
    });

    getLatestMultiItemSelectionProps().rightButton.onClick('left');

    expect(changeSpy).toBeCalledWith({displayOption: 'left', ids: [55]});
});

test('Should not call the onChange callback if the component props change', () => {
    const changeSpy = jest.fn();

    const {rerender} = renderMultiMediaSelection({
        onChange: changeSpy,
        value: {displayOption: undefined, ids: [55]},
    });

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

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function(resourceKey, selectedIds) {
        this.loadItems = jest.fn();
        this.move = jest.fn();
        this.removeById = jest.fn();
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: selectedIds.map((id) => {
                return {id, thumbnails: {}, mimeType: 'image/jpeg'};
            }),
        });
    });

    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    renderMultiMediaSelection({
        onChange: changeSpy,
        value: {displayOption: undefined, ids: [55]},
    });

    const mediaSelectionStore = getMultiSelectionStoreInstance();

    act(() => {
        mediaSelectionStore.items.push({id: 99, thumbnails: {}, mimeType: 'image/jpeg'});
    });
    expect(changeSpy).toBeCalledWith({ids: [55, 99]});
    expect(changeSpy).toHaveBeenCalledTimes(1);

    act(() => {
        unrelatedObservable.set(55);
    });
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should call the onItemClick handler if an item is clicked', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function(resourceKey, selectedIds) {
        this.loadItems = jest.fn();
        this.move = jest.fn();
        this.removeById = jest.fn();
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: selectedIds.map((id) => {
                return {id, mimeType: 'image/jpeg', thumbnails: {}};
            }),
        });
    });

    const itemClickSpy = jest.fn();

    renderMultiMediaSelection({
        onItemClick: itemClickSpy,
        value: {displayOption: undefined, ids: [55, 99]},
    });

    const mediaSelectionStore = getMultiSelectionStoreInstance();

    getLatestMultiItemSelectionProps().onItemClick(55, mediaSelectionStore.items[0]);
    expect(itemClickSpy).toHaveBeenLastCalledWith(55, {id: 55, mimeType: 'image/jpeg', thumbnails: {}});

    getLatestMultiItemSelectionProps().onItemClick(99, mediaSelectionStore.items[1]);
    expect(itemClickSpy).toHaveBeenLastCalledWith(99, {id: 99, mimeType: 'image/jpeg', thumbnails: {}});
});

test('Pass correct props to MultiItemSelection component', () => {
    renderMultiMediaSelection({disabled: true, sortable: false});

    expect(getLatestMultiItemSelectionProps().disabled).toEqual(true);
    expect(getLatestMultiItemSelectionProps().sortable).toEqual(false);
});
