/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import ResourceMetadataStore from 'sulu-admin-bundle/stores/ResourceMetadataStore';
import MediaCollection from '../MediaCollection';
import MediaCardOverviewAdapter from '../../Datagrid/adapters/MediaCardOverviewAdapter';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';
const SETTINGS_KEY = 'media_collection_test';

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        AbstractAdapter: require('sulu-admin-bundle/containers/Datagrid/adapters/AbstractAdapter').default,
        Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
        DatagridStore: jest.fn(function(resourceKey, userSettingsKey, observableOptions) {
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

            this.userSettingsKey = userSettingsKey;
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
            this.selections = [];
            this.selectionIds = [];
            this.getPage = jest.fn().mockReturnValue(2);
            this.getSchema = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.updateLoadingStrategy = jest.fn();
            this.updateStructureStrategy = jest.fn();
        }),
        FlatStructureStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/structureStrategies/FlatStructureStrategy'
        ).default,
        Form: require('sulu-admin-bundle/containers/Form').default,
        FormStore: jest.fn(function(resourceStore) {
            switch (resourceStore.resourceKey) {
                case 'collections':
                    this.schema = {
                        title: {
                            type: 'text_line',
                        },
                        description: {
                            type: 'text_line',
                        },
                    };
                    break;
                default:
                    this.schema = {};
            }

            this.data = resourceStore.data;
            this.isFieldModified = jest.fn();
        }),
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
        SingleDatagridOverlay: jest.fn(() => null),
    };
});

jest.mock('sulu-admin-bundle/containers/Form/registries/FieldRegistry', () => ({
    get: jest.fn().mockReturnValue(jest.fn().mockReturnValue(null)),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => {
    const getAllAdaptersMock = jest.fn();

    return {
        getAllAdaptersMock: getAllAdaptersMock,
        add: jest.fn(),
        get: jest.fn((key) => getAllAdaptersMock()[key]),
        getOptions: jest.fn().mockReturnValue({}),
        has: jest.fn(),
    };
});

jest.mock('sulu-admin-bundle/stores/ResourceMetadataStore', () => ({
    loadConfiguration: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores', () => {
    const ResourceStoreMock = jest.fn(function(resourceKey) {
        this.resourceKey = resourceKey;
        this.destroy = jest.fn();
        this.delete = jest.fn();
        this.move = jest.fn();
        this.clone = jest.fn(() => {
            const resourceStore = new ResourceStoreMock(resourceKey);
            resourceStore.data = this.data;
            return resourceStore;
        });
        this.save = jest.fn();
        this.setMultiple = jest.fn();
        this.changeSchema = jest.fn();
        this.load = jest.fn();
        this.loading = false;
        this.id = 1;
        this.data = {
            id: 1,
        };

        mockExtendObservable(this, {
            deleting: false,
            moving: false,
        });
    });

    return {
        ResourceStore: ResourceStoreMock,
    };
});

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

jest.mock('sulu-admin-bundle/containers/SingleDatagridOverlay', () => jest.fn(() => null));

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
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionDatagridStore = new DatagridStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    const mediaCollection = render(
        <MediaCollection
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );
    expect(mediaCollection).toMatchSnapshot();
});

test('Render the MediaCollection for all media', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionDatagridStore = new DatagridStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(undefined, locale);
    collectionStore.resourceStore.id = undefined;

    const mediaCollection = render(
        <MediaCollection
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );
    expect(mediaCollection).toMatchSnapshot();
});

test('Pass correct options to SingleDatagridOverlay', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const SingleDatagridOverlay = require('sulu-admin-bundle/containers').SingleDatagridOverlay;
    const mediaDatagridStore = new DatagridStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionDatagridStore = new DatagridStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    const mediaCollection = mount(
        <MediaCollection
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );

    expect(mediaCollection.find(SingleDatagridOverlay).prop('reloadOnOpen')).toEqual(true);
});

test('Should send a request to add a new collection via the overlay', () => {
    const fieldRegistry = require('sulu-admin-bundle/containers/Form/registries/FieldRegistry');
    const promise = Promise.resolve();
    const field = jest.fn().mockReturnValue(null);
    fieldRegistry.get.mockReturnValue(field);
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionDatagridStore = new DatagridStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.data = {
        title: 'Title',
    };

    ResourceMetadataStore.loadConfiguration.mockReturnValue({form: {}});

    const mediaCollection = mount(
        <MediaCollection
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );

    mediaCollection.find('CollectionSection Icon[name="su-plus"]').simulate('click');
    expect(collectionStore.resourceStore.clone).not.toBeCalled();
    expect(field.mock.calls[0][0].value).toEqual(undefined);

    expect(mediaCollection.find('CollectionSection > div > Dialog').prop('open')).toEqual(false);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(true);
    expect(document.querySelector('.content header').outerHTML).toEqual(expect.stringContaining('Add collection'));

    const newResourceStore = mediaCollection.find('CollectionSection').instance().resourceStoreByOperationType;
    newResourceStore.save = jest.fn().mockReturnValue(promise);

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    document.querySelector('button.primary').click();

    return promise.then(() => {
        mediaCollection.update();
        expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
        expect(newResourceStore.save).toBeCalled();
    });
});

test('Should send a request to update the collection via the overlay', () => {
    const fieldRegistry = require('sulu-admin-bundle/containers/Form/registries/FieldRegistry');
    const field = jest.fn().mockReturnValue(null);
    fieldRegistry.get.mockReturnValue(field);
    const promise = Promise.resolve();
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionDatagridStore = new DatagridStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.data = {
        title: 'Title',
    };

    const mediaCollection = mount(
        <MediaCollection
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );

    mediaCollection.find('CollectionSection Icon[name="su-pen"]').simulate('click');

    const resourceStoreInstances = ResourceStore.mock.instances;
    const newResourceStore = resourceStoreInstances[resourceStoreInstances.length - 1];
    newResourceStore.save.mockReturnValue(promise);
    expect(collectionStore.resourceStore.clone).toBeCalled();
    expect(field.mock.calls[0][0].value).toEqual('Title');

    expect(mediaCollection.find('CollectionSection > div > Dialog').prop('open')).toEqual(false);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(true);
    expect(document.querySelector('.content header').outerHTML).toEqual(expect.stringContaining('Edit collection'));

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    document.querySelector('button.primary').click();

    return promise.then(() => {
        mediaCollection.update();
        expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
        expect(newResourceStore.save).toBeCalledWith({breadcrumb: true});
    });
});

test('Confirming the delete dialog should delete the item', () => {
    const promise = Promise.resolve();
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionDatagridStore = new DatagridStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.delete = jest.fn().mockReturnValue(promise);

    const mediaCollection = mount(
        <MediaCollection
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );

    mediaCollection.find('Icon[name="su-trash-alt"]').simulate('click');

    expect(mediaCollection.find('CollectionSection > div > Dialog').prop('open')).toEqual(true);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);

    mediaCollection.find('Dialog Button[skin="primary"]').simulate('click');
    collectionStore.resourceStore.deleting = true;
    mediaCollection.update();

    expect(collectionStore.resourceStore.delete).toBeCalled();
    expect(mediaCollection.find('CollectionSection > div > Dialog').prop('open')).toEqual(true);
    expect(mediaCollection.find('CollectionSection > div > Dialog').prop('confirmLoading')).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.deleting = false;
        expect(collectionNavigateSpy).toBeCalledWith(undefined);
        mediaCollection.update();
        expect(mediaCollection.find('CollectionSection > div > Dialog').prop('open')).toEqual(false);
        expect(mediaCollection.find('CollectionSection > div > Dialog').prop('confirmLoading')).toEqual(false);
    });
});

test('Confirming the delete dialog should delete the item and navigate to its parent', () => {
    const promise = Promise.resolve();
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionDatagridStore = new DatagridStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.delete = jest.fn().mockImplementationOnce(() => {
        collectionStore.resourceStore.data = {};
        return promise;
    });

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
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            onCollectionNavigate={collectionNavigateSpy}
        />
    );

    mediaCollection.find('Icon[name="su-trash-alt"]').simulate('click');

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    document.querySelector('button.primary').click();

    return promise.then(() => {
        expect(collectionNavigateSpy).toBeCalledWith(3);
    });
});

test('Confirming the move dialog should move the item', () => {
    const promise = Promise.resolve();
    const page = observable.box();
    const locale = observable.box();
    const SingleDatagridOverlay = require('sulu-admin-bundle/containers').SingleDatagridOverlay;
    const DatagridStore = require('sulu-admin-bundle/containers').DatagridStore;
    const mediaDatagridStore = new DatagridStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionDatagridStore = new DatagridStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.move = jest.fn().mockReturnValue(promise);

    const mediaCollection = mount(
        <MediaCollection
            collectionDatagridStore={collectionDatagridStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaDatagridAdapters={['media_card_overview']}
            mediaDatagridStore={mediaDatagridStore}
            onCollectionNavigate={jest.fn()}
        />
    );

    mediaCollection.find('Icon[name="su-arrows-alt"]').simulate('click');

    expect(mediaCollection.find('CollectionSection > div > Dialog').prop('open')).toEqual(false);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
    expect(mediaCollection.find(SingleDatagridOverlay).prop('open')).toEqual(true);

    mediaCollection.find(SingleDatagridOverlay).prop('onConfirm')({id: 7});
    collectionStore.resourceStore.moving = true;
    mediaCollection.update();

    expect(collectionStore.resourceStore.move).toBeCalledWith(7);
    expect(mediaCollection.find(SingleDatagridOverlay).prop('open')).toEqual(true);
    expect(mediaCollection.find(SingleDatagridOverlay).prop('options')).toEqual({includeRoot: true});
    expect(mediaCollection.find(SingleDatagridOverlay).prop('confirmLoading')).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.moving = false;
        mediaCollection.update();
        expect(mediaCollection.find(SingleDatagridOverlay).prop('open')).toEqual(false);
        expect(mediaCollection.find(SingleDatagridOverlay).prop('confirmLoading')).toEqual(false);
        expect(collectionStore.resourceStore.load).toBeCalledWith();
    });
});
