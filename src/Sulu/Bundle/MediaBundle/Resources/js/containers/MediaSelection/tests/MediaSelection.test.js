/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render, shallow} from 'enzyme';
import pretty from 'pretty';
import React from 'react';
import MediaSelection from '../MediaSelection';
import MediaCardSelectionAdapter from '../../Datagrid/adapters/MediaCardSelectionAdapter';

jest.mock('../stores/MediaSelectionStore', () => jest.fn(function() {
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
}));

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
                    title: 'Title 1',
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

jest.mock('../../MediaContainer', () => ({
    MediaContainer: require.requireActual('../../../containers/MediaContainer/MediaContainer').default,
    CollectionInfoStore: jest.fn(),
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
        folder: {
            Adapter: require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
            paginationType: 'default',
        },
        mediaCardSelection: {
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
    expect(render(
        <MediaSelection />
    )).toMatchSnapshot();
});

test('The MediaSelection should have 3 child-items', () => {
    const mediaSelection = shallow(
        <MediaSelection />
    );

    expect(mediaSelection.find('Item').length).toBe(3);
});

test('Clicking on the "add media" button should open up an overlay', () => {
    const body = document.body;
    const mediaSelection = mount(<MediaSelection />);

    mediaSelection.find('.button.left').simulate('click');
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});
