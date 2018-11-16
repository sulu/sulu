// @flow
import {mount, render, shallow} from 'enzyme';
import pretty from 'pretty';
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MediaSelection from '../MediaSelection';
import MediaSelectionStore from '../../../stores/MediaSelectionStore';

jest.mock('../../../stores/MediaSelectionStore', () => jest.fn());

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        Form: require('sulu-admin-bundle/containers/Form').default,
        FormStore: jest.fn(),
        AbstractAdapter: require('sulu-admin-bundle/containers/Datagrid/adapters/AbstractAdapter').default,
        Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
        DatagridStore: jest.fn(function(resourceKey, observableOptions) {
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
                'sulu-240x': 'http://lorempixel.com/240/100',
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
            mockExtendObservable(this, {
                selections: [],
                selectionIds: [],
            });
            this.observableOptions = observableOptions;
            this.loading = false;
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
            this.setLimit = jest.fn();
            this.data = (resourceKey === COLLECTIONS_RESOURCE_KEY)
                ? collectionData
                : mediaData;
            this.getPage = jest.fn().mockReturnValue(2);
            this.getFields = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.updateLoadingStrategy = jest.fn();
            this.updateStructureStrategy = jest.fn();
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.deselectEntirePage = jest.fn();
            this.getSchema = jest.fn().mockReturnValue({});
        }),
        FlatStructureStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/structureStrategies/FlatStructureStrategy'
        ).default,
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
        SingleDatagridOverlay: jest.fn(() => null),
    };
});

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => {
    return {
        add: jest.fn(),
        getOptions: jest.fn().mockReturnValue({}),
        has: jest.fn(() => true),
        get: jest.fn((key) => {
            const adapters = {
                'folder': require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
                'media_card_selection': require('../../Datagrid/adapters/MediaCardSelectionAdapter').default,
            };
            return adapters[key];
        }),
    };
});

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.destroy = jest.fn();
        this.loading = false;
        this.id = 1;
        this.data = {
            id: 1,
        };
    }),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_media.all_media':
                return 'All Media';
            case 'sulu_media.copy_url':
                return 'Copy URL';
            case 'sulu_media.download_masterfile':
                return 'Download master file';
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
            case 'sulu_admin.object':
                return 'Object';
            case 'sulu_admin.objects':
                return 'Objects';
            case 'sulu_media.media_selected_singular':
                return 'media element selected';
            case 'sulu_media.media_selected_plural':
                return 'media elements selected';
            case 'sulu_media.reset_selection':
                return 'Reset fields';
            case 'sulu_media.select_media_plural':
                return 'Select media';
            case 'sulu_admin.confirm':
                return 'Confirm';
        }
    },
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
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

jest.mock('sulu-admin-bundle/containers/Datagrid/stores/DatagridStore', () => jest.fn(function() {
    this.clearSelection = jest.fn();
    this.selections = [];
}));

test('Render a MediaSelection field', () => {
    // $FlowFixMe
    MediaSelectionStore.mockImplementationOnce(function() {
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
        <MediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    )).toMatchSnapshot();
});

test('The MediaSelection should have 3 child-items', () => {
    // $FlowFixMe
    MediaSelectionStore.mockImplementationOnce(function() {
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
        <MediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    expect(mediaSelection.find('Item').length).toBe(3);
});

test('Clicking on the "add media" button should open up an overlay', () => {
    // $FlowFixMe
    MediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
    });

    const body = document.body;
    const mediaSelection = mount(<MediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    mediaSelection.find('.button.left').simulate('click');
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('Should remove media from the selection store', () => {
    // $FlowFixMe
    MediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.removeById = jest.fn();
    });

    const mediaSelectionInstance = shallow(
        <MediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    ).instance();

    mediaSelectionInstance.handleRemove(1);
    expect(mediaSelectionInstance.mediaSelectionStore.removeById).toBeCalledWith(1);
});

test('Should move media inside the selection store', () => {
    // $FlowFixMe
    MediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.move = jest.fn();
    });

    const mediaSelectionInstance = shallow(
        <MediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    ).instance();

    mediaSelectionInstance.handleSorted(1, 3);
    expect(mediaSelectionInstance.mediaSelectionStore.move).toBeCalledWith(1, 3);
});

test('Should add the selected medias to the selection store on confirm', () => {
    // $FlowFixMe
    MediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.add = jest.fn();
    });

    const thumbnails = {
        'sulu-240x': 'http://lorempixel.com/240/100',
        'sulu-25x25': 'http://lorempixel.com/25/25',
    };
    const mediaSelectionInstance = shallow(
        <MediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    ).instance();

    mediaSelectionInstance.openMediaOverlay();
    mediaSelectionInstance.handleOverlayConfirm([
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
    ]);
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[0][0].id).toBe(1);
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[0][0].title).toBe('Title 1');
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[1][0].id).toBe(2);
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[1][0].title).toBe('Title 2');
    expect(mediaSelectionInstance.overlayOpen).toBe(false);
});

test('Should call the onChange handler if selection store changes', () => {
    // $FlowFixMe
    MediaSelectionStore.mockImplementationOnce(function(selectedIds) {
        mockExtendObservable(this, {
            selectedMedia: selectedIds.map((id) => {
                return {id};
            }),
            get selectedMediaIds() {
                return this.selectedMedia.map((media) => media.id);
            },
        });
    });

    const changeSpy = jest.fn();

    const mediaSelectionInstance = shallow(
        <MediaSelection locale={observable.box('en')} onChange={changeSpy} value={{ids: [55]}} />
    ).instance();

    mediaSelectionInstance.mediaSelectionStore.selectedMedia.push({id: 99});
    expect(changeSpy).toBeCalledWith({ids: [55, 99]});

    mediaSelectionInstance.mediaSelectionStore.selectedMedia.splice(0, 1);
    expect(changeSpy).toBeCalledWith({ids: [99]});
});

test('Pass correct props to MultiItemSelection component', () => {
    // $FlowFixMe
    MediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
    });

    const mediaSelection = mount(<MediaSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} />);

    expect(mediaSelection.find('MultiItemSelection').prop('disabled')).toEqual(true);
});
