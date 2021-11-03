// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {RequestPromise} from 'sulu-admin-bundle/services/ResourceRequester';
import MediaCardOverviewAdapter from '../../List/adapters/MediaCardOverviewAdapter';
import MediaCollection from '../MediaCollection';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';
const SETTINGS_KEY = 'media_collection_test';
const USER_SETTINGS_KEY = 'media_overview';

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () =>jest.fn(function(resourceStore) {
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
    this.types = {};
}));

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
                    thumbnails,
                },
                {
                    id: 2,
                    title: 'Title 1',
                    mimeType: 'image/jpeg',
                    size: 54321,
                    url: 'http://lorempixel.com/500/500',
                    thumbnails,
                },
            ];

            this.userSettingsKey = userSettingsKey;
            this.observableOptions = observableOptions;
            this.loading = false;
            this.pageCount = 3;
            this.filterOptions = {
                get: jest.fn().mockReturnValue({}),
            };
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
            this.reset = jest.fn();
            this.reload = jest.fn();
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
        resourceFormStoreFactory: require('sulu-admin-bundle/containers/Form/stores/resourceFormStoreFactory').default,
        memoryFormStoreFactory: {
            createFromFormKey: jest.fn(),
        },
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/List/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
        SingleListOverlay: jest.fn(() => null),
    };
});

jest.mock('sulu-admin-bundle/containers/Form/registries/fieldRegistry', () => ({
    get: jest.fn().mockReturnValue(jest.fn().mockReturnValue(null)),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/containers/List/registries/listAdapterRegistry', () => {
    const getAllAdaptersMock = jest.fn();

    return {
        getAllAdaptersMock,
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
        this.id = 1;

        mockExtendObservable(this, {
            data: {
                id: 1,
                _permissions: {},
            },
            deleting: false,
            moving: false,
            loading: false,
        });
    });

    return {
        ResourceStore: ResourceStoreMock,
    };
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
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
    MediaCollection.securable = true;

    const listAdapterRegistry = require('sulu-admin-bundle/containers/List/registries/listAdapterRegistry');

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

test('Render the MediaCollection without dropdown button when collection is a system collection', () => {
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
        locked: true,
        _permissions: {},
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

    expect(mediaCollection.find('Button[icon="su-plus"]')).toHaveLength(0);
    expect(mediaCollection.find('DropdownButton')).toHaveLength(0);
});

test('Render the MediaCollection without dropdown button when permissions are missing', () => {
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
    MediaCollection.deletable = false;
    MediaCollection.editable = false;
    MediaCollection.securable = false;

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

    expect(mediaCollection.find('Button[icon="su-plus"]')).toHaveLength(0);
    expect(mediaCollection.find('DropdownButton')).toHaveLength(0);
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
    MediaCollection.securable = true;

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
    expect(mediaCollection.find('Action').find({children: 'sulu_security.permissions'})).toHaveLength(1);
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
    MediaCollection.securable = true;

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
    expect(mediaCollection.find('Action').find({children: 'sulu_security.permissions'})).toHaveLength(1);
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
    MediaCollection.securable = true;

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
    expect(mediaCollection.find('Action').find({children: 'sulu_security.permissions'})).toHaveLength(1);
});

test('Render the MediaCollection without security buttons when permission is missing', () => {
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
    MediaCollection.editable = true;
    MediaCollection.securable = false;

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
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.edit'})).toHaveLength(1);
    expect(mediaCollection.find('Action').find({children: 'sulu_admin.move'})).toHaveLength(1);
    expect(mediaCollection.find('Action').find({children: 'sulu_security.permissions'})).toHaveLength(0);
});

test('Reload medias and fire onUploadError callback if an error happens while uploading a file', () => {
    const page = observable.box();
    const locale = observable.box();
    const onUploadErrorSpy = jest.fn();
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

    const mediaCollection = shallow(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={jest.fn()}
            onUploadError={onUploadErrorSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    expect(onUploadErrorSpy).not.toBeCalled();

    mediaCollection.find('MultiMediaDropzone').props().onUploadError(
        [
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
        ]
    );

    expect(onUploadErrorSpy).toBeCalledWith(
        [
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
        ]
    );
    expect(mediaListStore.reload).toBeCalled();
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

test('Pass correct options to SingleListOverlay for moving collections', () => {
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

    const moveCollectionOverlay = mediaCollection.find(SingleListOverlay).find('[title="sulu_media.move_collection"]');
    expect(moveCollectionOverlay.prop('listKey')).toEqual('collections');
    expect(moveCollectionOverlay.prop('resourceKey')).toEqual('collections');
    expect(moveCollectionOverlay.prop('reloadOnOpen')).toEqual(true);
});

test.each([true, false])('Pass correct hasChildren "%s" option to PermissionFormOverlay', (hasChildren) => {
    const page = observable.box();
    const locale = observable.box();
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
    mockExtendObservable(collectionStore.resourceStore.data, {
        hasChildren,
    });

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

    mediaCollection.update();
    expect(mediaCollection.find('PermissionFormOverlay').prop('hasChildren')).toEqual(hasChildren);
});

test('Pass action for uploading new media to media list', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const List = require('sulu-admin-bundle/containers').List;
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

    const uploadOverlayOpenSpy = jest.fn();

    const mediaCollection = mount(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={uploadOverlayOpenSpy}
            uploadOverlayOpen={false}
        />
    );

    const mediaListActions = mediaCollection.find(List).at(1).prop('actions');
    expect(mediaListActions).toHaveLength(1);
    expect(mediaListActions[0].label).toEqual('sulu_media.upload_file');
    expect(mediaListActions[0].onClick).toEqual(uploadOverlayOpenSpy);
    expect(mediaListActions[0].disabled).toBeFalsy();

    collectionStore.resourceStore.loading = true;
    mediaCollection.update();

    expect(mediaCollection.find(List).at(1).prop('actions')[0].disabled).toBeTruthy();
});

test('Do not pass action for uploading new media to media list if hideUploadAction prop is set to true', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const List = require('sulu-admin-bundle/containers').List;
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

    const uploadOverlayOpenSpy = jest.fn();

    const mediaCollection = mount(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            hideUploadAction={false}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={uploadOverlayOpenSpy}
            uploadOverlayOpen={false}
        />
    );

    expect(mediaCollection.find(List).at(1).prop('actions')).toHaveLength(1);

    mediaCollection.setProps({hideUploadAction: true});
    expect(mediaCollection.find(List).at(1).prop('actions')).toHaveLength(0);
});

test('Do not pass action for uploading new media to media list if addable permission is set to false', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const List = require('sulu-admin-bundle/containers').List;
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

    const mediaListActions = mediaCollection.find(List).at(1).prop('actions');
    expect(mediaListActions).toHaveLength(0);
});

test('Do not pass action for uploading new media to media list when collection is a system collection', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const List = require('sulu-admin-bundle/containers').List;
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
        locked: true,
        _permissions: {},
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

    const mediaListActions = mediaCollection.find(List).at(1).prop('actions');
    expect(mediaListActions).toHaveLength(0);
});

test('Disable dropzone if addable permission is set to false', () => {
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

    expect(mediaCollection.find('MultiMediaDropzone').prop('disabled')).toBeTruthy();
});

test('Disable dropzone when collection is loading', () => {
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

    expect(mediaCollection.find('MultiMediaDropzone').prop('disabled')).toBeFalsy();

    collectionStore.resourceStore.loading = true;
    mediaCollection.update();

    expect(mediaCollection.find('MultiMediaDropzone').prop('disabled')).toBeTruthy();
});

test('Should send a request to add a new collection via the overlay', () => {
    const fieldRegistry = require('sulu-admin-bundle/containers/Form/registries/fieldRegistry');
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
        _permissions: {},
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

    expect(mediaCollection.find('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
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
    const fieldRegistry = require('sulu-admin-bundle/containers/Form/registries/fieldRegistry');
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
        _permissions: {},
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

    expect(mediaCollection.find('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
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

    expect(mediaCollection.find('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(true);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);

    mediaCollection.find('Dialog Button[skin="primary"]').simulate('click');
    collectionStore.resourceStore.deleting = true;
    mediaCollection.update();

    expect(collectionStore.resourceStore.delete).toBeCalled();
    expect(mediaCollection.find('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(true);
    expect(mediaCollection.find('Dialog[title="sulu_media.remove_collection"]').prop('confirmLoading')).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.deleting = false;
        expect(collectionNavigateSpy).toBeCalledWith(undefined);
        mediaCollection.update();
        expect(mediaCollection.find('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
        expect(mediaCollection.find('Dialog[title="sulu_media.remove_collection"]').prop('confirmLoading'))
            .toEqual(false);
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
        _permissions: {},
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
    mediaCollection.find('Dialog Button[skin="primary"]').simulate('click');

    return promise.then(() => {
        expect(collectionNavigateSpy).toBeCalledWith(3);
    });
});

test('Confirming the move dialog should move the item', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
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
    const getMoveCollectionOverlay = () => {
        return mediaCollection.find(SingleListOverlay).find('[title="sulu_media.move_collection"]');
    };

    mediaCollection.find('DropdownButton').simulate('click');
    mediaCollection.find('DropdownButton Action').find({children: 'sulu_admin.move'}).simulate('click');

    expect(mediaCollection.find('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);

    getMoveCollectionOverlay().prop('onConfirm')({id: 7});
    collectionStore.resourceStore.moving = true;
    mediaCollection.update();

    expect(collectionStore.resourceStore.move).toBeCalledWith(7);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);
    expect(getMoveCollectionOverlay().prop('options')).toEqual({includeRoot: true});
    expect(getMoveCollectionOverlay().prop('confirmLoading')).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.moving = false;
        mediaCollection.update();
        expect(getMoveCollectionOverlay().prop('open')).toEqual(false);
        expect(getMoveCollectionOverlay().prop('confirmLoading')).toEqual(false);
        expect(collectionStore.resourceStore.reload).toBeCalledWith();
    });
});

test('Confirming the move dialog should move the item after confirming the permission dialog', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
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
    const getMoveCollectionOverlay = () => {
        return mediaCollection.find(SingleListOverlay).find('[title="sulu_media.move_collection"]');
    };

    mediaCollection.find('DropdownButton').simulate('click');
    mediaCollection.find('DropdownButton Action').find({children: 'sulu_admin.move'}).simulate('click');

    expect(mediaCollection.find('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);

    expect(
        mediaCollection.find('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
            .prop('open')
    ).toEqual(false);
    getMoveCollectionOverlay().prop('onConfirm')({id: 7, _hasPermissions: true});
    mediaCollection.update();
    expect(
        mediaCollection.find('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
            .prop('open')
    ).toEqual(true);

    mediaCollection.find('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
        .prop('onConfirm')();

    collectionStore.resourceStore.moving = true;
    mediaCollection.update();

    expect(collectionStore.resourceStore.move).toBeCalledWith(7);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);
    expect(getMoveCollectionOverlay().prop('options')).toEqual({includeRoot: true});
    expect(getMoveCollectionOverlay().prop('confirmLoading')).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.moving = false;
        mediaCollection.update();
        expect(getMoveCollectionOverlay().prop('open')).toEqual(false);
        expect(getMoveCollectionOverlay().prop('confirmLoading')).toEqual(false);
        expect(collectionStore.resourceStore.reload).toBeCalledWith();
    });
});

test('Confirming the move dialog should not move the item after denying the permission dialog', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
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
    const getMoveCollectionOverlay = () => {
        return mediaCollection.find(SingleListOverlay).find('[title="sulu_media.move_collection"]');
    };

    mediaCollection.find('DropdownButton').simulate('click');
    mediaCollection.find('DropdownButton Action').find({children: 'sulu_admin.move'}).simulate('click');

    expect(mediaCollection.find('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
    expect(mediaCollection.find('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);

    expect(
        mediaCollection.find('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
            .prop('open')
    ).toEqual(false);
    getMoveCollectionOverlay().prop('onConfirm')({id: 7, _hasPermissions: true});
    mediaCollection.update();
    expect(
        mediaCollection.find('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
            .prop('open')
    ).toEqual(true);

    mediaCollection.find('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
        .prop('onCancel')();

    mediaCollection.update();

    expect(
        mediaCollection.find('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
            .prop('open')
    ).toEqual(false);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);
    expect(getMoveCollectionOverlay().prop('confirmLoading')).toEqual(false);
});

test('Confirming the permission overlay should save the permissions', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
    const page = observable.box();
    const locale = observable.box();
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
    expect(mediaCollection.find('PermissionFormOverlay').prop('open')).toEqual(false);
    mediaCollection.find('DropdownButton Action').find({children: 'sulu_security.permissions'}).simulate('click');
    expect(mediaCollection.find('PermissionFormOverlay').prop('open')).toEqual(true);

    const savePromise = Promise.resolve();
    mediaCollection.find('PermissionFormOverlay').instance().resourceStore.save.mockReturnValue(savePromise);

    mediaCollection.find('PermissionFormOverlay Form').at(0).prop('onSubmit')();

    expect(mediaCollection.find('PermissionFormOverlay').instance().resourceStore.save)
        .toBeCalledWith({resourceKey: 'media'});

    return savePromise.then(() => {
        expect(collectionStore.resourceStore.reload).toBeCalledWith();
        mediaCollection.update();
        expect(mediaCollection.find('PermissionFormOverlay').prop('open')).toEqual(false);
    });
});
