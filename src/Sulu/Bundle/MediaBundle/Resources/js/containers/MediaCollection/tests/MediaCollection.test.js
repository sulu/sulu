/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render} from 'enzyme';
import {observable} from 'mobx';
import ResourceMetadataStore from 'sulu-admin-bundle/stores/ResourceMetadataStore';
import MediaCollection from '../MediaCollection';
import MediaCardOverviewAdapter from '../../Datagrid/adapters/MediaCardOverviewAdapter';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        Form: require('sulu-admin-bundle/containers/Form').default,
        Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
        AbstractAdapter: require('sulu-admin-bundle/containers/Datagrid/adapters/AbstractAdapter').default,
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
                'sulu-240x': 'http://lorempixel.com/240/100',
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
            this.getSchema = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.updateStrategies = jest.fn();
        }),
        FlatStructureStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/structureStrategies/FlatStructureStrategy'
        ).default,
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
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

jest.mock('sulu-admin-bundle/stores/ResourceMetadataStore', () => ({
    loadConfiguration: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.destroy = jest.fn();
        this.delete = jest.fn();
        this.clone = jest.fn().mockReturnValue(this);
        this.save = jest.fn();
        this.setMultiple = jest.fn();
        this.changeSchema = jest.fn();
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
            case 'sulu_media.copy_masterfile_url':
                return 'Copy masterfile url';
            case 'sulu_media.add_collection':
                return 'Add collection';
            case 'sulu_media.edit_collection':
                return 'Edit collection';
            case 'sulu_media.remove_collection':
                return 'Remove collection';
            case 'sulu_media.remove_collection_warning':
                return 'Warning: Remove collection';
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
            case 'sulu_admin.object':
                return 'Object';
            case 'sulu_admin.objects':
                return 'Objects';
            case 'sulu_admin.ok':
                return 'ok';
            case 'sulu_admin.cancel':
                return 'cancel';
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

beforeEach(() => {
    const datagridAdapterRegistry = require('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry');

    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
        'media_card_overview': MediaCardOverviewAdapter,
    });
});

afterEach(() => document.body.innerHTML = '');

test('Render the MediaCollection', () => {
    const page = observable();
    const locale = observable();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(MEDIA_RESOURCE_KEY, {
        page,
        locale,
    });
    const collectionDatagridStore = new DatagridStore(COLLECTIONS_RESOURCE_KEY, {
        page,
        locale,
    });
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    const mediaCollection = render(
        <MediaCollection
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );
    expect(mediaCollection).toMatchSnapshot();
});

test('Should send a request to add a new collection via the overlay', () => {
    const promise = Promise.resolve();
    const page = observable();
    const locale = observable();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(MEDIA_RESOURCE_KEY, {
        page,
        locale,
    });
    const collectionDatagridStore = new DatagridStore(COLLECTIONS_RESOURCE_KEY, {
        page,
        locale,
    });
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    ResourceMetadataStore.loadConfiguration.mockReturnValue({form: {}});

    const mediaCollection = mount(
        <MediaCollection
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );

    // TODO something like Icon[name="trash"] should be possible as selector,
    // but we have to update enzyme because of https://github.com/airbnb/enzyme/issues/534
    mediaCollection.find('.collectionSection .fa-plus').simulate('click');
    expect(collectionStore.resourceStore.clone).not.toBeCalled();

    expect(mediaCollection.find('Dialog').prop('open')).toEqual(false);
    expect(mediaCollection.find('Overlay').prop('open')).toEqual(true);
    expect(document.querySelector('.content header').outerHTML).toEqual(expect.stringContaining('Add collection'));

    const newResourceStore = mediaCollection.find('CollectionSection').node.resourceStoreByOperationType;
    newResourceStore.save = jest.fn().mockReturnValue(promise);

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    document.querySelector('button.primary').click();

    return promise.then(() => {
        expect(mediaCollection.find('Overlay').prop('open')).toEqual(false);
        expect(newResourceStore.save).toBeCalled();
    });
});

test('Should send a request to update the collection via the overlay', () => {
    const promise = Promise.resolve();
    const page = observable();
    const locale = observable();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(MEDIA_RESOURCE_KEY, {
        page,
        locale,
    });
    const collectionDatagridStore = new DatagridStore(COLLECTIONS_RESOURCE_KEY, {
        page,
        locale,
    });
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.save.mockReturnValue(promise);

    ResourceMetadataStore.loadConfiguration.mockReturnValue({form: {}});

    const mediaCollection = mount(
        <MediaCollection
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );

    // TODO something like Icon[name="trash"] should be possible as selector,
    // but we have to update enzyme because of https://github.com/airbnb/enzyme/issues/534
    mediaCollection.find('.collectionSection .fa-pencil').simulate('click');
    expect(collectionStore.resourceStore.clone).toBeCalled();

    expect(mediaCollection.find('Dialog').prop('open')).toEqual(false);
    expect(mediaCollection.find('Overlay').prop('open')).toEqual(true);
    expect(document.querySelector('.content header').outerHTML).toEqual(expect.stringContaining('Edit collection'));

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    document.querySelector('button.primary').click();

    return promise.then(() => {
        expect(mediaCollection.find('Overlay').prop('open')).toEqual(false);
        expect(collectionStore.resourceStore.save).toBeCalled();
    });
});

test('Confirming the delete dialog should delete the item', () => {
    const promise = Promise.resolve();
    const page = observable();
    const locale = observable();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(MEDIA_RESOURCE_KEY, {
        page,
        locale,
    });
    const collectionDatagridStore = new DatagridStore(COLLECTIONS_RESOURCE_KEY, {
        page,
        locale,
    });
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.delete = jest.fn().mockReturnValue(promise);

    const mediaCollection = mount(
        <MediaCollection
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );

    // TODO something like Icon[name="trash"] should be possible as selector,
    // but we have to update enzyme because of https://github.com/airbnb/enzyme/issues/534
    mediaCollection.find('.fa-trash').simulate('click');

    expect(mediaCollection.find('Dialog').prop('open')).toEqual(true);
    expect(mediaCollection.find('Overlay').prop('open')).toEqual(false);

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    document.querySelector('button.primary').click();

    expect(collectionStore.resourceStore.delete).toBeCalled();

    return promise.then(() => {
        expect(collectionNavigateSpy).toBeCalledWith(null);
        expect(mediaCollection.find('Dialog').prop('open')).toEqual(false);
    });
});

test('Confirming the delete dialog should delete the item and navigate to its parent', () => {
    const promise = Promise.resolve();
    const page = observable();
    const locale = observable();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(MEDIA_RESOURCE_KEY, {
        page,
        locale,
    });
    const collectionDatagridStore = new DatagridStore(COLLECTIONS_RESOURCE_KEY, {
        page,
        locale,
    });
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.delete = jest.fn().mockReturnValue(promise);
    collectionStore.resourceStore.data = {
        id: 1,
        _embedded: {
            parent: {
                id: 3,
            },
        },
    };

    const mediaCollection = mount(
        <MediaCollection
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );

    // TODO something like Icon[name="trash"] should be possible as selector,
    // but we have to update enzyme because of https://github.com/airbnb/enzyme/issues/534
    mediaCollection.find('.fa-trash').simulate('click');

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    document.querySelector('button.primary').click();

    return promise.then(() => {
        expect(collectionNavigateSpy).toBeCalledWith(3);
    });
});
