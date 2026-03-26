// @flow
import React from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {act, render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {memoryFormStoreFactory, ResourceFormStore} from 'sulu-admin-bundle/containers';
import PermissionFormOverlay from '../PermissionFormOverlay';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.save = jest.fn();

    mockExtendObservable(this, {
        saving: false,
    });
}));

jest.mock('sulu-admin-bundle/containers/Form/MissingTypeDialog', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.data = {};
    this.schema = {};
    this.validate = jest.fn().mockReturnValue(true);
    this.types = {};
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => ({
        data: {},
        destroy: jest.fn(),
        schema: {},
        validate: jest.fn(() => true),
    })),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

function getResourceStoreMockInstance(index: number = 0) {
    return (ResourceStore: any).mock.instances[index];
}

function getResourceFormStoreMockInstance(index: number = 0) {
    return (ResourceFormStore: any).mock.instances[index];
}

function getInheritDialogFormStoreMock(index: number = 0) {
    return (memoryFormStoreFactory.createFromFormKey: any).mock.results[index].value;
}

function getOverlaySection() {
    const overlay = screen.getByText('sulu_security.permissions').closest('section');
    if (!overlay) {
        throw new Error('Expected overlay section to exist');
    }

    return overlay;
}

function getInheritDialogSection() {
    const dialog = screen.getByText('sulu_security.inherit_permissions_title').closest('section');
    if (!dialog) {
        throw new Error('Expected inherit dialog section to exist');
    }

    return dialog;
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Create new ResourceFormStore when collectionId has changed', () => {
    const {rerender} = render(
        <PermissionFormOverlay
            collectionId={1}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(ResourceStore).toHaveBeenLastCalledWith('permissions', 1, {}, {resourceKey: 'media'});
    expect(ResourceFormStore).toHaveBeenLastCalledWith(
        getResourceStoreMockInstance(0),
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

    expect(getResourceStoreMockInstance(0).destroy).toBeCalledWith();
    expect(getResourceFormStoreMockInstance(0).destroy).toBeCalledWith();

    expect(ResourceStore).toHaveBeenLastCalledWith('permissions', 3, {}, {resourceKey: 'media'});
    expect(ResourceFormStore).toHaveBeenLastCalledWith(
        getResourceStoreMockInstance(1),
        'permission_details',
        {resourceKey: 'media'},
        undefined
    );
    expect(memoryFormStoreFactory.createFromFormKey).toHaveBeenLastCalledWith('permission_inheritance');
});

test('Call destroy of created stores', () => {
    const {unmount} = render(
        <PermissionFormOverlay
            collectionId={undefined}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    const formStore = getResourceFormStoreMockInstance(0);
    const resourceStore = getResourceStoreMockInstance(0);
    const inheritDialogFormStore = getInheritDialogFormStoreMock(0);

    unmount();
    expect(formStore.destroy).toBeCalledWith();
    expect(resourceStore.destroy).toBeCalledWith();
    expect(inheritDialogFormStore.destroy).toBeCalledWith();
});

test('Confirming dialog should save the current value and inherit it', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();

    render(
        <PermissionFormOverlay
            collectionId={undefined}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    let resolveSave;
    const savePromise = new Promise<void>((resolve) => {
        resolveSave = resolve;
    });
    const resourceStore = getResourceStoreMockInstance(0);
    const inheritDialogFormStore = getInheritDialogFormStoreMock(0);
    resourceStore.save.mockReturnValue(savePromise);

    await user.click(within(getOverlaySection()).getByRole('button', {name: 'sulu_admin.ok'}));

    act(() => {
        inheritDialogFormStore.data.inherit = true;
    });
    await user.click(within(getInheritDialogSection()).getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceStore.save).toBeCalledWith({inherit: true, resourceKey: 'media'});

    expect(confirmSpy).not.toBeCalled();

    if (!resolveSave) {
        throw new Error('Expected save resolver to exist');
    }
    resolveSave();
    await savePromise;
    await waitFor(() => expect(confirmSpy).toBeCalledWith());
});

test('Cancel inherit dialog should not save anything', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();
    const closeSpy = jest.fn();

    render(
        <PermissionFormOverlay
            collectionId={undefined}
            hasChildren={true}
            onClose={closeSpy}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    await user.click(within(getOverlaySection()).getByRole('button', {name: 'sulu_admin.ok'}));
    await user.click(within(getInheritDialogSection()).getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(getResourceStoreMockInstance(0).save).not.toBeCalled();

    expect(confirmSpy).not.toBeCalled();
    expect(closeSpy).not.toBeCalled();
});

test.each([
    [true],
    [false],
])('Pass saving prop of value "%s" to confirmLoading prop of Overlay', (saving) => {
    render(
        <PermissionFormOverlay
            collectionId={1}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    const resourceStore = getResourceStoreMockInstance(0);
    act(() => {
        resourceStore.saving = saving;
    });

    const overlayConfirmButton = within(getOverlaySection()).getByRole('button', {name: 'sulu_admin.ok'});
    if (saving) {
        expect(overlayConfirmButton).toBeDisabled();
    } else {
        expect(overlayConfirmButton).toBeEnabled();
    }
});

test.each([
    [true],
    [false],
])('Pass open prop of value "%s" to open prop of Overlay', (open) => {
    render(
        <PermissionFormOverlay
            collectionId={1}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={open}
        />
    );

    if (open) {
        expect(screen.getByText('sulu_security.permissions')).toBeInTheDocument();
    } else {
        expect(screen.queryByText('sulu_security.permissions')).not.toBeInTheDocument();
    }
});
