// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {extendObservable as mockExtendObservable} from 'mobx';
import userStore from '../../../stores/userStore';
import FormOverlay from '../../FormOverlay';
import ProfileFormOverlay from '../ProfileFormOverlay';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../../Form/stores/ResourceFormStore';

jest.mock('../../FormOverlay', () => jest.fn(() => null));

jest.mock('../../../stores/userStore', () => ({
    setFullName: jest.fn(),
}));
jest.mock('../../../stores/ResourceStore', () => jest.fn(
    (resourceKey, itemId) => {
        return {
            id: itemId,
        };
    }
));
jest.mock('../../Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceStore, formKey, options, metadataOptions) {
        this.id = resourceStore.id;
        this.formKey = formKey;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.data = {};

        this.save = jest.fn();
        this.destroy = jest.fn();

        mockExtendObservable(this, {
            dirty: false,
            saving: false,
        });
    })
);

function getLatestFormOverlayProps() {
    const calls = (FormOverlay: any).mock.calls;
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Component should render', () => {
    render(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    expect((FormOverlay: any).mock.calls).toHaveLength(1);
});

test('Should pass correct props to FormOverlay', () => {
    const closeSpy = jest.fn();

    render(
        <ProfileFormOverlay
            onClose={closeSpy}
            open={true}
        />
    );

    const formStore = (ResourceFormStore: any).mock.instances[0];
    const formOverlayProps = getLatestFormOverlayProps();

    expect(formOverlayProps.confirmText).toEqual('sulu_admin.save');
    expect(formOverlayProps.formStore).toBe(formStore);
    expect(formOverlayProps.onClose).toBe(closeSpy);
    expect(formOverlayProps.open).toEqual(true);
    expect(formOverlayProps.size).toEqual('large');
    expect(formOverlayProps.title).toEqual('sulu_admin.edit_profile');
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters when mounted', () => {
    render(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    expect(ResourceStore).toBeCalledWith('profile', '-');
    expect(ResourceFormStore).toBeCalledWith(expect.anything(), 'profile_details');
});

test('Should construct new ResourceStore and ResourceFormStore when closed and opened again', () => {
    const {rerender} = render(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    const initialFormStore = (ResourceFormStore: any).mock.instances[0];
    expect(initialFormStore.destroy).not.toHaveBeenCalled();

    rerender(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={false}
        />
    );
    rerender(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    expect(ResourceStore).toHaveBeenCalledTimes(2);
    expect(ResourceStore).lastCalledWith('profile', '-');
    expect(ResourceFormStore).toHaveBeenCalledTimes(2);
    expect(ResourceFormStore).lastCalledWith(expect.anything(), 'profile_details');

    expect(initialFormStore.destroy).toHaveBeenCalled();
    expect(initialFormStore).not.toEqual((ResourceFormStore: any).mock.instances[1]);
});

test('Should destroy ResourceFormStore when component is unmounted', () => {
    const {unmount} = render(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    const formStore = (ResourceFormStore: any).mock.instances[0];
    expect(formStore.destroy).not.toHaveBeenCalled();

    unmount();

    expect(formStore.destroy).toHaveBeenCalled();
});

test('Should update full name in UserStore and call onClose callback when FormOverlay is confirmed', () => {
    const closeSpy = jest.fn();

    render(
        <ProfileFormOverlay
            onClose={closeSpy}
            open={true}
        />
    );

    const formStore = (ResourceFormStore: any).mock.instances[0];
    formStore.data = {
        firstName: 'Donald',
        lastName: 'Duck',
    };

    expect(userStore.setFullName).not.toHaveBeenCalled();
    expect(closeSpy).not.toHaveBeenCalled();

    getLatestFormOverlayProps().onConfirm();

    expect(userStore.setFullName).toHaveBeenCalledWith('Donald Duck');
    expect(closeSpy).toHaveBeenCalled();
});
