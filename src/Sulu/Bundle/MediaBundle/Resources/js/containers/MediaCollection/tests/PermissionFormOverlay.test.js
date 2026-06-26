// @flow
import React from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {memoryFormStoreFactory, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
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

jest.mock('sulu-admin-bundle/utils/Translator');

test('Create new ResourceFormStore when collectionId has changed', () => {
    const {rerender} = renderWithRef(
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
        // $FlowFixMe
        ResourceStore.mock.instances[0],
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

    // $FlowFixMe
    expect(ResourceStore.mock.instances[0].destroy).toHaveBeenCalledWith();
    // $FlowFixMe
    expect(ResourceFormStore.mock.instances[0].destroy).toHaveBeenCalledWith();

    expect(ResourceStore).toHaveBeenLastCalledWith('permissions', 3, {}, {resourceKey: 'media'});
    expect(ResourceFormStore).toHaveBeenLastCalledWith(
        // $FlowFixMe
        ResourceStore.mock.instances[1],
        'permission_details',
        {resourceKey: 'media'},
        undefined
    );
    expect(memoryFormStoreFactory.createFromFormKey).toHaveBeenLastCalledWith('permission_inheritance');
});

test('Call destroy of created stores', () => {
    const {instance, unmount} = renderWithRef(
        <PermissionFormOverlay
            collectionId={undefined}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    const formStore = instance.formStore;
    const resourceStore = instance.resourceStore;
    const inheritDialogFormStore = instance.inheritDialogFormStore;
    formStore.destroy = jest.fn();
    resourceStore.destroy = jest.fn();
    inheritDialogFormStore.destroy = jest.fn();

    unmount();
    expect(formStore.destroy).toHaveBeenCalledWith();
    expect(resourceStore.destroy).toHaveBeenCalledWith();
    expect(inheritDialogFormStore.destroy).toHaveBeenCalledWith();
});

test('Confirming dialog should save the current value and inherit it', () => {
    const confirmSpy = jest.fn();

    const {instance: permissionFormOverlay} = renderWithRef(
        <PermissionFormOverlay
            collectionId={undefined}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    const savePromise = Promise.resolve();
    permissionFormOverlay.resourceStore.save.mockReturnValue(savePromise);

    permissionFormOverlay.permissionFormRef = {
        submit: jest.fn((options) => permissionFormOverlay.handleSubmitPermission(options)),
    };
    permissionFormOverlay.inheritDialogFormRef = {
        submit: jest.fn(() => permissionFormOverlay.handleSubmitInherit()),
    };

    findElementByType(permissionFormOverlay.render(), 'Overlay').props.onConfirm();

    permissionFormOverlay.inheritDialogFormStore.data.inherit = true;
    findElementByType(permissionFormOverlay.render(), 'Dialog').props.onConfirm();

    expect(permissionFormOverlay.resourceStore.save).toHaveBeenCalledWith({
        inherit: true,
        resourceKey: 'media',
    });

    expect(confirmSpy).not.toHaveBeenCalled();
    return savePromise.then(() => {
        expect(confirmSpy).toHaveBeenCalledWith();
    });
});

test('Cancel inherit dialog should not save anything', () => {
    const confirmSpy = jest.fn();
    const closeSpy = jest.fn();

    const {instance: permissionFormOverlay} = renderWithRef(
        <PermissionFormOverlay
            collectionId={undefined}
            hasChildren={true}
            onClose={closeSpy}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    findElementByType(permissionFormOverlay.render(), 'Overlay').props.onConfirm();

    findElementByType(permissionFormOverlay.render(), 'Dialog').props.onCancel();

    expect(permissionFormOverlay.resourceStore.save).not.toHaveBeenCalled();

    expect(confirmSpy).not.toHaveBeenCalled();
    expect(closeSpy).not.toHaveBeenCalled();
});

test.each([
    [true],
    [false],
])('Pass saving prop of value "%s" to confirmLoading prop of Overlay', (saving) => {
    const {instance} = renderWithRef(
        <PermissionFormOverlay
            collectionId={1}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    instance.resourceStore.saving = saving;

    expect(findElementByType(instance.render(), 'Overlay').props.confirmLoading).toEqual(saving);
});

test.each([
    [true],
    [false],
])('Pass open prop of value "%s" to open prop of Overlay', (open) => {
    const {instance} = renderWithRef(
        <PermissionFormOverlay
            collectionId={1}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={open}
        />
    );

    expect(findElementByType(instance.render(), 'Overlay').props.open).toEqual(open);
});
