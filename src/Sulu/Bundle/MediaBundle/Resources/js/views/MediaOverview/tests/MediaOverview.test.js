/* eslint-disable flowtype/require-valid-file-annotation */
import React, {default as mockReact} from 'react';
import {render} from '@testing-library/react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {
    findAllElementsByType,
    findElementByType,
    findWithHighOrderFunction,
    renderWithRef,
} from 'sulu-admin-bundle/utils/TestHelper';
import MediaCardOverviewAdapter from '../../../containers/List/adapters/MediaCardOverviewAdapter';

jest.mock(
    'react-dropzone',
    () => mockReact.forwardRef(({children}, ref) => children({getInputProps: jest.fn(), getRootProps: jest.fn(), ref}))
);

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
    this.destroy = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/CKEditor5', () => jest.fn(() => null));
jest.mock('sulu-admin-bundle/containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('sulu-admin-bundle/containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => ({
        destroy: jest.fn(),
    })),
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function(resourceKey, observableOptions) {
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

    this.observableOptions = observableOptions;
    this.loading = false;
    this.pageCount = 3;
    this.moveSelection = jest.fn();
    this.reload = jest.fn();
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
    this.clear = jest.fn();
    this.updateLoadingStrategy = jest.fn();
    this.updateStructureStrategy = jest.fn();
    this.sort = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.destroy = jest.fn();
        this.id = 1;

        mockExtendObservable(this, {
            loading: false,
            data: {
                id: 1,
                locked: false,
                _embedded: {
                    parent: {
                        id: 1,
                    },
                },
                _permissions: {},
            },
        });
    }),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

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

jest.mock('sulu-admin-bundle/containers/SingleListOverlay/SingleListOverlay', () => jest.fn(() => null));

function createRouter(options = {}) {
    return {
        attributes: options.attributes || {},
        bind: jest.fn(),
        navigate: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                locales: options.locales,
                permissions: options.permissions || {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
    };
}

function renderMediaOverview(router = createRouter()) {
    const MediaOverview = require('../MediaOverview').default;

    return {
        MediaOverview,
        router,
        ...renderWithRef(<MediaOverview router={router} />),
    };
}

function getToolbarFunction(MediaOverview) {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;

    return findWithHighOrderFunction(withToolbar, MediaOverview);
}

function getMediaCollectionProps(mediaOverview) {
    return findElementByType(mediaOverview.render(), 'MediaCollection').props;
}

function getMoveMediaOverlayProps(mediaOverview) {
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;

    return findAllElementsByType(mediaOverview.render(), SingleListOverlay)
        .find((overlay) => overlay.props.title === 'sulu_media.move_media')
        .props;
}

function getToolbarItem(toolbarConfig, label) {
    return toolbarConfig.items.find((item) => item.label === label);
}

beforeEach(() => {
    jest.resetModules();

    const listAdapterRegistry = require('sulu-admin-bundle/containers/List/registries/listAdapterRegistry');

    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/List/adapters/FolderAdapter').default,
        'table': require('sulu-admin-bundle/containers/List/adapters/TableAdapter').default,
        'media_card_overview': MediaCardOverviewAdapter,
    });
});

test('Render a simple MediaOverview', () => {
    const MediaOverview = require('../MediaOverview').default;
    const router = createRouter();

    const {container} = render(<MediaOverview router={router} />);
    expect(container.innerHTML).toMatchSnapshot();
});

test('Destroy all stores on unmount', () => {
    const router = createRouter();
    const {
        instance: mediaOverview,
        unmount,
    } = renderMediaOverview(router);
    const collectionPage = router.bind.mock.calls[0][1];
    const mediaPage = router.bind.mock.calls[1][1];
    const locale = router.bind.mock.calls[2][1];
    const collectionLimit = router.bind.mock.calls[5][1];
    const mediaFilter = router.bind.mock.calls[6][1];
    const mediaLimit = router.bind.mock.calls[7][1];
    const mediaSortColumn = router.bind.mock.calls[8][1];
    const mediaSortOrder = router.bind.mock.calls[9][1];

    expect(mediaOverview.collectionListStore.sort).toHaveBeenCalledWith('title', 'asc');
    expect(collectionPage.get()).toBe(undefined);
    expect(mediaPage.get()).toBe(1);
    expect(locale.get()).toBe(undefined);
    expect(router.bind).toHaveBeenCalledWith('collectionPage', collectionPage, 1);
    expect(router.bind).toHaveBeenCalledWith('mediaPage', mediaPage, 1);
    expect(router.bind).toHaveBeenCalledWith('locale', locale);
    expect(router.bind).toHaveBeenCalledWith('collectionLimit', collectionLimit, 10);
    expect(router.bind).toHaveBeenCalledWith('mediaFilter', mediaFilter, {});
    expect(router.bind).toHaveBeenCalledWith('mediaLimit', mediaLimit, 10);
    expect(router.bind).toHaveBeenCalledWith('mediaSortColumn', mediaSortColumn);
    expect(router.bind).toHaveBeenCalledWith('mediaSortOrder', mediaSortOrder);

    unmount();
    expect(mediaOverview.mediaListStore.destroy).toHaveBeenCalled();
    expect(mediaOverview.collectionListStore.destroy).toHaveBeenCalled();
    expect(mediaOverview.collectionStore.resourceStore.destroy).toHaveBeenCalled();
});

test('Should navigate to defined route on back button click', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaOverview);
    toolbarConfig.backButton.onClick();

    expect(mediaOverview.mediaListStore.clear).toHaveBeenCalled();
    expect(mediaOverview.mediaListStore.clearSelection).toHaveBeenCalled();
    expect(mediaOverview.collectionListStore.clear).toHaveBeenCalled();
    expect(mediaOverview.collectionListStore.clearSelection).toHaveBeenCalled();
    expect(router.restore).toHaveBeenCalledWith('sulu_media.overview', {
        'collectionPage': '1',
        'id': 1,
        'locale': 'de',
    });
});

test('Router navigate should be called when a media was clicked', () => {
    const locale = 'de';
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: [locale],
    });
    const {instance: mediaOverview} = renderMediaOverview(router);
    mediaOverview.locale.set(locale);

    getMediaCollectionProps(mediaOverview).onMediaNavigate(1);

    expect(router.navigate).toHaveBeenCalledWith(
        'sulu_media.form.details',
        {'id': 1, locale}
    );
});

test('The collectionId should be update along with the content when a collection was clicked', () => {
    const locale = 'de';
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: [locale],
    });
    const {instance: mediaOverview} = renderMediaOverview(router);
    mediaOverview.locale.set(locale);
    mediaOverview.mediaPage.set(3);
    mediaOverview.collectionPage.set(2);
    mediaOverview.collectionId.set(4);

    getMediaCollectionProps(mediaOverview).onCollectionNavigate(1);

    expect(mediaOverview.collectionId.get()).toEqual(1);
    expect(mediaOverview.collectionPage.get()).toEqual(1);
    expect(mediaOverview.mediaPage.get()).toEqual(1);
    expect(mediaOverview.mediaListStore.clearSelection).toHaveBeenCalled();
    expect(mediaOverview.mediaListStore.clear).toHaveBeenCalled();
    expect(mediaOverview.collectionListStore.clearSelection).toHaveBeenCalled();
    expect(mediaOverview.collectionListStore.clear).toHaveBeenCalled();
});

test('Delete overlay should be shown when delete button is clicked', () => {
    const router = createRouter();
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    const mediaListStore = mediaOverview.mediaListStore;
    const requestSelectionDeleteSpy = jest.fn();
    mediaOverview.mediaList.requestSelectionDelete = requestSelectionDeleteSpy;
    mediaListStore.selectionIds.push(1, 4, 6);

    const deleteItem = getToolbarItem(toolbarFunction.call(mediaOverview), 'sulu_admin.delete_selected');
    expect(deleteItem.disabled).toEqual(false);

    deleteItem.onClick();

    expect(requestSelectionDeleteSpy).toHaveBeenCalledWith();
});

test('Upload button should be disabled if collection is loading', () => {
    const router = createRouter({
        locales: ['de'],
    });
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    mediaOverview.locale.set('de');
    mediaOverview.collectionId.set(4);

    mediaOverview.collectionStore.resourceStore.loading = true;
    expect(toolbarFunction.call(mediaOverview).items[0].label).toEqual('sulu_media.upload_file');
    expect(toolbarFunction.call(mediaOverview).items[0].disabled).toBeTruthy();

    mediaOverview.collectionStore.resourceStore.loading = false;
    expect(toolbarFunction.call(mediaOverview).items[0].disabled).toBeFalsy();
});

test('Upload overlay should be opened and closed as it requests', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    const {instance: mediaOverview} = renderMediaOverview(router);

    expect(getMediaCollectionProps(mediaOverview).uploadOverlayOpen).toEqual(false);
    getMediaCollectionProps(mediaOverview).onUploadOverlayOpen();
    expect(getMediaCollectionProps(mediaOverview).uploadOverlayOpen).toEqual(true);
    getMediaCollectionProps(mediaOverview).onUploadOverlayClose();
    expect(getMediaCollectionProps(mediaOverview).uploadOverlayOpen).toEqual(false);
});

test('Toolbar buttons should disappear when permissions are missing', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
        permissions: {
            add: false,
            delete: false,
            edit: false,
        },
    });
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    expect(toolbarFunction.call(mediaOverview).items).toHaveLength(0);
});

test('Toolbar buttons should disappear when permissions are missing on current collection', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    mediaOverview.collectionStore.resourceStore.data = {
        _permissions: {add: false, delete: false, edit: false},
    };

    expect(toolbarFunction.call(mediaOverview).items).toHaveLength(0);
});

test('Move button should be disabled if nothing is selected', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    expect(toolbarFunction.call(mediaOverview).items[2].disabled).toEqual(true);
    expect(toolbarFunction.call(mediaOverview).items[2].label).toEqual('sulu_admin.move_selected');

    mediaOverview.mediaListStore.selectionIds.push(8);
    expect(toolbarFunction.call(mediaOverview).items[2].disabled).toEqual(false);
});

test('Upload and move button should disappear if collection is locked', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    mediaOverview.collectionStore.resourceStore.data.locked = false;
    expect(toolbarFunction.call(mediaOverview).items).toHaveLength(3);
    expect(toolbarFunction.call(mediaOverview).items[0].label).toEqual('sulu_media.upload_file');
    expect(toolbarFunction.call(mediaOverview).items[2].label).toEqual('sulu_admin.move_selected');

    mediaOverview.collectionStore.resourceStore.data.locked = true;
    expect(toolbarFunction.call(mediaOverview).items).toHaveLength(1);
    expect(toolbarFunction.call(mediaOverview).items[0].label).not.toEqual('sulu_media.upload_file');
    expect(toolbarFunction.call(mediaOverview).items[0].label).not.toEqual('sulu_media.move_selected');
});

test('Move overlay should disappear when overlay is closed', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaOverview);

    expect(toolbarConfig.items[2].label).toEqual('sulu_admin.move_selected');
    toolbarConfig.items[2].onClick();
    expect(getMoveMediaOverlayProps(mediaOverview).listKey).toEqual('collections');
    expect(getMoveMediaOverlayProps(mediaOverview).resourceKey).toEqual('collections');
    expect(getMoveMediaOverlayProps(mediaOverview).open).toEqual(true);

    getMoveMediaOverlayProps(mediaOverview).onClose();
    expect(getMoveMediaOverlayProps(mediaOverview).open).toEqual(false);
});

test('Media should be moved when overlay is confirmed', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');
    const movePromise = Promise.resolve();
    mediaOverview.mediaListStore.moveSelection.mockReturnValue(movePromise);

    const toolbarConfig = toolbarFunction.call(mediaOverview);

    expect(toolbarConfig.items[2].label).toEqual('sulu_admin.move_selected');
    toolbarConfig.items[2].onClick();
    expect(getMoveMediaOverlayProps(mediaOverview).resourceKey).toEqual('collections');
    expect(getMoveMediaOverlayProps(mediaOverview).confirmLoading).toEqual(false);
    expect(getMoveMediaOverlayProps(mediaOverview).open).toEqual(true);

    getMoveMediaOverlayProps(mediaOverview).onConfirm({id: 8});
    expect(getMoveMediaOverlayProps(mediaOverview).confirmLoading).toEqual(true);

    expect(mediaOverview.mediaListStore.moveSelection).toHaveBeenCalledWith(8);

    return movePromise.then(() => {
        expect(mediaOverview.collectionListStore.reload).toHaveBeenCalledTimes(1);
        expect(getMoveMediaOverlayProps(mediaOverview).open).toEqual(false);
        expect(getMoveMediaOverlayProps(mediaOverview).confirmLoading).toEqual(false);
        expect(mediaOverview.mediaListStore.clearSelection).toHaveBeenCalled();
    });
});

test('Should show generic error if upload of multiple files fails in MediaCollection', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    expect(toolbarFunction.call(mediaOverview).errors).toEqual([]);

    getMediaCollectionProps(mediaOverview).onUploadError(
        [
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
        ]
    );

    expect(toolbarFunction.call(mediaOverview).errors).toEqual(['sulu_media.upload_server_error']);
});

test('Should show error message from serve if upload of a single files fails in MediaCollection', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    const {MediaOverview, instance: mediaOverview} = renderMediaOverview(router);
    const toolbarFunction = getToolbarFunction(MediaOverview);
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    expect(toolbarFunction.call(mediaOverview).errors).toEqual([]);

    getMediaCollectionProps(mediaOverview).onUploadError(
        [
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
        ]
    );

    expect(toolbarFunction.call(mediaOverview).errors).toEqual(
        ['The uploaded file exceeds the configured maximum filesize.']
    );
});
