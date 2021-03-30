// @flow
import {shallow, mount} from 'enzyme/build';
import mockReact from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import userStore from '../../../stores/userStore';
import FormOverlay from '../../FormOverlay';
import ProfileFormOverlay from '../ProfileFormOverlay';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../../Form/stores/ResourceFormStore';

const React = mockReact;

jest.mock('../../../containers/Form', () => class FormMock extends mockReact.Component<*> {
    render() {
        return <div>form container mock</div>;
    }
});
jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

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

        this.save = jest.fn();
        this.destroy = jest.fn();

        mockExtendObservable(this, {
            dirty: false,
            saving: false,
        });
    })
);

test('Component should render', () => {
    const profileFormOverlay = mount(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    expect(profileFormOverlay.render()).toMatchSnapshot();
});

test('Should pass correct props to FormOverlay', () => {
    const closeSpy = jest.fn();

    const profileFormOverlay = shallow(
        <ProfileFormOverlay
            onClose={closeSpy}
            open={true}
        />
    );

    expect(profileFormOverlay.find(FormOverlay).props()).toEqual(expect.objectContaining({
        confirmText: 'sulu_admin.save',
        formStore: profileFormOverlay.instance().formStore,
        onClose: closeSpy,
        open: true,
        size: 'large',
        title: 'sulu_admin.edit_profile',
    }));
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters when mounted', () => {
    shallow(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    expect(ResourceStore).toBeCalledWith('profile', '-');
    expect(ResourceFormStore).toBeCalledWith(expect.anything(), 'profile_details');
});

test('Should construct new ResourceStore and ResourceFormStore when closed and opened again', () => {
    const profileFormOverlay = shallow(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    const initialFormStore = profileFormOverlay.instance().formStore;
    expect(initialFormStore.destroy).not.toHaveBeenCalled();

    profileFormOverlay.setProps({open: false});
    profileFormOverlay.setProps({open: true});

    expect(ResourceStore).toHaveBeenCalledTimes(2);
    expect(ResourceStore).lastCalledWith('profile', '-');
    expect(ResourceFormStore).toHaveBeenCalledTimes(2);
    expect(ResourceFormStore).lastCalledWith(expect.anything(), 'profile_details');

    expect(initialFormStore.destroy).toHaveBeenCalled();
    expect(initialFormStore).not.toEqual(profileFormOverlay.instance().formStore);
});

test('Should destroy ResourceFormStore when component is unmounted', () => {
    const profileFormOverlay = shallow(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    const formStore = profileFormOverlay.instance().formStore;
    expect(formStore.destroy).not.toHaveBeenCalled();

    profileFormOverlay.unmount();

    expect(formStore.destroy).toHaveBeenCalled();
});

test('Should update full name in UserStore and call onClose callback when FormOverlay is confirmed', () => {
    const closeSpy = jest.fn();

    const profileFormOverlay = shallow(
        <ProfileFormOverlay
            onClose={closeSpy}
            open={true}
        />
    );

    profileFormOverlay.instance().formStore.data = {
        firstName: 'Donald',
        lastName: 'Duck',
    };

    expect(userStore.setFullName).not.toHaveBeenCalled();
    expect(closeSpy).not.toHaveBeenCalled();

    profileFormOverlay.find(FormOverlay).props().onConfirm();

    expect(userStore.setFullName).toHaveBeenCalledWith('Donald Duck');
    expect(closeSpy).toHaveBeenCalled();
});
