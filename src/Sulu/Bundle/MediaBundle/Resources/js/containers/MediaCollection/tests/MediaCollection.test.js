// @flow
import React from 'react';
import {act, fireEvent, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MediaCardOverviewAdapter from '../../List/adapters/MediaCardOverviewAdapter';
import CollectionSection from '../CollectionSection';
import MediaCollection from '../MediaCollection';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';
const SETTINGS_KEY = 'media_collection_test';
const USER_SETTINGS_KEY = 'media_overview';
let mockSingleListOverlaySelection = {id: 7};
let mockUploadPromise = Promise.resolve({});
let mockResourceStoreInstances = [];

function mockSingleListOverlay(props) {
    if (!props.open) {
        return null;
    }

    const handleConfirm = () => props.onConfirm(mockSingleListOverlaySelection);

    return (
        <div
            data-list-key={props.listKey}
            data-options={JSON.stringify(props.options)}
            data-reload-on-open={props.reloadOnOpen}
            data-resource-key={props.resourceKey}
            data-testid="single-list-overlay"
        >
            <div>{props.title}</div>
            {React.createElement(
                'button',
                {disabled: props.confirmLoading, onClick: handleConfirm, type: 'button'},
                'confirm list selection'
            )}
            <button onClick={props.onClose} type="button">close list selection</button>
        </div>
    );
}

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
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
            createFromFormKey: jest.fn(() => ({
                data: {},
                destroy: jest.fn(),
                schema: {},
                types: {},
                validate: jest.fn(() => true),
            })),
        },
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/List/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
        SingleListOverlay: jest.fn(mockSingleListOverlay),
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
        mockResourceStoreInstances.push(this);
    });

    return {
        ResourceStore: ResourceStoreMock,
    };
});

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(mockSingleListOverlay));

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.create = jest.fn(() => mockUploadPromise);
}));

jest.mock('../CollectionFormOverlay', () => jest.fn(function(props) {
    if (!['create', 'update'].includes(props.operationType)) {
        return null;
    }

    const React = require('react');

    return React.createElement(
        'div',
        {'data-testid': 'collection-form-overlay'},
        React.createElement('div', {}, props.operationType),
        React.createElement(
            'button',
            {onClick: () => props.onConfirm(props.resourceStore), type: 'button'},
            'confirm collection form'
        ),
        React.createElement('button', {onClick: props.onClose, type: 'button'}, 'close collection form')
    );
}));

jest.mock('../PermissionFormOverlay', () => jest.fn(function(props) {
    if (!props.open) {
        return null;
    }

    const React = require('react');

    return React.createElement(
        'div',
        {
            'data-collection-id': props.collectionId,
            'data-has-children': props.hasChildren,
            'data-testid': 'permission-form-overlay',
        },
        React.createElement('button', {onClick: props.onConfirm, type: 'button'}, 'confirm permission form'),
        React.createElement('button', {onClick: props.onClose, type: 'button'}, 'close permission form')
    );
}));

function createMediaCollectionTestProps(options: any = {}) {
    const page = observable.box();
    const locale = observable.box();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const CollectionStore = require('../../../stores/CollectionStore').default;

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
    const collectionStore = new CollectionStore(options.collectionId, locale);

    if (options.collectionId === undefined) {
        collectionStore.resourceStore.id = undefined;
    }

    if (options.collectionData) {
        collectionStore.resourceStore.data = options.collectionData;
    }

    const props = {
        collectionListStore,
        collectionStore,
        hideUploadAction: options.hideUploadAction || false,
        locale,
        mediaListAdapters: ['media_card_overview'],
        mediaListStore,
        onCollectionNavigate: options.onCollectionNavigate || jest.fn(),
        onDeleteError: options.onDeleteError,
        onMediaNavigate: options.onMediaNavigate,
        onUploadError: options.onUploadError,
        onUploadOverlayClose: options.onUploadOverlayClose || jest.fn(),
        onUploadOverlayOpen: options.onUploadOverlayOpen || jest.fn(),
        uploadOverlayOpen: options.uploadOverlayOpen || false,
    };

    return {
        collectionListStore,
        collectionStore,
        locale,
        mediaListStore,
        props,
    };
}

function renderMediaCollection(options: any = {}) {
    const testData = createMediaCollectionTestProps(options);

    return {
        ...testData,
        ...render(<MediaCollection {...testData.props} />),
    };
}

function renderCollectionSection(options: any = {}) {
    const locale = options.locale || observable.box();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const handleDeleteError = options.onDeleteError;
    const listStore = options.listStore || new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page: observable.box(),
            locale,
        }
    );
    const resourceStore = options.resourceStore || new ResourceStore(COLLECTIONS_RESOURCE_KEY);

    if (options.resourceData) {
        resourceStore.data = options.resourceData;
    }

    return {
        listStore,
        locale,
        resourceStore,
        ...render(
            <CollectionSection
                addable={options.addable !== undefined ? options.addable : true}
                deletable={options.deletable !== undefined ? options.deletable : true}
                editable={options.editable !== undefined ? options.editable : true}
                listStore={listStore}
                locale={locale}
                onCollectionNavigate={options.onCollectionNavigate || jest.fn()}
                onDeleteError={handleDeleteError}
                overlayType={options.overlayType || 'overlay'}
                resourceStore={resourceStore}
                securable={options.securable !== undefined ? options.securable : true}
            />
        ),
    };
}

function getFileInput() {
    const input = document.querySelector('input[type="file"]');

    if (!input) {
        throw new Error('File input not found');
    }

    return input;
}

function getDropzone() {
    const dropzone = getFileInput().parentElement;

    if (!dropzone) {
        throw new Error('Dropzone not found');
    }

    return dropzone;
}

function dragEnterDropzone() {
    const file = new File(['content'], 'test.jpg', {type: 'image/jpeg'});

    fireEvent.dragEnter(getDropzone(), {
        dataTransfer: {
            files: [file],
            items: [{getAsFile: () => file, kind: 'file', type: file.type}],
            types: ['Files'],
        },
    });
}

async function clickDropdownItem(user, label: string) {
    await user.click(screen.getByRole('button', {name: /su-cog/}));
    await user.click(screen.getByRole('button', {name: label}));
}

function getLatestCollectionResourceStore() {
    for (let index = mockResourceStoreInstances.length - 1; index >= 0; --index) {
        if (mockResourceStoreInstances[index].resourceKey === COLLECTIONS_RESOURCE_KEY) {
            return mockResourceStoreInstances[index];
        }
    }

    throw new Error('Collection resource store not found');
}

function finishDialogCloseTransition() {
    const dialogContainer = document.querySelector('.dialogContainer');

    if (dialogContainer) {
        fireEvent.transitionEnd(dialogContainer);
    }
}

beforeEach(() => {
    jest.clearAllMocks();
    MediaCollection.addable = true;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;
    MediaCollection.securable = true;
    mockSingleListOverlaySelection = {id: 7};
    mockUploadPromise = Promise.resolve({});
    mockResourceStoreInstances = [];

    const listAdapterRegistry = require('sulu-admin-bundle/containers/List/registries/listAdapterRegistry');

    // $FlowFixMe
    listAdapterRegistry.has.mockReturnValue(true);
    // $FlowFixMe
    listAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/List/adapters/FolderAdapter').default,
        'media_card_overview': MediaCardOverviewAdapter,
    });
});

test('Render the MediaCollection', () => {
    const {props} = createMediaCollectionTestProps({collectionId: 1});

    const {container} = render(<MediaCollection {...props} />);

    expect(container.innerHTML).toMatchSnapshot();
});

test('Render the MediaCollection without dropdown button when collection is a system collection', () => {
    renderMediaCollection({
        collectionData: {
            title: 'Title',
            locked: true,
            _permissions: {},
        },
    });

    expect(screen.queryByRole('button', {name: /su-plus/})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /su-cog/})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_media.upload_file/})).not.toBeInTheDocument();
});

test('Render the MediaCollection without dropdown button when permissions are missing', () => {
    MediaCollection.addable = false;
    MediaCollection.deletable = false;
    MediaCollection.editable = false;
    MediaCollection.securable = false;

    renderMediaCollection({collectionId: 1});

    expect(screen.queryByRole('button', {name: /su-plus/})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /su-cog/})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_media.upload_file/})).not.toBeInTheDocument();
});

test('Render the MediaCollection without add button when permission is missing', async() => {
    const user = userEvent.setup();
    renderCollectionSection({
        addable: false,
        deletable: true,
        editable: true,
        securable: true,
    });

    expect(screen.queryByRole('button', {name: /su-plus/})).not.toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: /su-cog/}));
    expect(screen.getByRole('button', {name: 'sulu_admin.edit'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_security.permissions'})).toBeInTheDocument();
});

test('Render the MediaCollection without delete button when permission is missing', async() => {
    const user = userEvent.setup();
    renderCollectionSection({
        addable: true,
        deletable: false,
        editable: true,
        securable: true,
    });

    expect(screen.getByRole('button', {name: /su-plus/})).toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: /su-cog/}));
    expect(screen.getByRole('button', {name: 'sulu_admin.edit'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_admin.delete'})).not.toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_security.permissions'})).toBeInTheDocument();
});

test('Render the MediaCollection without edit buttons when permission is missing', async() => {
    const user = userEvent.setup();
    renderCollectionSection({
        addable: true,
        deletable: true,
        editable: false,
        securable: true,
    });

    expect(screen.getByRole('button', {name: /su-plus/})).toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: /su-cog/}));
    expect(screen.queryByRole('button', {name: 'sulu_admin.edit'})).not.toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_admin.move'})).not.toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_security.permissions'})).toBeInTheDocument();
});

test('Render the MediaCollection without security buttons when permission is missing', async() => {
    const user = userEvent.setup();
    renderCollectionSection({
        addable: true,
        deletable: true,
        editable: true,
        securable: false,
    });

    expect(screen.getByRole('button', {name: /su-plus/})).toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: /su-cog/}));
    expect(screen.getByRole('button', {name: 'sulu_admin.edit'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_security.permissions'})).not.toBeInTheDocument();
});

test('Reload medias and fire onUploadError callback if an error happens while uploading a file', async() => {
    const user = userEvent.setup();
    const onUploadErrorSpy = jest.fn();
    const {mediaListStore} = renderMediaCollection({
        collectionId: 1,
        onUploadError: onUploadErrorSpy,
    });
    const errors = [
        {
            'code': 5003,
            'detail': 'The uploaded file exceeds the configured maximum filesize.',
        },
    ];

    expect(onUploadErrorSpy).not.toHaveBeenCalled();
    mockUploadPromise = Promise.reject(errors[0]);
    await user.upload(getFileInput(), new File(['content'], 'test.jpg', {type: 'image/jpeg'}));

    await waitFor(() => expect(onUploadErrorSpy).toHaveBeenCalledWith(errors));

    expect(mediaListStore.reload).toHaveBeenCalled();
});

test('Render the MediaCollection for all media', () => {
    const {collectionStore, props} = createMediaCollectionTestProps({collectionId: undefined});
    collectionStore.resourceStore.id = undefined;

    const {container} = render(<MediaCollection {...props} />);

    expect(container.innerHTML).toMatchSnapshot();
});

test('Pass correct options to SingleListOverlay for moving collections', async() => {
    const user = userEvent.setup();
    renderCollectionSection();

    await clickDropdownItem(user, 'sulu_admin.move');

    const overlay = screen.getByTestId('single-list-overlay');
    expect(overlay).toHaveAttribute('data-list-key', 'collections');
    expect(overlay).toHaveAttribute('data-resource-key', 'collections');
    expect(overlay).toHaveAttribute('data-reload-on-open', 'true');
});

test.each([true, false])(
    'Pass correct hasChildren "%s" option to PermissionFormOverlay',
    async(hasChildren) => {
        const user = userEvent.setup();
        const {resourceStore} = renderCollectionSection();
        mockExtendObservable(resourceStore.data, {
            hasChildren,
        });

        await clickDropdownItem(user, 'sulu_security.permissions');

        expect(screen.getByTestId('permission-form-overlay'))
            .toHaveAttribute('data-has-children', hasChildren.toString());
    }
);

test('Pass action for uploading new media to media list', async() => {
    const user = userEvent.setup();
    const uploadOverlayOpenSpy = jest.fn();
    const {collectionStore} = renderMediaCollection({
        onUploadOverlayOpen: uploadOverlayOpenSpy,
    });

    const uploadButton = screen.getByRole('button', {name: /sulu_media.upload_file/});
    expect(uploadButton).toBeEnabled();
    await user.click(uploadButton);
    expect(uploadOverlayOpenSpy).toHaveBeenCalledTimes(1);

    collectionStore.resourceStore.loading = true;

    expect(uploadButton).toBeDisabled();
});

test('Do not pass action for uploading new media to media list if hideUploadAction prop is set to true', () => {
    const uploadOverlayOpenSpy = jest.fn();
    const {
        props,
        rerender,
    } = renderMediaCollection({
        hideUploadAction: false,
        onUploadOverlayOpen: uploadOverlayOpenSpy,
    });

    expect(screen.getByRole('button', {name: /sulu_media.upload_file/})).toBeInTheDocument();

    rerender(<MediaCollection {...props} hideUploadAction={true} />);

    expect(screen.queryByRole('button', {name: /sulu_media.upload_file/})).not.toBeInTheDocument();
});

test('Do not pass action for uploading new media to media list if addable permission is set to false', () => {
    MediaCollection.addable = false;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    renderMediaCollection();

    expect(screen.queryByRole('button', {name: /sulu_media.upload_file/})).not.toBeInTheDocument();
});

test('Do not pass action for uploading new media to media list when collection is a system collection', () => {
    renderMediaCollection({
        collectionData: {
            title: 'Title',
            locked: true,
            _permissions: {},
        },
    });

    expect(screen.queryByRole('button', {name: /sulu_media.upload_file/})).not.toBeInTheDocument();
});

test('Disable dropzone if addable permission is set to false', async() => {
    const uploadOverlayOpenSpy = jest.fn();
    MediaCollection.addable = false;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    renderMediaCollection({onUploadOverlayOpen: uploadOverlayOpenSpy});

    dragEnterDropzone();
    await act(async() => Promise.resolve());
    expect(uploadOverlayOpenSpy).not.toHaveBeenCalled();
});

test('Disable dropzone when collection is loading', async() => {
    const uploadOverlayOpenSpy = jest.fn();
    MediaCollection.addable = true;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    const {collectionStore} = renderMediaCollection({onUploadOverlayOpen: uploadOverlayOpenSpy});

    dragEnterDropzone();
    await waitFor(() => expect(uploadOverlayOpenSpy).toHaveBeenCalledTimes(1));
    uploadOverlayOpenSpy.mockClear();

    collectionStore.resourceStore.loading = true;

    dragEnterDropzone();
    await act(async() => Promise.resolve());
    expect(uploadOverlayOpenSpy).not.toHaveBeenCalled();
});

test('Should send a request to add a new collection via the overlay', async() => {
    const user = userEvent.setup();
    const promise = Promise.resolve();
    const collectionNavigateSpy = jest.fn();
    const {resourceStore} = renderCollectionSection({
        onCollectionNavigate: collectionNavigateSpy,
        resourceData: {
            title: 'Title',
            _permissions: {},
        },
    });

    await user.click(screen.getByRole('button', {name: /su-plus/}));

    expect(resourceStore.clone).not.toHaveBeenCalled();
    expect(screen.getByTestId('collection-form-overlay')).toHaveTextContent('create');

    const newResourceStore = getLatestCollectionResourceStore();
    newResourceStore.save.mockReturnValue(promise);

    await user.click(screen.getByRole('button', {name: 'confirm collection form'}));

    await promise;
    expect(screen.queryByTestId('collection-form-overlay')).not.toBeInTheDocument();
    expect(newResourceStore.save).toHaveBeenCalledWith({breadcrumb: true});
    expect(newResourceStore.set).toHaveBeenCalledWith('parent', 1);
    expect(collectionNavigateSpy).toHaveBeenCalled();
    expect(resourceStore.setMultiple).not.toHaveBeenCalled();
});

test('Should send a request to update the collection via the overlay', async() => {
    const user = userEvent.setup();
    const promise = Promise.resolve();
    const collectionNavigateSpy = jest.fn();
    const {resourceStore} = renderCollectionSection({
        onCollectionNavigate: collectionNavigateSpy,
        resourceData: {
            title: 'Title',
            _permissions: {},
        },
    });

    await clickDropdownItem(user, 'sulu_admin.edit');

    const newResourceStore = getLatestCollectionResourceStore();
    newResourceStore.save.mockReturnValue(promise);
    expect(resourceStore.clone).toHaveBeenCalled();
    expect(newResourceStore.data.title).toEqual('Title');

    expect(screen.getByTestId('collection-form-overlay')).toHaveTextContent('update');

    await user.click(screen.getByRole('button', {name: 'confirm collection form'}));

    await promise;
    expect(screen.queryByTestId('collection-form-overlay')).not.toBeInTheDocument();
    expect(newResourceStore.save).toHaveBeenCalledWith({breadcrumb: true});
    expect(collectionNavigateSpy).not.toHaveBeenCalled();
    expect(resourceStore.setMultiple).toHaveBeenCalled();
});

test('Confirming the delete dialog should delete the item', async() => {
    const user = userEvent.setup();
    let resolveDelete: () => void = () => {};
    const promise = new Promise((resolve) => {
        resolveDelete = resolve;
    });
    const collectionNavigateSpy = jest.fn();
    const {resourceStore} = renderCollectionSection({
        onCollectionNavigate: collectionNavigateSpy,
    });
    resourceStore.delete.mockReturnValue(promise);

    await clickDropdownItem(user, 'sulu_admin.delete');

    expect(screen.getByText('sulu_media.remove_collection')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));
    resourceStore.deleting = true;

    expect(resourceStore.delete).toHaveBeenCalled();
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();

    await act(async() => {
        resolveDelete();
        await promise;
    });
    resourceStore.deleting = false;
    finishDialogCloseTransition();
    expect(collectionNavigateSpy).toHaveBeenCalledWith(undefined);
    expect(screen.queryByText('sulu_media.remove_collection')).not.toBeInTheDocument();
});

test('Confirming the delete dialog should delete the item and navigate to its parent', async() => {
    const user = userEvent.setup();
    const promise = Promise.resolve();
    const collectionNavigateSpy = jest.fn();
    const {resourceStore} = renderCollectionSection({
        onCollectionNavigate: collectionNavigateSpy,
        resourceData: {
            id: 1,
            _embedded: {
                parent: {
                    id: 3,
                },
            },
            _permissions: {},
        },
    });
    resourceStore.delete.mockImplementationOnce(() => {
        resourceStore.data = {};
        return promise;
    });

    await clickDropdownItem(user, 'sulu_admin.delete');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    await promise;
    expect(collectionNavigateSpy).toHaveBeenCalledWith(3);
});

test('Confirming the move dialog should move the item', async() => {
    const user = userEvent.setup();
    let resolveMove: () => void = () => {};
    const promise = new Promise((resolve) => {
        resolveMove = resolve;
    });
    const {resourceStore} = renderCollectionSection();
    resourceStore.move.mockReturnValue(promise);

    await clickDropdownItem(user, 'sulu_admin.move');

    const moveOverlay = screen.getByTestId('single-list-overlay');
    expect(moveOverlay).toHaveTextContent('sulu_media.move_collection');

    await user.click(screen.getByRole('button', {name: 'confirm list selection'}));
    resourceStore.moving = true;

    expect(resourceStore.move).toHaveBeenCalledWith(7);
    expect(moveOverlay).toHaveAttribute('data-options', JSON.stringify({includeRoot: true}));
    expect(screen.getByRole('button', {name: 'confirm list selection'})).toBeDisabled();

    await act(async() => {
        resolveMove();
        await promise;
    });
    resourceStore.moving = false;
    expect(screen.queryByTestId('single-list-overlay')).not.toBeInTheDocument();
    expect(resourceStore.reload).toHaveBeenCalledWith();
});

test('Confirming the move dialog should move the item after confirming the permission dialog', async() => {
    const user = userEvent.setup();
    let resolveMove: () => void = () => {};
    const promise = new Promise((resolve) => {
        resolveMove = resolve;
    });
    const {resourceStore} = renderCollectionSection();
    resourceStore.move.mockReturnValue(promise);
    mockSingleListOverlaySelection = {id: 7, _hasPermissions: true};

    await clickDropdownItem(user, 'sulu_admin.move');
    await user.click(screen.getByRole('button', {name: 'confirm list selection'}));

    expect(screen.getByText('sulu_security.move_permission_title')).toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.confirm'}));

    resourceStore.moving = true;

    expect(resourceStore.move).toHaveBeenCalledWith(7);
    expect(screen.getByRole('button', {name: 'confirm list selection'})).toBeDisabled();

    await act(async() => {
        resolveMove();
        await promise;
    });
    resourceStore.moving = false;
    expect(screen.queryByTestId('single-list-overlay')).not.toBeInTheDocument();
    expect(resourceStore.reload).toHaveBeenCalledWith();
});

test('Confirming the move dialog should not move the item after denying the permission dialog', async() => {
    const user = userEvent.setup();
    const {resourceStore} = renderCollectionSection();
    const promise = Promise.resolve();
    resourceStore.move.mockReturnValue(promise);
    mockSingleListOverlaySelection = {id: 7, _hasPermissions: true};

    await clickDropdownItem(user, 'sulu_admin.move');
    await user.click(screen.getByRole('button', {name: 'confirm list selection'}));
    expect(screen.getByText('sulu_security.move_permission_title')).toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_security.move_permission_title')).not.toBeInTheDocument();
    expect(screen.getByTestId('single-list-overlay')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'confirm list selection'})).toBeEnabled();
    expect(resourceStore.move).not.toHaveBeenCalled();
});

test('Confirming the permission overlay should reload the collection and close the overlay', async() => {
    const user = userEvent.setup();
    const {resourceStore} = renderCollectionSection();

    await clickDropdownItem(user, 'sulu_security.permissions');
    expect(screen.getByTestId('permission-form-overlay')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'confirm permission form'}));

    expect(resourceStore.reload).toHaveBeenCalledWith();
    expect(screen.queryByTestId('permission-form-overlay')).not.toBeInTheDocument();
});
