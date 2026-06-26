// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {RequestPromise} from 'sulu-admin-bundle/services/ResourceRequester';
import {
    findAllElementsByType,
    findElementByType,
    renderWithRef,
} from 'sulu-admin-bundle/utils/TestHelper';
import MediaCardOverviewAdapter from '../../List/adapters/MediaCardOverviewAdapter';
import CollectionSection from '../CollectionSection';
import MediaCollection from '../MediaCollection';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';
const SETTINGS_KEY = 'media_collection_test';
const USER_SETTINGS_KEY = 'media_overview';

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

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(() => null));

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
        ...renderWithRef(<MediaCollection {...testData.props} />),
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
        ...renderWithRef(
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

function getDropzoneProps(mediaCollection) {
    return findElementByType(mediaCollection.render(), 'MultiMediaDropzone').props;
}

function getCollectionSectionProps(mediaCollection) {
    return findElementByType(mediaCollection.render(), CollectionSection).props;
}

function getMediaListProps(mediaCollection) {
    const List = require('sulu-admin-bundle/containers').List;

    return findElementByType(mediaCollection.render(), List).props;
}

function getButtons(collectionSection) {
    return findAllElementsByType(collectionSection.render(), 'Button');
}

function getButton(collectionSection, icon) {
    return getButtons(collectionSection).find((button) => button.props.icon === icon);
}

function clickButton(collectionSection, icon) {
    const button = getButton(collectionSection, icon);

    expect(button).toBeDefined();
    button && button.props.onClick();
}

function getDropdownButton(collectionSection) {
    return findAllElementsByType(collectionSection.render(), 'DropdownButton')[0];
}

function getDropdownItems(collectionSection) {
    const dropdownButton = getDropdownButton(collectionSection);

    if (!dropdownButton) {
        return [];
    }

    return React.Children.toArray(dropdownButton.props.children);
}

function clickDropdownItem(collectionSection, label) {
    const item = getDropdownItems(collectionSection).find((item) => item.props.children === label);

    expect(item).toBeDefined();
    item && item.props.onClick();
}

function getCollectionFormOverlayProps(collectionSection) {
    return findElementByType(collectionSection.render(), 'CollectionFormOverlay').props;
}

function isCollectionFormOverlayOpen(collectionSection) {
    return ['create', 'update'].includes(getCollectionFormOverlayProps(collectionSection).operationType);
}

function getDeleteDialogProps(collectionSection) {
    const dialog = findAllElementsByType(collectionSection.render(), 'Dialog')
        .find((dialog) => dialog.props.title === 'sulu_media.remove_collection');

    if (!dialog) {
        throw new Error('Delete dialog not found!');
    }

    return dialog.props;
}

function getMovePermissionDialogProps(collectionSection) {
    const dialog = findAllElementsByType(collectionSection.render(), 'Dialog')
        .find((dialog) => dialog.props.title === 'sulu_security.move_permission_title');

    if (!dialog) {
        throw new Error('Move permission dialog not found!');
    }

    return dialog.props;
}

function getPermissionFormOverlayProps(collectionSection) {
    return findElementByType(collectionSection.render(), 'PermissionFormOverlay').props;
}

function getMoveCollectionOverlayProps(collectionSection) {
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;

    const overlay = findAllElementsByType(collectionSection.render(), SingleListOverlay)
        .find((overlay) => overlay.props.title === 'sulu_media.move_collection');

    if (!overlay) {
        throw new Error('Move collection overlay not found!');
    }

    return overlay.props;
}

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

test('Render the MediaCollection', () => {
    const {props} = createMediaCollectionTestProps({collectionId: 1});

    const {container} = render(<MediaCollection {...props} />);

    expect(container.innerHTML).toMatchSnapshot();
});

test('Render the MediaCollection without dropdown button when collection is a system collection', () => {
    const {instance: mediaCollection} = renderMediaCollection({
        collectionData: {
            title: 'Title',
            locked: true,
            _permissions: {},
        },
    });

    const collectionSectionProps = getCollectionSectionProps(mediaCollection);

    expect(collectionSectionProps.addable).toEqual(false);
    expect(collectionSectionProps.deletable).toEqual(false);
    expect(collectionSectionProps.editable).toEqual(false);
    expect(collectionSectionProps.securable).toEqual(false);

    const {instance: collectionSection} = renderCollectionSection(collectionSectionProps);

    expect(getButton(collectionSection, 'su-plus')).toBeUndefined();
    expect(getDropdownButton(collectionSection)).toBeUndefined();
});

test('Render the MediaCollection without dropdown button when permissions are missing', () => {
    MediaCollection.addable = false;
    MediaCollection.deletable = false;
    MediaCollection.editable = false;
    MediaCollection.securable = false;

    const {instance: mediaCollection} = renderMediaCollection({collectionId: 1});
    const collectionSectionProps = getCollectionSectionProps(mediaCollection);

    expect(collectionSectionProps.addable).toEqual(false);
    expect(collectionSectionProps.deletable).toEqual(false);
    expect(collectionSectionProps.editable).toEqual(false);
    expect(collectionSectionProps.securable).toEqual(false);

    const {instance: collectionSection} = renderCollectionSection(collectionSectionProps);

    expect(getButton(collectionSection, 'su-plus')).toBeUndefined();
    expect(getDropdownButton(collectionSection)).toBeUndefined();
});

test('Render the MediaCollection without add button when permission is missing', () => {
    const {instance: collectionSection} = renderCollectionSection({
        addable: false,
        deletable: true,
        editable: true,
        securable: true,
    });

    expect(getButton(collectionSection, 'su-plus')).toBeUndefined();
    expect(getDropdownItems(collectionSection).map((item) => item.props.children)).toEqual([
        'sulu_admin.edit',
        'sulu_admin.delete',
        'sulu_admin.move',
        'sulu_security.permissions',
    ]);
});

test('Render the MediaCollection without delete button when permission is missing', () => {
    const {instance: collectionSection} = renderCollectionSection({
        addable: true,
        deletable: false,
        editable: true,
        securable: true,
    });

    expect(getButton(collectionSection, 'su-plus')).toBeDefined();
    expect(getDropdownItems(collectionSection).map((item) => item.props.children)).toEqual([
        'sulu_admin.edit',
        'sulu_admin.move',
        'sulu_security.permissions',
    ]);
});

test('Render the MediaCollection without edit buttons when permission is missing', () => {
    const {instance: collectionSection} = renderCollectionSection({
        addable: true,
        deletable: true,
        editable: false,
        securable: true,
    });

    expect(getButton(collectionSection, 'su-plus')).toBeDefined();
    expect(getDropdownItems(collectionSection).map((item) => item.props.children)).toEqual([
        'sulu_admin.delete',
        'sulu_security.permissions',
    ]);
});

test('Render the MediaCollection without security buttons when permission is missing', () => {
    const {instance: collectionSection} = renderCollectionSection({
        addable: true,
        deletable: true,
        editable: true,
        securable: false,
    });

    expect(getButton(collectionSection, 'su-plus')).toBeDefined();
    expect(getDropdownItems(collectionSection).map((item) => item.props.children)).toEqual([
        'sulu_admin.edit',
        'sulu_admin.delete',
        'sulu_admin.move',
    ]);
});

test('Reload medias and fire onUploadError callback if an error happens while uploading a file', () => {
    const onUploadErrorSpy = jest.fn();
    const {instance: mediaCollection, mediaListStore} = renderMediaCollection({
        onUploadError: onUploadErrorSpy,
    });
    const errors = [
        {
            'code': 5003,
            'detail': 'The uploaded file exceeds the configured maximum filesize.',
        },
    ];

    expect(onUploadErrorSpy).not.toHaveBeenCalled();

    getDropzoneProps(mediaCollection).onUploadError(errors);

    expect(onUploadErrorSpy).toHaveBeenCalledWith(errors);
    expect(mediaListStore.reload).toHaveBeenCalled();
});

test('Render the MediaCollection for all media', () => {
    const {collectionStore, props} = createMediaCollectionTestProps({collectionId: undefined});
    collectionStore.resourceStore.id = undefined;

    const {container} = render(<MediaCollection {...props} />);

    expect(container.innerHTML).toMatchSnapshot();
});

test('Pass correct options to SingleListOverlay for moving collections', () => {
    const {instance: collectionSection} = renderCollectionSection();
    const moveCollectionOverlay = getMoveCollectionOverlayProps(collectionSection);

    expect(moveCollectionOverlay.listKey).toEqual('collections');
    expect(moveCollectionOverlay.resourceKey).toEqual('collections');
    expect(moveCollectionOverlay.reloadOnOpen).toEqual(true);
});

test.each([true, false])('Pass correct hasChildren "%s" option to PermissionFormOverlay', (hasChildren) => {
    const {resourceStore, instance: collectionSection} = renderCollectionSection();
    mockExtendObservable(resourceStore.data, {
        hasChildren,
    });

    expect(getPermissionFormOverlayProps(collectionSection).hasChildren).toEqual(hasChildren);
});

test('Pass action for uploading new media to media list', () => {
    const uploadOverlayOpenSpy = jest.fn();
    const {collectionStore, instance: mediaCollection} = renderMediaCollection({
        onUploadOverlayOpen: uploadOverlayOpenSpy,
    });

    const mediaListActions = getMediaListProps(mediaCollection).actions;
    expect(mediaListActions).toHaveLength(1);
    expect(mediaListActions[0].label).toEqual('sulu_media.upload_file');
    expect(mediaListActions[0].onClick).toEqual(uploadOverlayOpenSpy);
    expect(mediaListActions[0].disabled).toBeFalsy();

    collectionStore.resourceStore.loading = true;

    expect(getMediaListProps(mediaCollection).actions[0].disabled).toBeTruthy();
});

test('Do not pass action for uploading new media to media list if hideUploadAction prop is set to true', () => {
    const uploadOverlayOpenSpy = jest.fn();
    const {
        props,
        instance: mediaCollection,
        rerender,
    } = renderMediaCollection({
        hideUploadAction: false,
        onUploadOverlayOpen: uploadOverlayOpenSpy,
    });

    expect(getMediaListProps(mediaCollection).actions).toHaveLength(1);

    rerender(<MediaCollection {...props} hideUploadAction={true} />);

    expect(getMediaListProps(mediaCollection).actions).toHaveLength(0);
});

test('Do not pass action for uploading new media to media list if addable permission is set to false', () => {
    MediaCollection.addable = false;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    const {instance: mediaCollection} = renderMediaCollection();

    expect(getMediaListProps(mediaCollection).actions).toHaveLength(0);
});

test('Do not pass action for uploading new media to media list when collection is a system collection', () => {
    const {instance: mediaCollection} = renderMediaCollection({
        collectionData: {
            title: 'Title',
            locked: true,
            _permissions: {},
        },
    });

    expect(getMediaListProps(mediaCollection).actions).toHaveLength(0);
});

test('Disable dropzone if addable permission is set to false', () => {
    MediaCollection.addable = false;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    const {instance: mediaCollection} = renderMediaCollection();

    expect(getDropzoneProps(mediaCollection).disabled).toBeTruthy();
});

test('Disable dropzone when collection is loading', () => {
    MediaCollection.addable = true;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    const {collectionStore, instance: mediaCollection} = renderMediaCollection();

    expect(getDropzoneProps(mediaCollection).disabled).toBeFalsy();

    collectionStore.resourceStore.loading = true;

    expect(getDropzoneProps(mediaCollection).disabled).toBeTruthy();
});

test('Should send a request to add a new collection via the overlay', () => {
    const promise = Promise.resolve();
    const collectionNavigateSpy = jest.fn();
    const {resourceStore, instance: collectionSection} = renderCollectionSection({
        onCollectionNavigate: collectionNavigateSpy,
        resourceData: {
            title: 'Title',
            _permissions: {},
        },
    });

    clickButton(collectionSection, 'su-plus');

    expect(resourceStore.clone).not.toHaveBeenCalled();
    expect(getDeleteDialogProps(collectionSection).open).toEqual(false);
    expect(isCollectionFormOverlayOpen(collectionSection)).toEqual(true);

    const newResourceStore = getCollectionFormOverlayProps(collectionSection).resourceStore;
    newResourceStore.save.mockReturnValue(promise);

    getCollectionFormOverlayProps(collectionSection).onConfirm(newResourceStore);

    return promise.then(() => {
        expect(isCollectionFormOverlayOpen(collectionSection)).toEqual(false);
        expect(newResourceStore.save).toHaveBeenCalledWith({
            breadcrumb: true,
        });
        expect(newResourceStore.set).toHaveBeenCalledWith('parent', 1);
        expect(collectionNavigateSpy).toHaveBeenCalled();
        expect(resourceStore.setMultiple).not.toHaveBeenCalled();
    });
});

test('Should send a request to update the collection via the overlay', () => {
    const promise = Promise.resolve();
    const collectionNavigateSpy = jest.fn();
    const {resourceStore, instance: collectionSection} = renderCollectionSection({
        onCollectionNavigate: collectionNavigateSpy,
        resourceData: {
            title: 'Title',
            _permissions: {},
        },
    });

    clickDropdownItem(collectionSection, 'sulu_admin.edit');

    const newResourceStore = getCollectionFormOverlayProps(collectionSection).resourceStore;
    newResourceStore.save.mockReturnValue(promise);
    expect(resourceStore.clone).toHaveBeenCalled();
    expect(newResourceStore.data.title).toEqual('Title');

    expect(getDeleteDialogProps(collectionSection).open).toEqual(false);
    expect(isCollectionFormOverlayOpen(collectionSection)).toEqual(true);

    getCollectionFormOverlayProps(collectionSection).onConfirm(newResourceStore);

    return promise.then(() => {
        expect(isCollectionFormOverlayOpen(collectionSection)).toEqual(false);
        expect(newResourceStore.save).toHaveBeenCalledWith({breadcrumb: true});
        expect(collectionNavigateSpy).not.toHaveBeenCalled();
        expect(resourceStore.setMultiple).toHaveBeenCalled();
    });
});

test('Confirming the delete dialog should delete the item', () => {
    const promise = Promise.resolve();
    const collectionNavigateSpy = jest.fn();
    const {resourceStore, instance: collectionSection} = renderCollectionSection({
        onCollectionNavigate: collectionNavigateSpy,
    });
    resourceStore.delete.mockReturnValue(promise);

    clickDropdownItem(collectionSection, 'sulu_admin.delete');

    expect(getDeleteDialogProps(collectionSection).open).toEqual(true);
    expect(isCollectionFormOverlayOpen(collectionSection)).toEqual(false);

    getDeleteDialogProps(collectionSection).onConfirm();
    resourceStore.deleting = true;

    expect(resourceStore.delete).toHaveBeenCalled();
    expect(getDeleteDialogProps(collectionSection).open).toEqual(true);
    expect(getDeleteDialogProps(collectionSection).confirmLoading).toEqual(true);

    return promise.then(() => {
        resourceStore.deleting = false;
        expect(collectionNavigateSpy).toHaveBeenCalledWith(undefined);
        expect(getDeleteDialogProps(collectionSection).open).toEqual(false);
        expect(getDeleteDialogProps(collectionSection).confirmLoading).toEqual(false);
    });
});

test('Confirming the delete dialog should delete the item and navigate to its parent', () => {
    const promise = Promise.resolve();
    const collectionNavigateSpy = jest.fn();
    const {resourceStore, instance: collectionSection} = renderCollectionSection({
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

    clickDropdownItem(collectionSection, 'sulu_admin.delete');
    getDeleteDialogProps(collectionSection).onConfirm();

    return promise.then(() => {
        expect(collectionNavigateSpy).toHaveBeenCalledWith(3);
    });
});

test('Confirming the move dialog should move the item', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
    const {resourceStore, instance: collectionSection} = renderCollectionSection();
    resourceStore.move.mockReturnValue(promise);

    clickDropdownItem(collectionSection, 'sulu_admin.move');

    expect(getDeleteDialogProps(collectionSection).open).toEqual(false);
    expect(isCollectionFormOverlayOpen(collectionSection)).toEqual(false);
    expect(getMoveCollectionOverlayProps(collectionSection).open).toEqual(true);

    getMoveCollectionOverlayProps(collectionSection).onConfirm({id: 7});
    resourceStore.moving = true;

    expect(resourceStore.move).toHaveBeenCalledWith(7);
    expect(getMoveCollectionOverlayProps(collectionSection).open).toEqual(true);
    expect(getMoveCollectionOverlayProps(collectionSection).options).toEqual({includeRoot: true});
    expect(getMoveCollectionOverlayProps(collectionSection).confirmLoading).toEqual(true);

    return promise.then(() => {
        resourceStore.moving = false;
        expect(getMoveCollectionOverlayProps(collectionSection).open).toEqual(false);
        expect(getMoveCollectionOverlayProps(collectionSection).confirmLoading).toEqual(false);
        expect(resourceStore.reload).toHaveBeenCalledWith();
    });
});

test('Confirming the move dialog should move the item after confirming the permission dialog', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
    const {resourceStore, instance: collectionSection} = renderCollectionSection();
    resourceStore.move.mockReturnValue(promise);

    clickDropdownItem(collectionSection, 'sulu_admin.move');

    expect(getDeleteDialogProps(collectionSection).open).toEqual(false);
    expect(isCollectionFormOverlayOpen(collectionSection)).toEqual(false);
    expect(getMoveCollectionOverlayProps(collectionSection).open).toEqual(true);

    expect(getMovePermissionDialogProps(collectionSection).open).toEqual(false);
    getMoveCollectionOverlayProps(collectionSection).onConfirm({id: 7, _hasPermissions: true});
    expect(getMovePermissionDialogProps(collectionSection).open).toEqual(true);

    getMovePermissionDialogProps(collectionSection).onConfirm();

    resourceStore.moving = true;

    expect(resourceStore.move).toHaveBeenCalledWith(7);
    expect(getMoveCollectionOverlayProps(collectionSection).open).toEqual(true);
    expect(getMoveCollectionOverlayProps(collectionSection).options).toEqual({includeRoot: true});
    expect(getMoveCollectionOverlayProps(collectionSection).confirmLoading).toEqual(true);

    return promise.then(() => {
        resourceStore.moving = false;
        expect(getMoveCollectionOverlayProps(collectionSection).open).toEqual(false);
        expect(getMoveCollectionOverlayProps(collectionSection).confirmLoading).toEqual(false);
        expect(resourceStore.reload).toHaveBeenCalledWith();
    });
});

test('Confirming the move dialog should not move the item after denying the permission dialog', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
    const {resourceStore, instance: collectionSection} = renderCollectionSection();
    resourceStore.move.mockReturnValue(promise);

    clickDropdownItem(collectionSection, 'sulu_admin.move');

    expect(getDeleteDialogProps(collectionSection).open).toEqual(false);
    expect(isCollectionFormOverlayOpen(collectionSection)).toEqual(false);
    expect(getMoveCollectionOverlayProps(collectionSection).open).toEqual(true);

    expect(getMovePermissionDialogProps(collectionSection).open).toEqual(false);
    getMoveCollectionOverlayProps(collectionSection).onConfirm({id: 7, _hasPermissions: true});
    expect(getMovePermissionDialogProps(collectionSection).open).toEqual(true);

    getMovePermissionDialogProps(collectionSection).onCancel();

    expect(getMovePermissionDialogProps(collectionSection).open).toEqual(false);
    expect(getMoveCollectionOverlayProps(collectionSection).open).toEqual(true);
    expect(getMoveCollectionOverlayProps(collectionSection).confirmLoading).toEqual(false);
    expect(resourceStore.move).not.toHaveBeenCalled();
});

test('Confirming the permission overlay should reload the collection and close the overlay', () => {
    const {resourceStore, instance: collectionSection} = renderCollectionSection();

    clickDropdownItem(collectionSection, 'sulu_security.permissions');
    expect(getPermissionFormOverlayProps(collectionSection).open).toEqual(true);

    getPermissionFormOverlayProps(collectionSection).onConfirm();

    expect(resourceStore.reload).toHaveBeenCalledWith();
    expect(getPermissionFormOverlayProps(collectionSection).open).toEqual(false);
});
