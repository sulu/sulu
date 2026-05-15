// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import {List, SingleListOverlay} from 'sulu-admin-bundle/containers';
import {Dialog} from 'sulu-admin-bundle/components';
import {RequestPromise} from 'sulu-admin-bundle/services/ResourceRequester';
import MediaCardOverviewAdapter from '../../List/adapters/MediaCardOverviewAdapter';
import MultiMediaDropzone from '../../MultiMediaDropzone';
import CollectionFormOverlay from '../CollectionFormOverlay';
import MediaCollection from '../MediaCollection';
import PermissionFormOverlay from '../PermissionFormOverlay';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';
const SETTINGS_KEY = 'media_collection_test';
const USER_SETTINGS_KEY = 'media_overview';

const getListProps = (index: number) => {
    const {calls} = (List: any).mock;
    return calls[calls.length - 2 + index][0];
};
const getMediaListProps = () => getListProps(1);
const getLatestMultiMediaDropzoneProps = () => getLatestMockProps((MultiMediaDropzone: any));
const getLatestCollectionFormOverlayProps = () => getLatestMockProps((CollectionFormOverlay: any));
const getLatestPermissionFormOverlayProps = () => getLatestMockProps((PermissionFormOverlay: any));
const getDialogProps = (title: string) => {
    const call = [...(Dialog: any).mock.calls].reverse().find((args) => args[0].title === title);
    return call && call[0];
};
const getSingleListOverlayProps = (title: string) => {
    const call = [...(SingleListOverlay: any).mock.calls].reverse().find((args) => args[0].title === title);
    return call && call[0];
};

const openCollectionActions = async(user) => {
    await user.click(screen.getByRole('button', {name: /su-cog/}));
};

const clickCollectionAction = async(user, label: string) => {
    await openCollectionActions(user);
    await user.click(screen.getByRole('button', {name: label}));
};

const renderMediaCollection = (element: React$Element<any>) => {
    let currentProps = element.props || {};
    const createElement = () => React.cloneElement(element, currentProps);
    const view = render(createElement());

    return {
        ...view,
        rerenderWithProps: (nextProps: Object) => {
            currentProps = {...currentProps, ...nextProps};
            act(() => {
                view.rerender(createElement());
            });
        },
        rerenderCollection: () => {
            act(() => {
                view.rerender(createElement());
            });
        },
    };
};
const renderOnce = (element: React$Element<any>) => {
    const view = render(element);
    const renderedOutput = view.container.firstChild;
    view.unmount();
    return renderedOutput;
};

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
        List: jest.fn(() => null),
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
            createFromFormKey: jest.fn(() => ({
                data: {},
                destroy: jest.fn(),
            })),
        },
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/List/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
        SingleListOverlay: jest.fn(() => null),
    };
});

jest.mock('sulu-admin-bundle/components', () => ({
    ...jest.requireActual('sulu-admin-bundle/components'),
    Dialog: jest.fn(() => null),
}));

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
        this.set = jest.fn();
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

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(() => null));

jest.mock('../../MultiMediaDropzone', () => jest.fn((props) => {
    const React = require('react');
    return React.createElement('div', {}, props.children);
}));

jest.mock('../CollectionFormOverlay', () => jest.fn(() => null));

jest.mock('../PermissionFormOverlay', () => jest.fn(() => null));

beforeEach(() => {
    jest.clearAllMocks();

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

    const mediaCollection = renderOnce(
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

    renderMediaCollection(
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

    expect(screen.queryByText('sulu_media.add_collection')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'su-cog'})).not.toBeInTheDocument();
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

    renderMediaCollection(
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

    expect(screen.queryByText('sulu_media.add_collection')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'su-cog'})).not.toBeInTheDocument();
});

test('Render the MediaCollection without add button when permission is missing', async() => {
    const user = userEvent.setup();
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

    renderMediaCollection(
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

    await openCollectionActions(user);

    expect(screen.queryByText('sulu_media.add_collection')).not.toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.edit'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_security.permissions'})).toBeInTheDocument();
});

test('Render the MediaCollection without delete button when permission is missing', async() => {
    const user = userEvent.setup();
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

    renderMediaCollection(
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

    await openCollectionActions(user);

    expect(screen.getByText('sulu_media.add_collection')).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_admin.delete'})).not.toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.edit'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_security.permissions'})).toBeInTheDocument();
});

test('Render the MediaCollection without edit buttons when permission is missing', async() => {
    const user = userEvent.setup();
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

    renderMediaCollection(
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

    await openCollectionActions(user);

    expect(screen.getByText('sulu_media.add_collection')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_admin.edit'})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_admin.move'})).not.toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_security.permissions'})).toBeInTheDocument();
});

test('Render the MediaCollection without security buttons when permission is missing', async() => {
    const user = userEvent.setup();
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

    renderMediaCollection(
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

    await openCollectionActions(user);

    expect(screen.getByText('sulu_media.add_collection')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.edit'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_security.permissions'})).not.toBeInTheDocument();
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

    renderMediaCollection(
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

    getLatestMultiMediaDropzoneProps().onUploadError(
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

    const mediaCollection = renderOnce(
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

    renderMediaCollection(
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

    const moveCollectionOverlay = getSingleListOverlayProps('sulu_media.move_collection');
    expect(moveCollectionOverlay.listKey).toEqual('collections');
    expect(moveCollectionOverlay.resourceKey).toEqual('collections');
    expect(moveCollectionOverlay.reloadOnOpen).toEqual(true);
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

    const mediaCollection = renderMediaCollection(
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

    mediaCollection.rerenderCollection();
    expect(getLatestPermissionFormOverlayProps().hasChildren).toEqual(hasChildren);
});

test('Pass action for uploading new media to media list', () => {
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

    const uploadOverlayOpenSpy = jest.fn();

    const mediaCollection = renderMediaCollection(
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

    const mediaListActions = getMediaListProps().actions;
    expect(mediaListActions).toHaveLength(1);
    expect(mediaListActions[0].label).toEqual('sulu_media.upload_file');
    expect(mediaListActions[0].onClick).toEqual(uploadOverlayOpenSpy);
    expect(mediaListActions[0].disabled).toBeFalsy();

    collectionStore.resourceStore.loading = true;
    mediaCollection.rerenderCollection();

    expect(getMediaListProps().actions[0].disabled).toBeTruthy();
});

test('Do not pass action for uploading new media to media list if hideUploadAction prop is set to true', () => {
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

    const uploadOverlayOpenSpy = jest.fn();

    const mediaCollection = renderMediaCollection(
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

    expect(getMediaListProps().actions).toHaveLength(1);

    mediaCollection.rerenderWithProps({hideUploadAction: true});
    expect(getMediaListProps().actions).toHaveLength(0);
});

test('Do not pass action for uploading new media to media list if addable permission is set to false', () => {
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

    renderMediaCollection(
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

    const mediaListActions = getMediaListProps().actions;
    expect(mediaListActions).toHaveLength(0);
});

test('Do not pass action for uploading new media to media list when collection is a system collection', () => {
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

    renderMediaCollection(
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

    const mediaListActions = getMediaListProps().actions;
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

    renderMediaCollection(
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

    expect(getLatestMultiMediaDropzoneProps().disabled).toBeTruthy();
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

    const mediaCollection = renderMediaCollection(
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

    expect(getLatestMultiMediaDropzoneProps().disabled).toBeFalsy();

    collectionStore.resourceStore.loading = true;
    mediaCollection.rerenderCollection();

    expect(getLatestMultiMediaDropzoneProps().disabled).toBeTruthy();
});

test('Should send a request to add a new collection via the overlay', async() => {
    const user = userEvent.setup();
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
    collectionStore.resourceStore.data = {
        title: 'Title',
        _permissions: {},
    };

    const mediaCollection = renderMediaCollection(
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

    await user.click(screen.getByText('sulu_media.add_collection'));

    expect(collectionStore.resourceStore.clone).not.toBeCalled();
    expect(getDialogProps('sulu_media.remove_collection').open).toEqual(false);
    expect(getLatestCollectionFormOverlayProps().operationType).toEqual('create');

    const newResourceStore = getLatestCollectionFormOverlayProps().resourceStore;
    expect(newResourceStore.data.title).toEqual(undefined);
    newResourceStore.save = jest.fn().mockReturnValue(promise);

    act(() => {
        getLatestCollectionFormOverlayProps().onConfirm(newResourceStore);
    });

    return promise.then(() => {
        mediaCollection.rerenderCollection();
        expect(getLatestCollectionFormOverlayProps().operationType).toEqual(null);
        expect(newResourceStore.save).toHaveBeenCalledWith({
            breadcrumb: true,
        });
        expect(newResourceStore.set).toHaveBeenCalledWith('parent', 1);
        expect(collectionNavigateSpy).toBeCalled();
        expect(collectionStore.resourceStore.setMultiple).not.toBeCalled();
    });
});

test('Should send a request to update the collection via the overlay', async() => {
    const user = userEvent.setup();
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

    const mediaCollection = renderMediaCollection(
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

    await clickCollectionAction(user, 'sulu_admin.edit');

    // $FlowFixMe
    const resourceStoreInstances = ResourceStore.mock.instances;
    const newResourceStore = resourceStoreInstances[resourceStoreInstances.length - 1];
    newResourceStore.save.mockReturnValue(promise);
    expect(collectionStore.resourceStore.clone).toBeCalled();

    expect(getDialogProps('sulu_media.remove_collection').open).toEqual(false);
    expect(getLatestCollectionFormOverlayProps().operationType).toEqual('update');
    expect(getLatestCollectionFormOverlayProps().resourceStore.data.title).toEqual('Title');

    act(() => {
        getLatestCollectionFormOverlayProps().onConfirm(newResourceStore);
    });

    return promise.then(() => {
        mediaCollection.rerenderCollection();
        expect(getLatestCollectionFormOverlayProps().operationType).toEqual(null);
        expect(newResourceStore.save).toBeCalledWith({breadcrumb: true});
        expect(collectionNavigateSpy).not.toBeCalled();
        expect(collectionStore.resourceStore.setMultiple).toBeCalled();
    });
});

test('Confirming the delete dialog should delete the item', async() => {
    const user = userEvent.setup();
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

    const mediaCollection = renderMediaCollection(
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

    await clickCollectionAction(user, 'sulu_admin.delete');

    expect(getDialogProps('sulu_media.remove_collection').open).toEqual(true);
    expect(getLatestCollectionFormOverlayProps().operationType).toEqual('remove');

    act(() => {
        getDialogProps('sulu_media.remove_collection').onConfirm();
    });
    collectionStore.resourceStore.deleting = true;
    mediaCollection.rerenderCollection();

    expect(collectionStore.resourceStore.delete).toBeCalled();
    expect(getDialogProps('sulu_media.remove_collection').open).toEqual(true);
    expect(getDialogProps('sulu_media.remove_collection').confirmLoading).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.deleting = false;
        expect(collectionNavigateSpy).toBeCalledWith(undefined);
        mediaCollection.rerenderCollection();
        expect(getDialogProps('sulu_media.remove_collection').open).toEqual(false);
        expect(getDialogProps('sulu_media.remove_collection').confirmLoading)
            .toEqual(false);
    });
});

test('Confirming the delete dialog should delete the item and navigate to its parent', async() => {
    const user = userEvent.setup();
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

    renderMediaCollection(
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

    await clickCollectionAction(user, 'sulu_admin.delete');
    act(() => {
        getDialogProps('sulu_media.remove_collection').onConfirm();
    });

    return promise.then(() => {
        expect(collectionNavigateSpy).toBeCalledWith(3);
    });
});

test('Confirming the move dialog should move the item', async() => {
    const user = userEvent.setup();
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

    const mediaCollection = renderMediaCollection(
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
        return getSingleListOverlayProps('sulu_media.move_collection');
    };

    await clickCollectionAction(user, 'sulu_admin.move');

    expect(getDialogProps('sulu_media.remove_collection').open).toEqual(false);
    expect(getLatestCollectionFormOverlayProps().operationType).toEqual('move');
    expect(getMoveCollectionOverlay().open).toEqual(true);

    act(() => {
        getMoveCollectionOverlay().onConfirm({id: 7});
    });
    collectionStore.resourceStore.moving = true;
    mediaCollection.rerenderCollection();

    expect(collectionStore.resourceStore.move).toBeCalledWith(7);
    expect(getMoveCollectionOverlay().open).toEqual(true);
    expect(getMoveCollectionOverlay().options).toEqual({includeRoot: true});
    expect(getMoveCollectionOverlay().confirmLoading).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.moving = false;
        mediaCollection.rerenderCollection();
        expect(getMoveCollectionOverlay().open).toEqual(false);
        expect(getMoveCollectionOverlay().confirmLoading).toEqual(false);
        expect(collectionStore.resourceStore.reload).toBeCalledWith();
    });
});

test('Confirming the move dialog should move the item after confirming the permission dialog', async() => {
    const user = userEvent.setup();
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

    const mediaCollection = renderMediaCollection(
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
        return getSingleListOverlayProps('sulu_media.move_collection');
    };

    await clickCollectionAction(user, 'sulu_admin.move');

    expect(getDialogProps('sulu_media.remove_collection').open).toEqual(false);
    expect(getLatestCollectionFormOverlayProps().operationType).toEqual('move');
    expect(getMoveCollectionOverlay().open).toEqual(true);

    expect(getDialogProps('sulu_security.move_permission_title').open).toEqual(false);
    act(() => {
        getMoveCollectionOverlay().onConfirm({id: 7, _hasPermissions: true});
    });
    mediaCollection.rerenderCollection();
    expect(getDialogProps('sulu_security.move_permission_title').open).toEqual(true);

    act(() => {
        getDialogProps('sulu_security.move_permission_title').onConfirm();
    });

    collectionStore.resourceStore.moving = true;
    mediaCollection.rerenderCollection();

    expect(collectionStore.resourceStore.move).toBeCalledWith(7);
    expect(getMoveCollectionOverlay().open).toEqual(true);
    expect(getMoveCollectionOverlay().options).toEqual({includeRoot: true});
    expect(getMoveCollectionOverlay().confirmLoading).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.moving = false;
        mediaCollection.rerenderCollection();
        expect(getMoveCollectionOverlay().open).toEqual(false);
        expect(getMoveCollectionOverlay().confirmLoading).toEqual(false);
        expect(collectionStore.resourceStore.reload).toBeCalledWith();
    });
});

test('Confirming the move dialog should not move the item after denying the permission dialog', async() => {
    const user = userEvent.setup();
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

    const mediaCollection = renderMediaCollection(
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
        return getSingleListOverlayProps('sulu_media.move_collection');
    };

    await clickCollectionAction(user, 'sulu_admin.move');

    expect(getDialogProps('sulu_media.remove_collection').open).toEqual(false);
    expect(getLatestCollectionFormOverlayProps().operationType).toEqual('move');
    expect(getMoveCollectionOverlay().open).toEqual(true);

    expect(getDialogProps('sulu_security.move_permission_title').open).toEqual(false);
    act(() => {
        getMoveCollectionOverlay().onConfirm({id: 7, _hasPermissions: true});
    });
    mediaCollection.rerenderCollection();
    expect(getDialogProps('sulu_security.move_permission_title').open).toEqual(true);

    act(() => {
        getDialogProps('sulu_security.move_permission_title').onCancel();
    });

    mediaCollection.rerenderCollection();

    expect(getDialogProps('sulu_security.move_permission_title').open).toEqual(false);
    expect(getMoveCollectionOverlay().open).toEqual(true);
    expect(getMoveCollectionOverlay().confirmLoading).toEqual(false);
});

test('Confirming the permission overlay should reload the collection', async() => {
    const user = userEvent.setup();
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

    const mediaCollection = renderMediaCollection(
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

    expect(getLatestPermissionFormOverlayProps().open).toEqual(false);
    await clickCollectionAction(user, 'sulu_security.permissions');
    expect(getLatestPermissionFormOverlayProps().open).toEqual(true);

    act(() => {
        getLatestPermissionFormOverlayProps().onConfirm();
    });

    expect(collectionStore.resourceStore.reload).toBeCalledWith();
    mediaCollection.rerenderCollection();
    expect(getLatestPermissionFormOverlayProps().open).toEqual(false);
});
