// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {memoryFormStoreFactory, ResourceFormStore} from 'sulu-admin-bundle/containers';
import PermissionFormOverlay from '../PermissionFormOverlay';

let mockResourceStoreInstances = [];
let mockResourceFormStoreInstances = [];

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.save = jest.fn();

    mockExtendObservable(this, {
        saving: false,
    });
    mockResourceStoreInstances.push(this);
}));

jest.mock('sulu-admin-bundle/containers/Form/MissingTypeDialog', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.data = {};
    this.schema = {};
    this.validate = jest.fn().mockReturnValue(true);
    this.types = {};
    mockResourceFormStoreInstances.push(this);
}));

let mockInheritDialogFormStores = [];

jest.mock('sulu-admin-bundle/containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => {
        const formStore = {
            data: {},
            destroy: jest.fn(),
            schema: {},
            validate: jest.fn(() => true),
        };

        mockInheritDialogFormStores.push(formStore);

        return formStore;
    }),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

const ResourceStoreMock = (ResourceStore: any);

beforeEach(() => {
    jest.clearAllMocks();
    mockInheritDialogFormStores = [];
    mockResourceStoreInstances = [];
    mockResourceFormStoreInstances = [];
});

function renderPermissionFormOverlay(props: Object = {}) {
    return render(
        <PermissionFormOverlay
            collectionId={undefined}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            {...props}
        />
    );
}

function getLatestResourceStore() {
    const store = mockResourceStoreInstances[mockResourceStoreInstances.length - 1];

    if (!store) {
        throw new Error('Expected ResourceStore instance');
    }

    return store;
}

function getLatestResourceFormStore() {
    const store = mockResourceFormStoreInstances[mockResourceFormStoreInstances.length - 1];

    if (!store) {
        throw new Error('Expected ResourceFormStore instance');
    }

    return store;
}

function getLatestInheritDialogFormStore() {
    const store = mockInheritDialogFormStores[mockInheritDialogFormStores.length - 1];

    if (!store) {
        throw new Error('Expected inherit dialog form store');
    }

    return store;
}

function mockResourceStore(saving: boolean) {
    ResourceStoreMock.mockImplementationOnce(function() {
        this.destroy = jest.fn();
        this.save = jest.fn();

        mockExtendObservable(this, {
            saving,
        });
        mockResourceStoreInstances.push(this);
    });
}

function getLastButtonByName(name: string): HTMLElement {
    const buttons = screen.getAllByRole('button', {name});
    const button = buttons[buttons.length - 1];

    if (!(button instanceof HTMLElement)) {
        throw new Error('Expected button');
    }

    return button;
}

test('Create new ResourceFormStore when collectionId has changed', () => {
    const {rerender} = renderPermissionFormOverlay({collectionId: 1});

    expect(ResourceStore).toHaveBeenLastCalledWith('permissions', 1, {}, {resourceKey: 'media'});
    expect(ResourceFormStore).toHaveBeenLastCalledWith(
        mockResourceStoreInstances[0],
        'permission_details',
        {resourceKey: 'media'},
        undefined
    );

    rerender(
        <PermissionFormOverlay
            collectionId={3}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mockResourceStoreInstances[0].destroy).toHaveBeenCalledWith();
    expect(mockResourceFormStoreInstances[0].destroy).toHaveBeenCalledWith();

    expect(ResourceStore).toHaveBeenLastCalledWith('permissions', 3, {}, {resourceKey: 'media'});
    expect(ResourceFormStore).toHaveBeenLastCalledWith(
        mockResourceStoreInstances[1],
        'permission_details',
        {resourceKey: 'media'},
        undefined
    );
    expect(memoryFormStoreFactory.createFromFormKey).toHaveBeenLastCalledWith('permission_inheritance');
});

test('Call destroy of created stores', () => {
    const {unmount} = renderPermissionFormOverlay();
    const formStore = getLatestResourceFormStore();
    const resourceStore = getLatestResourceStore();
    const inheritDialogFormStore = getLatestInheritDialogFormStore();

    unmount();
    expect(formStore.destroy).toHaveBeenCalledWith();
    expect(resourceStore.destroy).toHaveBeenCalledWith();
    expect(inheritDialogFormStore.destroy).toHaveBeenCalledWith();
});

test('Confirming dialog should save the current value and inherit it', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();
    let resolveSavePromise = () => {};

    renderPermissionFormOverlay({onConfirm: confirmSpy});

    const savePromise = new Promise((resolve) => {
        resolveSavePromise = resolve;
    });
    const resourceStore = getLatestResourceStore();
    resourceStore.save.mockReturnValue(savePromise);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    getLatestInheritDialogFormStore().data.inherit = true;
    await user.click(getLastButtonByName('sulu_admin.ok'));

    expect(resourceStore.save).toHaveBeenCalledWith({
        inherit: true,
        resourceKey: 'media',
    });

    expect(confirmSpy).not.toHaveBeenCalled();
    resolveSavePromise();
    await savePromise;

    expect(confirmSpy).toHaveBeenCalledWith();
});

test('Cancel inherit dialog should not save anything', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();
    const closeSpy = jest.fn();

    renderPermissionFormOverlay({onClose: closeSpy, onConfirm: confirmSpy});
    const resourceStore = getLatestResourceStore();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(resourceStore.save).not.toHaveBeenCalled();

    expect(confirmSpy).not.toHaveBeenCalled();
    expect(closeSpy).not.toHaveBeenCalled();
});

test.each([
    [true],
    [false],
])('Pass saving prop of value "%s" to confirmLoading prop of Overlay', (saving) => {
    mockResourceStore(saving);

    renderPermissionFormOverlay({collectionId: 1});

    if (saving) {
        expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();
    } else {
        expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeEnabled();
    }
});

test.each([
    [true],
    [false],
])('Pass open prop of value "%s" to open prop of Overlay', (open) => {
    renderPermissionFormOverlay({collectionId: 1, open});

    if (open) {
        expect(screen.getByText('sulu_security.permissions')).toBeInTheDocument();
    } else {
        expect(screen.queryByText('sulu_security.permissions')).not.toBeInTheDocument();
    }
});
