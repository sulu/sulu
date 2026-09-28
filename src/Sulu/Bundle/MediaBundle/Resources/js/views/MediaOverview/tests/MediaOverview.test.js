/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import {createDeferred, mockResizeObserver} from 'sulu-admin-bundle/utils/TestHelper';

const mockListStores = [];
const mockRequestSelectionDelete = jest.fn();
let mockResourceStore;

mockResizeObserver();

jest.mock('sulu-admin-bundle/containers/CKEditor5', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => {
    const ListStore = jest.fn(function(resourceKey, listKey, userSettingsKey, observableOptions) {
        this.observableOptions = observableOptions;
        this.moveSelection = jest.fn();
        this.reload = jest.fn();
        this.filterOptions = {
            get: jest.fn().mockReturnValue({}),
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
        this.destroy = jest.fn();
        this.clearSelection = jest.fn();
        this.clear = jest.fn();
        this.sort = jest.fn();

        mockExtendObservable(this, {
            loading: false,
            selectionIds: [],
        });
        mockListStores.push(this);
    });

    ListStore.getFilterSetting = jest.fn();
    ListStore.getLimitSetting = jest.fn();
    ListStore.getSortColumnSetting = jest.fn();
    ListStore.getSortOrderSetting = jest.fn();

    return ListStore;
});

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        mockResourceStore = this;
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

jest.mock('../../../containers/MediaCollection', () => {
    const React = require('react');

    return jest.fn((props) => {
        props.mediaListRef({requestSelectionDelete: mockRequestSelectionDelete});

        function handleCollectionNavigate() {
            props.onCollectionNavigate(1);
        }

        function handleMediaNavigate() {
            props.onMediaNavigate(1);
        }

        function handleUploadError() {
            props.onUploadError([{detail: 'Upload failed.'}]);
        }

        function handleMultipleUploadErrors() {
            props.onUploadError([{detail: 'First error'}, {detail: 'Second error'}]);
        }

        return (
            <div>
                {React.createElement(
                    'button',
                    {onClick: handleCollectionNavigate, type: 'button'},
                    'Open collection'
                )}
                {React.createElement('button', {onClick: handleMediaNavigate, type: 'button'}, 'Open media')}
                {props.uploadOverlayOpen &&
                    <button onClick={props.onUploadOverlayClose} type="button">Close upload</button>
                }
                {React.createElement(
                    'button',
                    {onClick: handleUploadError, type: 'button'},
                    'Report upload error'
                )}
                {React.createElement(
                    'button',
                    {onClick: handleMultipleUploadErrors, type: 'button'},
                    'Report multiple upload errors'
                )}
            </div>
        );
    });
});

jest.mock('sulu-admin-bundle/containers/SingleListOverlay/SingleListOverlay', () => {
    const React = require('react');

    return jest.fn((props) => {
        if (!props.open) {
            return null;
        }

        function handleConfirm() {
            props.onConfirm({id: 8});
        }

        return (
            <div aria-label={props.title} role="dialog">
                <button onClick={props.onClose} type="button">Close</button>
                {React.createElement(
                    'button',
                    {onClick: handleConfirm, type: 'button'},
                    props.confirmLoading ? 'Moving' : 'Move'
                )}
            </div>
        );
    });
});

function createRouter(options = {}) {
    return {
        attributes: options.attributes || {},
        bind: jest.fn(),
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
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
    const Toolbar = require('sulu-admin-bundle/containers/Toolbar').default;

    render(<Toolbar />);

    return {
        MediaOverview,
        router,
        ...render(<MediaOverview router={router} />),
    };
}

function getListStores() {
    return {
        collectionListStore: mockListStores[0],
        mediaListStore: mockListStores[1],
    };
}

function getBoundValue(router, attributeName) {
    return router.bind.mock.calls.find(([name]) => name === attributeName)[1];
}

function setRouteValues(router, values) {
    act(() => {
        Object.keys(values).forEach((attributeName) => {
            getBoundValue(router, attributeName).set(values[attributeName]);
        });
    });
}

beforeEach(() => {
    jest.clearAllMocks();
    jest.resetModules();
    mockListStores.length = 0;
    mockResourceStore = undefined;
});

test('Render a simple MediaOverview', () => {
    const router = createRouter();

    renderMediaOverview(router);

    expect(screen.getByRole('button', {name: 'Open collection'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Open media'})).toBeInTheDocument();
});

test('Destroy all stores on unmount', () => {
    const router = createRouter();
    const {unmount} = renderMediaOverview(router);
    const {collectionListStore, mediaListStore} = getListStores();
    const collectionResourceStore = mockResourceStore;
    const collectionPage = getBoundValue(router, 'collectionPage');
    const mediaPage = getBoundValue(router, 'mediaPage');
    const locale = getBoundValue(router, 'locale');
    const collectionLimit = getBoundValue(router, 'collectionLimit');
    const mediaFilter = getBoundValue(router, 'mediaFilter');
    const mediaLimit = getBoundValue(router, 'mediaLimit');
    const mediaSortColumn = getBoundValue(router, 'mediaSortColumn');
    const mediaSortOrder = getBoundValue(router, 'mediaSortOrder');

    expect(collectionListStore.sort).toHaveBeenCalledWith('title', 'asc');
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
    expect(mediaListStore.destroy).toHaveBeenCalled();
    expect(collectionListStore.destroy).toHaveBeenCalled();
    expect(collectionResourceStore.destroy).toHaveBeenCalled();
});

test('Should navigate to defined route on back button click', async() => {
    const user = userEvent.setup();
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    renderMediaOverview(router);
    const {collectionListStore, mediaListStore} = getListStores();
    setRouteValues(router, {id: 4, locale: 'de'});

    await user.click(screen.getByRole('button', {name: 'su-angle-left'}));

    expect(mediaListStore.clear).toHaveBeenCalled();
    expect(mediaListStore.clearSelection).toHaveBeenCalled();
    expect(collectionListStore.clear).toHaveBeenCalled();
    expect(collectionListStore.clearSelection).toHaveBeenCalled();
    expect(router.restore).toHaveBeenCalledWith('sulu_media.overview', {
        'collectionPage': '1',
        'id': 1,
        'locale': 'de',
    });
});

test('Router navigate should be called when a media was clicked', async() => {
    const user = userEvent.setup();
    const locale = 'de';
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: [locale],
    });
    renderMediaOverview(router);
    setRouteValues(router, {locale});

    await user.click(screen.getByRole('button', {name: 'Open media'}));

    expect(router.navigate).toHaveBeenCalledWith(
        'sulu_media.form.details',
        {'id': 1, locale}
    );
});

test('The collectionId should be update along with the content when a collection was clicked', async() => {
    const user = userEvent.setup();
    const locale = 'de';
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: [locale],
    });
    renderMediaOverview(router);
    const {collectionListStore, mediaListStore} = getListStores();
    setRouteValues(router, {collectionPage: 2, id: 4, locale, mediaPage: 3});

    await user.click(screen.getByRole('button', {name: 'Open collection'}));

    expect(getBoundValue(router, 'id').get()).toEqual(1);
    expect(getBoundValue(router, 'collectionPage').get()).toEqual(1);
    expect(getBoundValue(router, 'mediaPage').get()).toEqual(1);
    expect(mediaListStore.clearSelection).toHaveBeenCalled();
    expect(mediaListStore.clear).toHaveBeenCalled();
    expect(collectionListStore.clearSelection).toHaveBeenCalled();
    expect(collectionListStore.clear).toHaveBeenCalled();
});

test('Delete overlay should be shown when delete button is clicked', async() => {
    const user = userEvent.setup();
    const router = createRouter();
    renderMediaOverview(router);
    const {mediaListStore} = getListStores();
    act(() => {
        mediaListStore.selectionIds.push(1, 4, 6);
    });

    const deleteButton = screen.getByRole('button', {name: /sulu_admin\.delete_selected/});
    expect(deleteButton).toBeEnabled();
    await user.click(deleteButton);

    expect(mockRequestSelectionDelete).toHaveBeenCalledWith();
});

test('Upload button should be disabled if collection is loading', () => {
    const router = createRouter({
        locales: ['de'],
    });
    renderMediaOverview(router);
    setRouteValues(router, {id: 4, locale: 'de'});
    const resourceStore = mockResourceStore;

    act(() => {
        resourceStore.loading = true;
    });
    expect(screen.getByRole('button', {name: /sulu_media\.upload_file/})).toBeDisabled();

    act(() => {
        resourceStore.loading = false;
    });
    expect(screen.getByRole('button', {name: /sulu_media\.upload_file/})).toBeEnabled();
});

test('Upload overlay should be opened and closed as requested', async() => {
    const user = userEvent.setup();
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    renderMediaOverview(router);

    expect(screen.queryByRole('button', {name: 'Close upload'})).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /sulu_media\.upload_file/}));
    expect(screen.getByRole('button', {name: 'Close upload'})).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'Close upload'}));
    expect(screen.queryByRole('button', {name: 'Close upload'})).not.toBeInTheDocument();
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
    renderMediaOverview(router);
    setRouteValues(router, {id: 4, locale: 'de'});

    expect(screen.queryByRole('button', {name: /sulu_media\.upload_file/})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_admin\.delete_selected/})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_admin\.move_selected/})).not.toBeInTheDocument();
});

test('Toolbar buttons should disappear when permissions are missing on current collection', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    renderMediaOverview(router);
    setRouteValues(router, {id: 4, locale: 'de'});

    act(() => {
        mockResourceStore.data = {
            _permissions: {add: false, delete: false, edit: false},
        };
    });

    expect(screen.queryByRole('button', {name: /sulu_media\.upload_file/})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_admin\.delete_selected/})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_admin\.move_selected/})).not.toBeInTheDocument();
});

test('Move button should be disabled if nothing is selected', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    renderMediaOverview(router);
    setRouteValues(router, {id: 4, locale: 'de'});
    const {mediaListStore} = getListStores();

    const moveButton = screen.getByRole('button', {name: /sulu_admin\.move_selected/});
    expect(moveButton).toBeDisabled();

    act(() => {
        mediaListStore.selectionIds.push(8);
    });
    expect(moveButton).toBeEnabled();
});

test('Upload and move button should disappear if collection is locked', () => {
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    renderMediaOverview(router);
    setRouteValues(router, {id: 4, locale: 'de'});
    const resourceStore = mockResourceStore;

    act(() => {
        resourceStore.data.locked = false;
    });
    expect(screen.getByRole('button', {name: /sulu_media\.upload_file/})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_admin\.move_selected/})).toBeInTheDocument();

    act(() => {
        resourceStore.data.locked = true;
    });
    expect(screen.queryByRole('button', {name: /sulu_media\.upload_file/})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_admin\.move_selected/})).not.toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_admin\.delete_selected/})).toBeInTheDocument();
});

test('Move overlay should disappear when overlay is closed', async() => {
    const user = userEvent.setup();
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    renderMediaOverview(router);
    setRouteValues(router, {id: 4, locale: 'de'});
    const {mediaListStore} = getListStores();

    act(() => {
        mediaListStore.selectionIds.push(8);
    });

    await user.click(screen.getByRole('button', {name: /sulu_admin\.move_selected/}));
    expect(screen.getByRole('dialog', {name: 'sulu_media.move_media'})).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'Close'}));
    expect(screen.queryByRole('dialog', {name: 'sulu_media.move_media'})).not.toBeInTheDocument();
});

test('Media should be moved when overlay is confirmed', async() => {
    const user = userEvent.setup();
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    renderMediaOverview(router);
    setRouteValues(router, {id: 4, locale: 'de'});
    const {collectionListStore, mediaListStore} = getListStores();
    const moveRequest = createDeferred();
    mediaListStore.moveSelection.mockReturnValue(moveRequest.promise);
    act(() => {
        mediaListStore.selectionIds.push(8);
    });

    await user.click(screen.getByRole('button', {name: /sulu_admin\.move_selected/}));
    expect(screen.getByRole('button', {name: 'Move'})).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'Move'}));
    expect(screen.getByRole('button', {name: 'Moving'})).toBeInTheDocument();

    expect(mediaListStore.moveSelection).toHaveBeenCalledWith(8);

    await act(async() => {
        moveRequest.resolve();
        await moveRequest.promise;
    });

    expect(collectionListStore.reload).toHaveBeenCalledTimes(1);
    expect(screen.queryByRole('dialog', {name: 'sulu_media.move_media'})).not.toBeInTheDocument();
    expect(mediaListStore.clearSelection).toHaveBeenCalled();
});

test('Should show generic error if upload of multiple files fails in MediaCollection', async() => {
    const user = userEvent.setup();
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    renderMediaOverview(router);
    setRouteValues(router, {id: 4, locale: 'de'});

    expect(screen.queryByRole('button', {name: /sulu_media\.upload_server_error/})).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'Report multiple upload errors'}));

    expect(screen.getByRole('button', {name: /sulu_media\.upload_server_error/})).toBeInTheDocument();
});

test('Should show error message from server if upload of a single file fails in MediaCollection', async() => {
    const user = userEvent.setup();
    const router = createRouter({
        attributes: {
            id: 4,
        },
        locales: ['de'],
    });
    renderMediaOverview(router);
    setRouteValues(router, {id: 4, locale: 'de'});

    expect(screen.queryByRole('button', {name: /Upload failed\./})).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'Report upload error'}));

    expect(screen.getByRole('button', {name: /Upload failed\./})).toBeInTheDocument();
});
