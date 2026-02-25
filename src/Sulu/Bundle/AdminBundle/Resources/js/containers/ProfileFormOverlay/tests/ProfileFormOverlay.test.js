// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import userStore from '../../../stores/userStore';
import ProfileFormOverlay from '../ProfileFormOverlay';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../../Form/stores/ResourceFormStore';

jest.mock('../../../containers/Form', () => {
    const React = require('react');

    return class FormMock extends React.Component<*> {
        submit() {
            this.props.onSubmit();
        }

        render() {
            return React.createElement('div', undefined, 'form container mock');
        }
    };
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

jest.mock(
    '../../Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceStore, formKey, options, metadataOptions) {
        this.id = resourceStore.id;
        this.formKey = formKey;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.data = {};

        this.save = jest.fn().mockReturnValue(Promise.resolve());
        this.destroy = jest.fn();

        mockExtendObservable(this, {
            dirty: false,
            saving: false,
        });
    })
);

const resourceFormStoreMock = ((ResourceFormStore: any): {
    mock: {instances: Array<Object>},
    ...
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('Component should render', () => {
    const {baseElement} = render(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Should pass correct props to FormOverlay', () => {
    const closeSpy = jest.fn();

    render(
        <ProfileFormOverlay
            onClose={closeSpy}
            open={true}
        />
    );

    expect(screen.getByText('sulu_admin.edit_profile')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.save'})).toBeDisabled();
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

    const initialFormStore = resourceFormStoreMock.mock.instances[0];
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
    expect(initialFormStore).not.toEqual(resourceFormStoreMock.mock.instances[1]);
});

test('Should destroy ResourceFormStore when component is unmounted', () => {
    const {unmount} = render(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    const formStore = resourceFormStoreMock.mock.instances[0];
    expect(formStore.destroy).not.toHaveBeenCalled();

    unmount();

    expect(formStore.destroy).toHaveBeenCalled();
});

test('Should update full name in UserStore and call onClose callback when FormOverlay is confirmed', async() => {
    const user = userEvent.setup();
    const closeSpy = jest.fn();

    render(
        <ProfileFormOverlay
            onClose={closeSpy}
            open={true}
        />
    );

    const formStore = resourceFormStoreMock.mock.instances[0];
    formStore.data = {
        firstName: 'Donald',
        lastName: 'Duck',
    };
    formStore.dirty = true;

    expect(userStore.setFullName).not.toHaveBeenCalled();
    expect(closeSpy).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.save'}));

    expect(userStore.setFullName).toHaveBeenCalledWith('Donald Duck');
    expect(closeSpy).toHaveBeenCalled();
});
