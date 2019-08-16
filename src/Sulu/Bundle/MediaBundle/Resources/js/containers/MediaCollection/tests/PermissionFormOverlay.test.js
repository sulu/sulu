// @flow
import React from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {mount, shallow} from 'enzyme';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {ResourceFormStore} from 'sulu-admin-bundle/containers';
import PermissionFormOverlay from '../PermissionFormOverlay';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.save = jest.fn();

    mockExtendObservable(this, {
        saving: false,
    });
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.data = {};
    this.schema = {};
    this.validate = jest.fn().mockReturnValue(true);
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

test('Create new ResourceFormStore when collectionId has changed', () => {
    const permissionFormOverlay = shallow(
        <PermissionFormOverlay collectionId={1} onClose={jest.fn()} onConfirm={jest.fn()} open={true} />
    );

    expect(ResourceStore).toHaveBeenLastCalledWith('permissions', 1, {}, {resourceKey: 'media'});
    expect(ResourceFormStore).toHaveBeenLastCalledWith(
        // $FlowFixMe
        ResourceStore.mock.instances[0],
        'permission_details',
        {resourceKey: 'media'}
    );

    permissionFormOverlay.setProps({collectionId: 3});

    // $FlowFixMe
    expect(ResourceStore.mock.instances[0].destroy).toBeCalledWith();
    // $FlowFixMe
    expect(ResourceFormStore.mock.instances[0].destroy).toBeCalledWith();

    expect(ResourceStore).toHaveBeenLastCalledWith('permissions', 3, {}, {resourceKey: 'media'});
    expect(ResourceFormStore).toHaveBeenLastCalledWith(
        // $FlowFixMe
        ResourceStore.mock.instances[1],
        'permission_details',
        {resourceKey: 'media'}
    );
});

test('Call destroy of created ResourceFormStore and ResourceStore', () => {
    const permissionFormOverlay = shallow(
        <PermissionFormOverlay collectionId={undefined} onClose={jest.fn()} onConfirm={jest.fn()} open={true} />
    );

    const formStore = permissionFormOverlay.instance().formStore;
    const resourceStore = permissionFormOverlay.instance().resourceStore;
    formStore.destroy = jest.fn();
    resourceStore.destroy = jest.fn();

    permissionFormOverlay.unmount();
    expect(formStore.destroy).toBeCalledWith();
    expect(resourceStore.destroy).toBeCalledWith();
});

test('Confirming dialog should save the current value', () => {
    const confirmSpy = jest.fn();

    const permissionFormOverlay = mount(
        <PermissionFormOverlay collectionId={undefined} onClose={jest.fn()} onConfirm={confirmSpy} open={true} />
    );

    const savePromise = Promise.resolve();
    permissionFormOverlay.instance().resourceStore.save.mockReturnValue(savePromise);

    permissionFormOverlay.update();

    permissionFormOverlay.find('Overlay').prop('onConfirm')();
    permissionFormOverlay.update();
    expect(permissionFormOverlay.instance().resourceStore.save).toBeCalledWith({resourceKey: 'media'});

    expect(confirmSpy).not.toBeCalled();
    return savePromise.then(() => {
        permissionFormOverlay.update();
        expect(confirmSpy).toBeCalledWith();
    });
});

test.each([
    [true],
    [false],
])('Pass saving prop of value "%s" to confirmLoading prop of Overlay', (saving) => {
    const permissionFormOverlay = shallow(
        <PermissionFormOverlay collectionId={1} onClose={jest.fn()} onConfirm={jest.fn()} open={true} />
    );

    permissionFormOverlay.instance().resourceStore.saving = saving;
    permissionFormOverlay.update();

    expect(permissionFormOverlay.find('Overlay').prop('confirmLoading')).toEqual(saving);
});

test.each([
    [true],
    [false],
])('Pass open prop of value "%s" to open prop of Overlay', (open) => {
    const permissionFormOverlay = shallow(
        <PermissionFormOverlay collectionId={1} onClose={jest.fn()} onConfirm={jest.fn()} open={open} />
    );

    expect(permissionFormOverlay.find('Overlay').prop('open')).toEqual(open);
});
