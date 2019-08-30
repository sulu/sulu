// @flow
import {mount, shallow} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import React from 'react';
import ListStore from 'sulu-admin-bundle/containers/List/stores/ListStore';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.id = 1;
    this.data = {
        id: 1,
        _permissions: {},
    };
}));

jest.mock('sulu-admin-bundle/containers/List/registries/listAdapterRegistry', () => {
    return {
        getOptions: jest.fn().mockReturnValue({}),
        has: jest.fn().mockReturnValue(true),
        get: jest.fn((key) => {
            const adapters = {
                'folder': require('sulu-admin-bundle/containers/List/adapters/FolderAdapter').default,
                'media_card_selection': require('../../List/adapters/MediaCardSelectionAdapter').default,
            };
            return adapters[key];
        }),
    };
});

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () =>
    jest.fn(function(resourceKey, userSettingsKey, observableOptions) {
        mockExtendObservable(this, {
            selections: [],
            selectionIds: [],
        });
        this.observableOptions = observableOptions;
        this.pageCount = 3;
        this.active = {
            get: jest.fn(),
        };
        this.sortColumn = {
            get: jest.fn(),
        };
        this.sortOrder = {
            get: jest.fn(),
        };
        this.searchTerm = {
            get: jest.fn(),
        };
        this.limit = {
            get: jest.fn().mockReturnValue(10),
        };
        this.data = [];
        this.getPage = jest.fn().mockReturnValue(2);
        this.setPage = jest.fn();

        this.updateLoadingStrategy = jest.fn();
        this.updateStructureStrategy = jest.fn();
        this.clearSelection = jest.fn();
        this.reload = jest.fn();
        this.clear = jest.fn();
        this.getSchema = jest.fn().mockReturnValue({});
        this.options = {};
    })
);

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
    this.destroy = jest.fn();
}));

let collectionListStoreMock: ListStore;
let mediaListStoreMock: ListStore;

beforeEach(() => {
    jest.clearAllMocks();

    collectionListStoreMock = new ListStore('collections', 'collections', 'media_selection_overlay', {
        page: observable.box(),
    }, {});
    collectionListStoreMock.data.push({
        id: 1,
        title: 'Title 1',
        objectCount: 1,
        description: 'Description 1',
    },
    {
        id: 2,
        title: 'Title 2',
        objectCount: 0,
        description: 'Description 2',
    });

    mediaListStoreMock = new ListStore('media', 'media', 'media_selection_overlay', {
        page: observable.box(),
    }, {});
    mediaListStoreMock.data.push(
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails: {
                'sulu-240x': 'http://lorempixel.com/240/100',
                'sulu-25x25': 'http://lorempixel.com/25/25',
            },
        },
        {
            id: 2,
            title: 'Title 2',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails: {
                'sulu-240x': 'http://lorempixel.com/240/100',
                'sulu-25x25': 'http://lorempixel.com/25/25',
            },
        }
    );
});

test('Render an open MediaSelectionOverlay', () => {
    const locale = observable.box();
    const mediaSelectionOverlay = mount(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaSelectionOverlay.render()).toMatchSnapshot();
});

test('Render an open MediaSelectionOverlay with selected items', () => {
    mediaListStoreMock.selections.push({id: 1});

    const locale = observable.box();
    const mediaSelectionOverlay = mount(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaSelectionOverlay.render()).toMatchSnapshot();
});

test('Render the overlay with a loading confirm button', () => {
    const mediaSelectionOverlay = mount(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            confirmLoading={true}
            locale={observable.box()}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaSelectionOverlay.find('Overlay').at(0).prop('confirmLoading')).toEqual(true);
});

test('Should call onConfirm callback with selected medias from media list', () => {
    const confirmSpy = jest.fn();
    const locale = observable.box();
    const mediaSelectionOverlay = shallow(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    const selections = [
        {id: 1},
        {id: 3},
    ];
    mediaListStoreMock.selections = selections;
    mediaSelectionOverlay.find('Overlay').simulate('confirm');

    expect(confirmSpy).toBeCalledWith(selections);
});

test('Should reset the selection of the media list when the reset-button is clicked', () => {
    const locale = observable.box();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    ).instance();

    mediaSelectionOverlayInstance.handleSelectionReset();
    expect(mediaListStoreMock.clearSelection).toBeCalled();
});

test('Should reset the selection of the media list when the overlay is closed', () => {
    const locale = observable.box();
    const mediaSelectionOverlay = shallow(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    mediaSelectionOverlay.setProps({open: false});
    expect(mediaListStoreMock.clearSelection).toBeCalled();
});

test('Should change the current collection id and reset the page of the lists on collection-change', () => {
    const locale = observable.box();
    const collectionId = observable.box();
    const mediaSelectionOverlay = mount(
        <MediaSelectionOverlay
            collectionId={collectionId}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(collectionListStoreMock.setPage).not.toHaveBeenCalled();
    expect(mediaListStoreMock.setPage).not.toHaveBeenCalled();

    mediaSelectionOverlay.find('Folder').at(0).simulate('click');

    expect(collectionListStoreMock.setPage).toHaveBeenCalledWith(1);
    expect(mediaListStoreMock.setPage).toHaveBeenCalledWith(1);
    expect(collectionId.get()).toEqual(1);
    expect(mediaListStoreMock.clearSelection).not.toBeCalled();
});
