// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import {resourceFormStoreFactory, memoryFormStoreFactory} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {getLatestMockProps} from 'sulu-admin-bundle/utils/TestHelper';
import PermissionFormOverlay from '../PermissionFormOverlay';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.save = jest.fn();

    mockExtendObservable(this, {
        saving: false,
    });
}));

jest.mock('sulu-admin-bundle/containers', () => {
    const React = require('react');

    class Form extends React.Component<*> {
        submit = (options) => {
            if (this.props.onSubmit) {
                this.props.onSubmit(options);
            }
        };

        render() {
            return React.createElement('div');
        }
    }

    return {
        Form,
        memoryFormStoreFactory: {
            createFromFormKey: jest.fn(() => ({
                data: {},
                destroy: jest.fn(),
                schema: {},
                validate: jest.fn(() => true),
            })),
        },
        resourceFormStoreFactory: {
            createFromResourceStore: jest.fn(() => ({
                data: {},
                destroy: jest.fn(),
                schema: {},
                validate: jest.fn(() => true),
            })),
        },
    };
});

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const Overlay = jest.fn(function OverlayMock(props) {
        return React.createElement(
            'div',
            {'data-testid': 'overlay'},
            props.children,
            React.createElement('button', {onClick: props.onConfirm, type: 'button'}, 'overlay-confirm')
        );
    });

    const Dialog = jest.fn(function DialogMock(props) {
        return React.createElement(
            'div',
            {'data-open': props.open ? 'true' : 'false', 'data-testid': 'dialog'},
            props.children,
            React.createElement('button', {onClick: props.onConfirm, type: 'button'}, 'dialog-confirm'),
            React.createElement('button', {onClick: props.onCancel, type: 'button'}, 'dialog-cancel')
        );
    });

    return {Dialog, Overlay};
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

const resourceStoreMock = (ResourceStore: any);
const resourceFormStoreFactoryMock = (resourceFormStoreFactory: any);
const memoryFormStoreFactoryMock = (memoryFormStoreFactory: any);

function getLatestOverlayProps(): any {
    return getLatestMockProps(((jest.requireMock('sulu-admin-bundle/components'): any).Overlay: any));
}

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
    expect(resourceFormStoreFactory.createFromResourceStore).toHaveBeenLastCalledWith(
        resourceStoreMock.mock.instances[0],
        'permission_details',
        {resourceKey: 'media'}
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

    expect(resourceStoreMock.mock.instances[0].destroy).toHaveBeenCalledWith();
    expect(resourceFormStoreFactoryMock.createFromResourceStore.mock.results[0].value.destroy).toHaveBeenCalledWith();
    expect(memoryFormStoreFactoryMock.createFromFormKey.mock.results[0].value.destroy).toHaveBeenCalledWith();

    expect(ResourceStore).toHaveBeenLastCalledWith('permissions', 3, {}, {resourceKey: 'media'});
    expect(resourceFormStoreFactory.createFromResourceStore).toHaveBeenLastCalledWith(
        resourceStoreMock.mock.instances[1],
        'permission_details',
        {resourceKey: 'media'}
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

    const formStore = resourceFormStoreFactoryMock.createFromResourceStore.mock.results[0].value;
    const resourceStore = resourceStoreMock.mock.instances[0];
    const inheritDialogFormStore = memoryFormStoreFactoryMock.createFromFormKey.mock.results[0].value;

    unmount();
    expect(formStore.destroy).toHaveBeenCalledWith();
    expect(resourceStore.destroy).toHaveBeenCalledWith();
    expect(inheritDialogFormStore.destroy).toHaveBeenCalledWith();
});

test('Confirming dialog should save the current value and inherit it', async() => {
    const confirmSpy = jest.fn();
    const user = userEvent.setup();
    let resolveSavePromise;
    const savePromise = new Promise<void>((resolve) => {
        resolveSavePromise = resolve;
    });
    resourceStoreMock.mockImplementationOnce(function() {
        this.destroy = jest.fn();
        this.save = jest.fn().mockReturnValue(savePromise);

        mockExtendObservable(this, {
            saving: false,
        });
    });

    render(
        <PermissionFormOverlay
            collectionId={undefined}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    await user.click(screen.getByRole('button', {name: 'overlay-confirm'}));

    memoryFormStoreFactoryMock.createFromFormKey.mock.results[0].value.data.inherit = true;
    await user.click(screen.getByRole('button', {name: 'dialog-confirm'}));

    expect(resourceStoreMock.mock.instances[0].save).toHaveBeenCalledWith({inherit: true, resourceKey: 'media'});
    expect(confirmSpy).not.toHaveBeenCalled();

    await act(async() => {
        resolveSavePromise();
        await savePromise;
    });

    expect(confirmSpy).toHaveBeenCalledWith();
});

test('Cancel inherit dialog should not save anything', async() => {
    const confirmSpy = jest.fn();
    const closeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <PermissionFormOverlay
            collectionId={undefined}
            hasChildren={true}
            onClose={closeSpy}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    await user.click(screen.getByRole('button', {name: 'overlay-confirm'}));
    await user.click(screen.getByRole('button', {name: 'dialog-cancel'}));

    expect(resourceStoreMock.mock.instances[0].save).not.toHaveBeenCalled();
    expect(confirmSpy).not.toHaveBeenCalled();
    expect(closeSpy).not.toHaveBeenCalled();
});

test.each([
    [true],
    [false],
])('Pass saving prop of value "%s" to confirmLoading prop of Overlay', (saving) => {
    const {rerender} = render(
        <PermissionFormOverlay
            collectionId={1}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    resourceStoreMock.mock.instances[0].saving = saving;
    rerender(
        <PermissionFormOverlay
            collectionId={1}
            hasChildren={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(getLatestOverlayProps().confirmLoading).toEqual(saving);
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

    expect(getLatestOverlayProps().open).toEqual(open);
});
