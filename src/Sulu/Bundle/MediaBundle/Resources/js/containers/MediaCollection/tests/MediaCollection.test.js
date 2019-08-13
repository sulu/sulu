// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MediaCollection from '../MediaCollection';
import MediaCardOverviewAdapter from '../../List/adapters/MediaCardOverviewAdapter';
const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';
const SETTINGS_KEY = 'media_collection_test';
const USER_SETTINGS_KEY = 'media_overview';

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        AbstractAdapter: require('sulu-admin-bundle/containers/List/adapters/AbstractAdapter').default,
        List: require('sulu-admin-bundle/containers/List/List').default,
        ListStore: jest.fn(function(resourceKey, userSettingsKey, observableOptions) {
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
            'sulu-admin-bundle/containers/List/structureStrategies/FlatStructureStrategy'
        ).default,
        Form: require('sulu-admin-bundle/containers/Form').default,
        ResourceFormStore: jest.fn(function(resourceStore) {
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
            this.validate = jest.fn().mockReturnValue(true);
            this.destroy = jest.fn();
        }),
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/List/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
        SingleListOverlay: jest.fn(() => null),
    };
});

jest.mock('sulu-admin-bundle/containers/Form/registries/FieldRegistry', () => ({
    get: jest.fn().mockReturnValue(jest.fn().mockReturnValue(null)),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/containers/List/registries/ListAdapterRegistry', () => {
    const getAllAdaptersMock = jest.fn();

    return {
        getAllAdaptersMock: getAllAdaptersMock,
        add: jest.fn(),
        get: jest.fn((key) => getAllAdaptersMock()[key]),
        getOptions: jest.fn().mockReturnValue({}),
        has: jest.fn(),
    };
});

jest.mock('sulu-admin-bundle/stores', () => {
    const ResourceStoreMock = jest.fn(function(resourceKey) {
        this.resourceKey = resourceKey;
        this.destroy = jest.fn();
        this.delete = jest.fn();
        this.move = jest.fn();
        this.clone = jest.fn(() => {
            // $FlowFixMe
            const resourceStore = new ResourceStoreMock(resourceKey);
            resourceStore.data = this.data;
            return resourceStore;
        });
        this.save = jest.fn();
        this.setMultiple = jest.fn();
        this.changeSchema = jest.fn();
        this.load = jest.fn();
        this.reload = jest.fn();
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
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(() => null));

beforeEach(() => {
    MediaCollection.addable = true;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    const listAdapterRegistry = require('sulu-admin-bundle/containers/List/registries/ListAdapterRegistry');

    // $FlowFixMe
    listAdapterRegistry.has.mockReturnValue(true);
    // $FlowFixMe
    listAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/List/adapters/FolderAdapter').default,
        'media_card_overview': MediaCardOverviewAdapter,
    });
});

afterEach(() => {
    const body = document.body;
    if (body) {
        body.innerHTML = '';
    }
});

test('Render the MediaCollection', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    const mediaCollection = render(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );
    expect(mediaCollection).toMatchSnapshot();
});

test('Render the MediaCollection without add button when permission is missing', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = false;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    const mediaCollection = mount(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.find('DropdownButton').simulate('click');

    expect(mediaCollection.find('Button[icon="su-plus"]')).toHaveLength(0);
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.delete'})).toHaveLength(1);
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.edit'})).toHaveLength(1);
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.move'})).toHaveLength(1);
});

test('Render the MediaCollection without delete button when permission is missing', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = true;
    MediaCollection.deletable = false;
    MediaCollection.editable = true;

    const mediaCollection = mount(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.find('DropdownButton').simulate('click');

    expect(mediaCollection.find('Button[icon="su-plus"]')).toHaveLength(1);
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.delete'})).toHaveLength(0);
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.edit'})).toHaveLength(1);
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.move'})).toHaveLength(1);
});

test('Render the MediaCollection without edit buttons when permission is missing', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = true;
    MediaCollection.deletable = true;
    MediaCollection.editable = false;

    const mediaCollection = mount(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.find('DropdownButton').simulate('click');

    expect(mediaCollection.find('Button[icon="su-plus"]')).toHaveLength(1);
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.delete'})).toHaveLength(1);
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.edit'})).toHaveLength(0);
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.move'})).toHaveLength(0);
});

test('Render the MediaCollection for all media', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
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
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );
    expect(mediaCollection).toMatchSnapshot();
});

test('Pass correct options to SingleListOverlay', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    const mediaCollection = mount(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    expect(mediaCollection.find(SingleListOverlay).prop('listKey')).toEqual('collections');
    expect(mediaCollection.find(SingleListOverlay).prop('resourceKey')).toEqual('collections');
    expect(mediaCollection.find(SingleListOverlay).prop('reloadOnOpen')).toEqual(true);
});

test('Deactive dropzone by passing no collectionId if dropzone should not be shown', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = false;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    const mediaCollection = mount(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    expect(mediaCollection.find('MultiMediaDropzone').prop('collectionId')).toEqual(undefined);
});

test('Should send a request to add a new collection via the overlay', () => {
    const fieldRegistry = require('sulu-admin-bundle/containers/Form/registries/FieldRegistry');
    const promise = Promise.resolve();
    const field = jest.fn().mockReturnValue(null);
    // $FlowFixMe
    fieldRegistry.get.mockReturnValue(field);
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
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
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.find('Button[icon="su-plus"]').simulate('click');

    expect(collectionStore.resourceStore.clone).not.toBeCalled();
    expect(field.mock.calls[0][0].value).toEqual(undefined);

    expect(mediaCollection.find('CollectionSection > div > Dialog').prop('open')).toEqual(false);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(true);

    const header = document.querySelector('.content header');
    if (!header) {
        throw new Error('Header not found!');
    }
    expect(header.outerHTML).toEqual(expect.stringContaining('sulu_media.add_collection'));

    const newResourceStore = mediaCollection.find('CollectionSection').instance().resourceStoreByOperationType;
    newResourceStore.save = jest.fn().mockReturnValue(promise);

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    const button = document.querySelector('button.primary');
    if (!button) {
        throw new Error('Button not found!');
    }
    button.click();

    return promise.then(() => {
        mediaCollection.update();
        expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
        expect(newResourceStore.save).toBeCalled();
    });
});

test('Should send a request to update the collection via the overlay', () => {
    const fieldRegistry = require('sulu-admin-bundle/containers/Form/registries/FieldRegistry');
    const field = jest.fn().mockReturnValue(null);
    // $FlowFixMe
    fieldRegistry.get.mockReturnValue(field);
    const promise = Promise.resolve();
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        USER_SETTINGS_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        USER_SETTINGS_KEY,
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
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.find('DropdownButton').simulate('click');
    mediaCollection.find('DropdownButton Action').find({children: 'sulu_admin.edit'}).simulate('click');

    // $FlowFixMe
    const resourceStoreInstances = ResourceStore.mock.instances;
    const newResourceStore = resourceStoreInstances[resourceStoreInstances.length - 1];
    newResourceStore.save.mockReturnValue(promise);
    expect(collectionStore.resourceStore.clone).toBeCalled();
    expect(field.mock.calls[0][0].value).toEqual('Title');

    expect(mediaCollection.find('CollectionSection > div > Dialog').prop('open')).toEqual(false);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(true);

    const header = document.querySelector('.content header');
    if (!header) {
        throw new Error('Header not found!');
    }
    expect(header.outerHTML).toEqual(expect.stringContaining('sulu_media.edit_collection'));

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    const button = document.querySelector('button.primary');
    if (!button) {
        throw new Error('Button not found!');
    }

    button.click();

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
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        USER_SETTINGS_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        USER_SETTINGS_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    // $FlowFixMe
    collectionStore.resourceStore.delete = jest.fn().mockReturnValue(promise);

    const mediaCollection = mount(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.find('DropdownButton').simulate('click');
    mediaCollection.find('DropdownButton Action').find({children: 'sulu_admin.delete'}).simulate('click');

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
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    // $FlowFixMe
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
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.find('DropdownButton').simulate('click');
    mediaCollection.find('DropdownButton Action').find({children: 'sulu_admin.delete'}).simulate('click');

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    const button = document.querySelector('button.primary');
    if (!button) {
        throw new Error('Button not found!');
    }

    button.click();

    return promise.then(() => {
        expect(collectionNavigateSpy).toBeCalledWith(3);
    });
});

test('Confirming the move dialog should move the item', () => {
    const promise = Promise.resolve({});
    const page = observable.box();
    const locale = observable.box();
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
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
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={jest.fn()}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.find('DropdownButton').simulate('click');
    mediaCollection.find('DropdownButton Action').find({children: 'sulu_admin.move'}).simulate('click');

    expect(mediaCollection.find('CollectionSection > div > Dialog').prop('open')).toEqual(false);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
    expect(mediaCollection.find(SingleListOverlay).prop('open')).toEqual(true);

    mediaCollection.find(SingleListOverlay).prop('onConfirm')({id: 7});
    collectionStore.resourceStore.moving = true;
    mediaCollection.update();

    expect(collectionStore.resourceStore.move).toBeCalledWith(7);
    expect(mediaCollection.find(SingleListOverlay).prop('open')).toEqual(true);
    expect(mediaCollection.find(SingleListOverlay).prop('options')).toEqual({includeRoot: true});
    expect(mediaCollection.find(SingleListOverlay).prop('confirmLoading')).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.moving = false;
        mediaCollection.update();
        expect(mediaCollection.find(SingleListOverlay).prop('open')).toEqual(false);
        expect(mediaCollection.find(SingleListOverlay).prop('confirmLoading')).toEqual(false);
        expect(collectionStore.resourceStore.reload).toBeCalledWith();
    });
});
