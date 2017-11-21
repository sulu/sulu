/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render, shallow} from 'enzyme';
import pretty from 'pretty';
import React from 'react';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import MediaSelection from '../MediaSelection';
import MediaCardSelectionAdapter from '../../Datagrid/adapters/MediaCardSelectionAdapter';
import MediaSelectionStore from '../stores/MediaSelectionStore';
import CollectionStore from '../../../stores/CollectionStore';

jest.mock('../stores/MediaSelectionStore', () => jest.fn());

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
        DatagridStore: jest.fn(function(resourceKey) {
            const COLLECTIONS_RESOURCE_KEY = 'collections';

            const collectionData = [
                {
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
                },
            ];

            const thumbnails = {
                'sulu-260x': 'http://lorempixel.com/260/100',
                'sulu-25x25': 'http://lorempixel.com/25/25',
            };

            const mediaData = [
                {
                    id: 1,
                    title: 'Title 1',
                    mimeType: 'image/png',
                    size: 12345,
                    url: 'http://lorempixel.com/500/500',
                    thumbnails: thumbnails,
                },
                {
                    id: 2,
                    title: 'Title 2',
                    mimeType: 'image/jpeg',
                    size: 54321,
                    url: 'http://lorempixel.com/500/500',
                    thumbnails: thumbnails,
                },
            ];

            this.loading = false;
            this.pageCount = 3;
            this.data = (resourceKey === COLLECTIONS_RESOURCE_KEY)
                ? collectionData
                : mediaData;
            this.selections = [];
            this.getPage = jest.fn().mockReturnValue(2);
            this.getFields = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.setAppendRequestData = jest.fn();
            this.deselectEntirePage = jest.fn();
            this.getSchema = jest.fn().mockReturnValue({});
        }),
    };
});

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => {
    const getAllAdaptersMock = jest.fn();

    return {
        getAllAdaptersMock: getAllAdaptersMock,
        add: jest.fn(),
        get: jest.fn((key) => getAllAdaptersMock()[key]),
        has: jest.fn(),
    };
});

jest.mock('../../../stores/CollectionStore', () => jest.fn(function() {
    this.destroy = jest.fn();
}));

jest.mock('sulu-admin-bundle/services', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_media.all_media':
                return 'All Media';
            case 'sulu_media.copy_url':
                return 'Copy URL';
            case 'sulu_media.download_masterfile':
                return 'Downoad master file';
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
            case 'sulu_admin.object':
                return 'Object';
            case 'sulu_admin.objects':
                return 'Objects';
        }
    },
}));

beforeEach(() => {
    const datagridAdapterRegistry = require('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry');

    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': {
            Adapter: require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
            paginationType: 'default',
        },
        'media_card_selection': {
            Adapter: MediaCardSelectionAdapter,
            paginationType: 'infiniteScroll',
        },
    });
});

jest.mock('sulu-admin-bundle/services/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
            case 'sulu_admin.object':
                return 'Object';
            case 'sulu_admin.objects':
                return 'Objects';
        }
    },
}));

jest.mock('sulu-admin-bundle/services', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_media.reset_selection':
                return 'Reset fields';
            case 'sulu_media.select_media':
                return 'Select media';
            case 'sulu_admin.confirm':
                return 'Confirm';
        }
    },
}));

test('Render a MediaSelection field', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [
            {
                id: 1,
                title: 'Media 1',
                thumbnail: 'http://lorempixel.com/25/25',
            },
            {
                id: 2,
                title: 'Media 2',
                thumbnail: 'http://lorempixel.com/25/25',
            },
            {
                id: 3,
                title: 'Media 3',
                thumbnail: 'http://lorempixel.com/25/25',
            },
        ];
        this.selectedMediaIds = [1, 2, 3];
    });

    expect(render(
        <MediaSelection />
    )).toMatchSnapshot();
});

test('The MediaSelection should have 3 child-items', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [
            {
                id: 1,
                title: 'Media 1',
                thumbnail: 'http://lorempixel.com/25/25',
            },
            {
                id: 2,
                title: 'Media 2',
                thumbnail: 'http://lorempixel.com/25/25',
            },
            {
                id: 3,
                title: 'Media 3',
                thumbnail: 'http://lorempixel.com/25/25',
            },
        ];
        this.selectedMediaIds = [1, 2, 3];
    });

    const mediaSelection = shallow(
        <MediaSelection />
    );

    expect(mediaSelection.find('Item').length).toBe(3);
});

test('Clicking on the "add media" button should open up an overlay', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
    });

    const body = document.body;
    const mediaSelection = mount(<MediaSelection />);

    mediaSelection.find('.button.left').simulate('click');
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('Should instantiate the needed stores when the overlay opens', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
    });

    const locale = 'en';
    const mediaResourceKey = 'media';
    const collectionResourceKey = 'collections';
    const mediaSelectionInstance = shallow(<MediaSelection locale={locale} />).instance();

    mediaSelectionInstance.openMediaOverlay();
    expect(mediaSelectionInstance.mediaPage.get()).toBe(1);
    expect(mediaSelectionInstance.collectionPage.get()).toBe(1);

    expect(CollectionStore.mock.calls[0][0]).toBe(undefined);
    expect(CollectionStore.mock.calls[0][1]).toBe(locale);

    expect(DatagridStore.mock.calls[0][0]).toBe(mediaResourceKey);
    expect(DatagridStore.mock.calls[0][1].locale).toBe(locale);
    expect(DatagridStore.mock.calls[0][1].page.get()).toBe(1);
    expect(DatagridStore.mock.calls[0][2].fields).toEqual([
        'id',
        'type',
        'name',
        'size',
        'title',
        'mimeType',
        'subVersion',
        'thumbnails',
    ].join(','));
    expect(DatagridStore.mock.calls[0][3]).toBe(true);
    expect(typeof DatagridStore.mock.calls[0][4]).toBe('function');
    expect(DatagridStore.mock.calls[0][5]).toEqual([]);

    expect(DatagridStore.mock.calls[1][0]).toBe(collectionResourceKey);
    expect(DatagridStore.mock.calls[1][1].locale).toBe(locale);
    expect(DatagridStore.mock.calls[1][1].page.get()).toBe(1);
});

test('Should add and remove media ids', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
    });

    const mediaSelectionInstance = shallow(<MediaSelection />).instance();

    expect(mediaSelectionInstance.selectedMediaIds).toEqual([]);

    mediaSelectionInstance.handleMediaSelection(1, true);
    mediaSelectionInstance.handleMediaSelection(2, true);
    mediaSelectionInstance.handleMediaSelection(3, true);
    expect(mediaSelectionInstance.selectedMediaIds).toEqual([1, 2, 3]);

    mediaSelectionInstance.handleMediaSelection(2, false);
    expect(mediaSelectionInstance.selectedMediaIds).toEqual([1, 3]);
});

test('Should remove media from the selection', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.removeById = jest.fn();
    });

    const changeSpy = jest.fn();
    const mediaSelectionInstance = shallow(<MediaSelection onChange={changeSpy} />).instance();

    mediaSelectionInstance.handleMediaRemove(1);
    expect(changeSpy).toBeCalled();
    expect(mediaSelectionInstance.mediaSelectionStore.removeById).toBeCalledWith(1);
});

test('Should move media inside the selection', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.move = jest.fn();
    });

    const changeSpy = jest.fn();
    const mediaSelectionInstance = shallow(<MediaSelection onChange={changeSpy} />).instance();

    mediaSelectionInstance.handleMediaSorted(1, 3);
    expect(changeSpy).toBeCalled();
    expect(mediaSelectionInstance.mediaSelectionStore.move).toBeCalledWith(1, 3);
});

test('Should reset the selection array and add the selected medias to the selection store on confirm', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.add = jest.fn();
    });

    const changeSpy = jest.fn();
    const mediaSelectionInstance = shallow(<MediaSelection onChange={changeSpy} />).instance();

    mediaSelectionInstance.openMediaOverlay();
    mediaSelectionInstance.handleMediaSelection(1, true);
    mediaSelectionInstance.handleMediaSelection(2, true);
    expect(mediaSelectionInstance.selectedMediaIds).toEqual([1, 2]);

    mediaSelectionInstance.handleOverlayConfirm();
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[0][0].id).toBe(1);
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[0][0].title).toBe('Title 1');
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[1][0].id).toBe(2);
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[1][0].title).toBe('Title 2');
    expect(changeSpy).toBeCalled();
    expect(mediaSelectionInstance.overlayOpen).toBe(false);
    expect(mediaSelectionInstance.selectedMediaIds).toEqual([]);
});

test('Should reset the selection array when the "Reset Selection" button was clicked', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
    });

    const changeSpy = jest.fn();
    const mediaSelectionInstance = shallow(<MediaSelection onChange={changeSpy} />).instance();

    mediaSelectionInstance.openMediaOverlay();
    mediaSelectionInstance.handleMediaSelection(1, true);
    mediaSelectionInstance.handleMediaSelection(2, true);
    expect(mediaSelectionInstance.selectedMediaIds).toEqual([1, 2]);

    mediaSelectionInstance.handleSelectionReset();
    expect(mediaSelectionInstance.selectedMediaIds).toEqual([]);
    expect(mediaSelectionInstance.mediaDatagridStore.deselectEntirePage).toBeCalled();
});

test('Should destroy the stores and cleanup all states when the overlay is closed', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
    });

    const changeSpy = jest.fn();
    const mediaSelectionInstance = shallow(<MediaSelection onChange={changeSpy} />).instance();

    mediaSelectionInstance.openMediaOverlay();
    mediaSelectionInstance.handleMediaSelection(1, true);
    mediaSelectionInstance.handleMediaSelection(2, true);
    mediaSelectionInstance.setCollectionId(1);

    expect(mediaSelectionInstance.collectionId).toBe(1);
    expect(mediaSelectionInstance.selectedMediaIds).toEqual([1, 2]);

    mediaSelectionInstance.closeMediaOverlay();
    expect(mediaSelectionInstance.collectionId).toBe(undefined);
    expect(mediaSelectionInstance.selectedMediaIds).toEqual([]);
    expect(mediaSelectionInstance.collectionStore.destroy).toBeCalled();
    expect(mediaSelectionInstance.mediaDatagridStore.destroy).toBeCalled();
    expect(mediaSelectionInstance.collectionDatagridStore.destroy).toBeCalled();
});
