// @flow
import mockReact from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import userStore from '../../../stores/userStore';
import ProfileFormOverlay from '../ProfileFormOverlay';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../../Form/stores/ResourceFormStore';
import {createTestRef} from '../../../utils/TestHelper';

const React = mockReact;
const mockForm = jest.fn();

jest.mock('../../../containers/Form', () => class FormMock extends mockReact.Component<*> {
    submit() {
        this.props.onSubmit();
    }

    render() {
        mockForm(this.props);
        return <div>form container mock</div>;
    }
});
jest.mock('../../../utils/Translator');

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

        this.save = jest.fn(() => Promise.resolve());
        this.destroy = jest.fn();

        mockExtendObservable(this, {
            data: {},
            dirty: false,
            saving: false,
        });
    })
);

test('Component should render', () => {
    render(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    expect(screen.getByRole('heading', {name: 'sulu_admin.edit_profile'})).toBeInTheDocument();
    expect(screen.getByText('form container mock')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.save'})).toBeDisabled();
});

test('Should pass correct props to FormOverlay', async() => {
    const closeSpy = jest.fn();
    const user = userEvent.setup();
    const ref = createTestRef();

    render(
        <ProfileFormOverlay
            onClose={closeSpy}
            open={true}
            ref={ref}
        />
    );

    expect(screen.getByRole('heading', {name: 'sulu_admin.edit_profile'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.save'})).toBeDisabled();
    expect(mockForm).toHaveBeenLastCalledWith(expect.objectContaining({
        store: ref.current.formStore,
    }));

    await user.click(screen.getAllByRole('button', {name: 'su-times'})[0]);

    expect(closeSpy).toHaveBeenCalled();
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters when mounted', () => {
    render(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );

    expect(ResourceStore).toHaveBeenCalledWith('profile', '-');
    expect(ResourceFormStore).toHaveBeenCalledWith(expect.anything(), 'profile_details');
});

test('Should construct new ResourceStore and ResourceFormStore when closed and opened again', () => {
    const ref = createTestRef();
    const {rerender} = render(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
            ref={ref}
        />
    );

    const initialFormStore = ref.current.formStore;
    expect(initialFormStore.destroy).not.toHaveBeenCalled();

    rerender(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={false}
            ref={ref}
        />
    );
    rerender(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
            ref={ref}
        />
    );

    expect(ResourceStore).toHaveBeenCalledTimes(2);
    expect(ResourceStore).toHaveBeenLastCalledWith('profile', '-');
    expect(ResourceFormStore).toHaveBeenCalledTimes(2);
    expect(ResourceFormStore).toHaveBeenLastCalledWith(expect.anything(), 'profile_details');

    expect(initialFormStore.destroy).toHaveBeenCalled();
    expect(initialFormStore).not.toEqual(ref.current.formStore);
});

test('Should destroy ResourceFormStore when component is unmounted', () => {
    const ref = createTestRef();
    const {unmount} = render(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
            ref={ref}
        />
    );

    const formStore = ref.current.formStore;
    expect(formStore.destroy).not.toHaveBeenCalled();

    unmount();

    expect(formStore.destroy).toHaveBeenCalled();
});

test('Should update full name in UserStore and call onClose callback when FormOverlay is confirmed', async() => {
    const closeSpy = jest.fn();
    const user = userEvent.setup();
    const ref = createTestRef();

    render(
        <ProfileFormOverlay
            onClose={closeSpy}
            open={true}
            ref={ref}
        />
    );

    act(() => {
        ref.current.formStore.data = {
            firstName: 'Donald',
            lastName: 'Duck',
        };
        ref.current.formStore.dirty = true;
    });

    expect(userStore.setFullName).not.toHaveBeenCalled();
    expect(closeSpy).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.save'}));

    await waitFor(() => expect(userStore.setFullName).toHaveBeenCalledWith('Donald Duck'));

    expect(closeSpy).toHaveBeenCalled();
});
