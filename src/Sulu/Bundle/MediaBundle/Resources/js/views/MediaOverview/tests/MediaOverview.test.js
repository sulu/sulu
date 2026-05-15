/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {act, render} from '@testing-library/react';
import {extendObservable as mockExtendObservable} from 'mobx';
import SingleListOverlay from 'sulu-admin-bundle/containers/SingleListOverlay';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import getMockCallArg from 'sulu-admin-bundle/utils/TestHelper/getMockCallArg';
import MediaCollection from '../../../containers/MediaCollection';
import MediaOverview from '../MediaOverview';

const mockListStores = [];
const mockMediaList = {
    requestSelectionDelete: jest.fn(),
};
const mockToolbarConfigGetters = [];

jest.mock('sulu-admin-bundle/containers/Toolbar/withToolbar', () => jest.fn((Component, toolbar) => {
    return class WithToolbarMock extends Component {
        render() {
            mockToolbarConfigGetters.push(() => toolbar.call(this));

            return super.render();
        }
    };
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function(
    resourceKey,
    listKey,
    userSettingsKey,
    observableOptions
) {
    this.resourceKey = resourceKey;
    this.observableOptions = observableOptions;
    this.loading = false;
    this.pageCount = 3;
    this.selectionIds = [];
    this.deletingSelection = false;
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

    mockListStores.push(this);
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

jest.mock('../../../containers/MediaCollection', () => {
    const React = require('react');
    const MediaCollectionMock = jest.fn(function MediaCollectionMock(props) {
        React.useEffect(() => {
            props.mediaListRef(mockMediaList);

            return () => props.mediaListRef(null);
        }, [props]);

        return <div data-testid="media-collection" />;
    });

    return MediaCollectionMock;
});

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => {
    const SingleListOverlayMock = jest.fn(function SingleListOverlayMock() {
        return <div data-testid="single-list-overlay" />;
    });

    return SingleListOverlayMock;
});

const MediaCollectionMock = MediaCollection;
const SingleListOverlayMock = SingleListOverlay;

function createRouter(overrides = {}) {
    return {
        attributes: {},
        bind: jest.fn(),
        navigate: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        ...overrides,
    };
}

function renderMediaOverview(router) {
    return render(<MediaOverview router={router} />);
}

function getMediaCollectionProps() {
    return getLatestMockProps(MediaCollectionMock);
}

function getSingleListOverlayProps() {
    return getLatestMockProps(SingleListOverlayMock);
}

function getListStore(resourceKey) {
    const listStore = mockListStores.find((listStore) => listStore.resourceKey === resourceKey);

    if (!listStore) {
        throw new Error('ListStore was not created for resource "' + resourceKey + '"');
    }

    return listStore;
}

function getCollectionListStore() {
    return getListStore('collections');
}

function getMediaListStore() {
    return getListStore('media');
}

function getCollectionStore() {
    return getMediaCollectionProps().collectionStore;
}

function getLatestToolbarConfig() {
    return mockToolbarConfigGetters[mockToolbarConfigGetters.length - 1]();
}

function getBoundRouterValue(router, name) {
    const call = router.bind.mock.calls.find(([bindName]) => bindName === name);

    if (!call) {
        throw new Error('Router value "' + name + '" was not bound');
    }

    return call[1];
}

beforeEach(() => {
    jest.clearAllMocks();
    mockListStores.splice(0, mockListStores.length);
    mockToolbarConfigGetters.splice(0, mockToolbarConfigGetters.length);
});

test('Render a simple MediaOverview', () => {
    const router = createRouter();

    const {asFragment} = renderMediaOverview(router);

    expect(asFragment()).toMatchSnapshot();
});

test('Destroy all stores on unmount', () => {
    const router = createRouter();

    const {unmount} = renderMediaOverview(router);
    const collectionListStore = getCollectionListStore();
    const mediaListStore = getMediaListStore();
    const collectionStore = getCollectionStore();

    const collectionPage = getMockCallArg(router.bind, 0, 1);
    const mediaPage = getMockCallArg(router.bind, 1, 1);
    const locale = getMockCallArg(router.bind, 2, 1);
    const collectionLimit = getMockCallArg(router.bind, 5, 1);
    const mediaFilter = getMockCallArg(router.bind, 6, 1);
    const mediaLimit = getMockCallArg(router.bind, 7, 1);
    const mediaSortColumn = getMockCallArg(router.bind, 8, 1);
    const mediaSortOrder = getMockCallArg(router.bind, 9, 1);

    expect(collectionListStore.sort).toBeCalledWith('title', 'asc');
    expect(collectionPage.get()).toBe(undefined);
    expect(mediaPage.get()).toBe(1);
    expect(locale.get()).toBe(undefined);
    expect(router.bind).toBeCalledWith('collectionPage', collectionPage, 1);
    expect(router.bind).toBeCalledWith('mediaPage', mediaPage, 1);
    expect(router.bind).toBeCalledWith('locale', locale);
    expect(router.bind).toBeCalledWith('collectionLimit', collectionLimit, 10);
    expect(router.bind).toBeCalledWith('mediaFilter', mediaFilter, {});
    expect(router.bind).toBeCalledWith('mediaLimit', mediaLimit, 10);
    expect(router.bind).toBeCalledWith('mediaSortColumn', mediaSortColumn);
    expect(router.bind).toBeCalledWith('mediaSortOrder', mediaSortOrder);

    unmount();

    expect(mediaListStore.destroy).toBeCalled();
    expect(collectionListStore.destroy).toBeCalled();
    expect(collectionStore.resourceStore.destroy).toBeCalled();
});

test('Should navigate to defined route on back button click', () => {
    const router = createRouter({
        attributes: {id: 4},
    });

    renderMediaOverview(router);

    getBoundRouterValue(router, 'id').set(4);
    getBoundRouterValue(router, 'locale').set('de');

    const toolbarConfig = getLatestToolbarConfig();
    toolbarConfig.backButton.onClick();

    expect(getMediaListStore().clear).toBeCalled();
    expect(getMediaListStore().clearSelection).toBeCalled();
    expect(getCollectionListStore().clear).toBeCalled();
    expect(getCollectionListStore().clearSelection).toBeCalled();
    expect(router.restore).toBeCalledWith('sulu_media.overview', {
        collectionPage: '1',
        id: 1,
        locale: 'de',
    });
});

test('Router navigate should be called when a media was clicked', () => {
    const locale = 'de';
    const router = createRouter({
        attributes: {id: 4},
        route: {
            options: {
                locales: [locale],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
    });

    renderMediaOverview(router);
    getBoundRouterValue(router, 'locale').set(locale);

    act(() => {
        getMediaCollectionProps().onMediaNavigate(1);
    });

    expect(router.navigate).toBeCalledWith('sulu_media.form.details', {id: 1, locale});
});

test('The collectionId should be update along with the content when a collection was clicked', () => {
    const locale = 'de';
    const router = createRouter({
        attributes: {id: 4},
        route: {
            options: {
                locales: [locale],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
    });

    renderMediaOverview(router);
    const mediaPage = getBoundRouterValue(router, 'mediaPage');
    const collectionPage = getBoundRouterValue(router, 'collectionPage');
    const collectionId = getBoundRouterValue(router, 'id');

    getBoundRouterValue(router, 'locale').set(locale);
    mediaPage.set(3);
    collectionPage.set(2);
    collectionId.set(4);

    act(() => {
        getMediaCollectionProps().onCollectionNavigate(1);
    });

    expect(collectionId.get()).toEqual(1);
    expect(collectionPage.get()).toEqual(1);
    expect(mediaPage.get()).toEqual(1);
    expect(getMediaListStore().clearSelection).toBeCalled();
    expect(getMediaListStore().clear).toBeCalled();
    expect(getCollectionListStore().clearSelection).toBeCalled();
    expect(getCollectionListStore().clear).toBeCalled();
});

test('Delete action should trigger media list deletion request', () => {
    const router = createRouter();

    renderMediaOverview(router);
    getMediaListStore().selectionIds.push(1, 4, 6);

    const deleteItem = getLatestToolbarConfig().items
        .find((item) => item.label === 'sulu_admin.delete_selected');

    act(() => {
        deleteItem.onClick();
    });

    expect(mockMediaList.requestSelectionDelete).toBeCalledWith();
});

test('Upload button should be disabled if collection is loading', () => {
    const router = createRouter();

    renderMediaOverview(router);

    getBoundRouterValue(router, 'locale').set('de');
    getBoundRouterValue(router, 'id').set(4);

    getCollectionStore().resourceStore.loading = true;
    expect(getLatestToolbarConfig().items[0].label).toEqual('sulu_media.upload_file');
    expect(getLatestToolbarConfig().items[0].disabled).toBe(true);

    getCollectionStore().resourceStore.loading = false;
    expect(getLatestToolbarConfig().items[0].disabled).toBe(false);
});

test('Upload overlay should be opened and closed as it requests', () => {
    const router = createRouter({attributes: {id: 4}});

    renderMediaOverview(router);

    expect(getMediaCollectionProps().uploadOverlayOpen).toBe(false);

    act(() => {
        getMediaCollectionProps().onUploadOverlayOpen();
    });

    expect(getMediaCollectionProps().uploadOverlayOpen).toBe(true);

    act(() => {
        getMediaCollectionProps().onUploadOverlayClose();
    });

    expect(getMediaCollectionProps().uploadOverlayOpen).toBe(false);
});

test('Toolbar buttons should disappear when permissions are missing', () => {
    const router = createRouter({
        attributes: {id: 4},
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: false,
                    delete: false,
                    edit: false,
                },
            },
        },
    });

    renderMediaOverview(router);

    getBoundRouterValue(router, 'id').set(4);
    getBoundRouterValue(router, 'locale').set('de');

    expect(getLatestToolbarConfig().items).toHaveLength(0);
});

test('Toolbar buttons should disappear when permissions are missing on current collection', () => {
    const router = createRouter({attributes: {id: 4}});

    renderMediaOverview(router);

    getBoundRouterValue(router, 'id').set(4);
    getBoundRouterValue(router, 'locale').set('de');

    getCollectionStore().resourceStore.data = {
        _permissions: {add: false, delete: false, edit: false},
    };

    expect(getLatestToolbarConfig().items).toHaveLength(0);
});

test('Move button should be disabled if nothing is selected', () => {
    const router = createRouter({attributes: {id: 4}});

    renderMediaOverview(router);

    getBoundRouterValue(router, 'id').set(4);
    getBoundRouterValue(router, 'locale').set('de');

    expect(getLatestToolbarConfig().items[2].disabled).toEqual(true);
    expect(getLatestToolbarConfig().items[2].label).toEqual('sulu_admin.move_selected');

    getMediaListStore().selectionIds.push(8);
    expect(getLatestToolbarConfig().items[2].disabled).toEqual(false);
});

test('Upload and move button should disappear if collection is locked', () => {
    const router = createRouter({attributes: {id: 4}});

    renderMediaOverview(router);

    getBoundRouterValue(router, 'id').set(4);
    getBoundRouterValue(router, 'locale').set('de');

    getCollectionStore().resourceStore.data.locked = false;
    expect(getLatestToolbarConfig().items).toHaveLength(3);
    expect(getLatestToolbarConfig().items[0].label).toEqual('sulu_media.upload_file');
    expect(getLatestToolbarConfig().items[2].label).toEqual('sulu_admin.move_selected');

    getCollectionStore().resourceStore.data.locked = true;
    expect(getLatestToolbarConfig().items).toHaveLength(1);
    expect(getLatestToolbarConfig().items[0].label).not.toEqual('sulu_media.upload_file');
    expect(getLatestToolbarConfig().items[0].label).not.toEqual('sulu_media.move_selected');
});

test('Move overlay should disappear when overlay is closed', () => {
    const router = createRouter({attributes: {id: 4}});

    renderMediaOverview(router);

    getBoundRouterValue(router, 'id').set(4);
    getBoundRouterValue(router, 'locale').set('de');

    const toolbarConfig = getLatestToolbarConfig();

    expect(toolbarConfig.items[2].label).toEqual('sulu_admin.move_selected');

    act(() => {
        toolbarConfig.items[2].onClick();
    });

    expect(getSingleListOverlayProps().listKey).toEqual('collections');
    expect(getSingleListOverlayProps().resourceKey).toEqual('collections');
    expect(getSingleListOverlayProps().open).toEqual(true);

    act(() => {
        getSingleListOverlayProps().onClose();
    });

    expect(getSingleListOverlayProps().open).toEqual(false);
});

test('Media should be moved when overlay is confirmed', async() => {
    const router = createRouter({attributes: {id: 4}});

    renderMediaOverview(router);

    getBoundRouterValue(router, 'id').set(4);
    getBoundRouterValue(router, 'locale').set('de');
    const movePromise = Promise.resolve();
    getMediaListStore().moveSelection.mockReturnValue(movePromise);

    const toolbarConfig = getLatestToolbarConfig();

    expect(toolbarConfig.items[2].label).toEqual('sulu_admin.move_selected');

    act(() => {
        toolbarConfig.items[2].onClick();
    });

    expect(getSingleListOverlayProps().resourceKey).toEqual('collections');
    expect(getSingleListOverlayProps().confirmLoading).toEqual(false);
    expect(getSingleListOverlayProps().open).toEqual(true);

    act(() => {
        getSingleListOverlayProps().onConfirm({id: 8});
    });

    expect(getSingleListOverlayProps().confirmLoading).toEqual(true);
    expect(getMediaListStore().moveSelection).toBeCalledWith(8);

    await movePromise;

    expect(getCollectionListStore().reload).toHaveBeenCalledTimes(1);
    expect(getSingleListOverlayProps().open).toEqual(false);
    expect(getSingleListOverlayProps().confirmLoading).toEqual(false);
    expect(getMediaListStore().clearSelection).toBeCalled();
});

test('Should show generic error if upload of multiple files fails in MediaCollection', () => {
    const router = createRouter({attributes: {id: 4}});

    renderMediaOverview(router);

    getBoundRouterValue(router, 'id').set(4);
    getBoundRouterValue(router, 'locale').set('de');

    expect(getLatestToolbarConfig().errors).toEqual([]);

    act(() => {
        getMediaCollectionProps().onUploadError(
            [
                {
                    code: 5003,
                    detail: 'The uploaded file exceeds the configured maximum filesize.',
                },
                {
                    code: 5003,
                    detail: 'The uploaded file exceeds the configured maximum filesize.',
                },
            ]
        );
    });

    expect(getLatestToolbarConfig().errors).toEqual(['sulu_media.upload_server_error']);
});

test('Should show error message from server if upload of a single files fails in MediaCollection', () => {
    const router = createRouter({attributes: {id: 4}});

    renderMediaOverview(router);

    getBoundRouterValue(router, 'id').set(4);
    getBoundRouterValue(router, 'locale').set('de');

    expect(getLatestToolbarConfig().errors).toEqual([]);

    act(() => {
        getMediaCollectionProps().onUploadError(
            [
                {
                    code: 5003,
                    detail: 'The uploaded file exceeds the configured maximum filesize.',
                },
            ]
        );
    });

    expect(getLatestToolbarConfig().errors).toEqual(
        ['The uploaded file exceeds the configured maximum filesize.']
    );
});
