/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import {observable} from 'mobx';
import MediaCollection from '../MediaCollection';
import MediaCardOverviewAdapter from '../../Datagrid/adapters/MediaCardOverviewAdapter';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';

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
                'sulu-100x100': 'http://lorempixel.com/100/100',
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

jest.mock('../stores/CollectionInfoStore', () => {
    return jest.fn();
});

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

beforeEach(() => {
    const datagridAdapterRegistry = require('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry');

    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        folder: {
            Adapter: require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
            paginationType: 'pagination',
        },
        mediaCardOverview: {
            Adapter: MediaCardOverviewAdapter,
            paginationType: 'infiniteScroll',
        },
    });
});

test('Render the MediaCollection', () => {
    const page = observable();
    const locale = observable();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaStore = new DatagridStore(MEDIA_RESOURCE_KEY, {
        page,
        locale,
    });
    const collectionStore = new DatagridStore(COLLECTIONS_RESOURCE_KEY, {
        page,
        locale,
    });
    const CollectionInfoStore = require('../stores/CollectionInfoStore');
    const collectionInfoStore = new CollectionInfoStore(1, locale);

    const mediaCollection = render(
        <MediaCollection
            page={page}
            locale={locale}
            mediaViews={['mediaCardOverview']}
            mediaStore={mediaStore}
            collectionStore={collectionStore}
            collectionInfoStore={collectionInfoStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );
    expect(mediaCollection).toMatchSnapshot();
});
