// @flow
import {shallow, mount} from 'enzyme/build';
import mockReact from 'react';
import Overlay from '../../../components/Overlay';
import ResourceRequester from '../../../services/ResourceRequester';
import userStore from '../../../stores/UserStore';
import Form from '../../Form';
import ProfileFormOverlay from '../ProfileFormOverlay';
const React = mockReact;

jest.mock('sulu-admin-bundle/services/Initializer', () => jest.fn());

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/MemoryFormStore', () => class {
        data;
        rawSchema;
        jsonSchema;
        constructor(data, rawSchema, jsonSchema){
            this.data = data;
            this.rawSchema = rawSchema;
            this.jsonSchema = jsonSchema;
        }
});

jest.mock('sulu-admin-bundle/stores/UserStore', () => ({
    setFullName: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockImplementation(() => Promise.resolve({})),
    getJsonSchema: jest.fn().mockImplementation(()=> Promise.resolve({})),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester/ResourceRequester', () => ({
    get: jest.fn(),
    put: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers/Form', () => class Form extends mockReact.Component<*> {
    render() {
        return <div>form container mock</div>;
    }
});

test('Render as overlay and validate properties', () => {
    const profileFormOverlay = shallow(
        <ProfileFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );
    expect(profileFormOverlay.find('Overlay')).toHaveLength(1);
    expect(profileFormOverlay.find('Overlay').props()).toEqual(expect.objectContaining({
        title: 'sulu_admin.edit_profile',
        size: 'large',
        confirmText: 'sulu_admin.save',
    }));
});

test('Overlay should send put request on submit and update full name in UserStore', (done) => {
    const putPromise = Promise.resolve();
    ResourceRequester.get.mockReturnValue(Promise.resolve({
        firstName: 'Peter',
        lastName: 'Muster',
        username: 'peterm',
        email: 'peter@muster.io',
        locale: 'de',
    }));
    ResourceRequester.put.mockReturnValue(putPromise);

    const profileFormOverlay = mount(
        <ProfileFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );
    setTimeout(() => {
        profileFormOverlay.update();
        profileFormOverlay.instance().formStore.data.firstName = 'newFirstName';
        profileFormOverlay.instance().formStore.data.lastName = 'newLastName';
        profileFormOverlay.instance().formStore.data.email = 'newEmail';
        profileFormOverlay.instance().formStore.data.password = 'newPassword';
        profileFormOverlay.find(Form).props().onSubmit();
        expect(profileFormOverlay.instance().saving).toBeTruthy();
        expect(ResourceRequester.put).toBeCalledWith('profile', {
            firstName: 'newFirstName',
            lastName: 'newLastName',
            email: 'newEmail',
            username: 'peterm',
            locale: 'de',
            password: 'newPassword',
        });
        putPromise.then(() =>{
            expect(profileFormOverlay.instance().props.onClose).toBeCalled();
            expect(userStore.setFullName).toBeCalledWith('newFirstName newLastName');
            expect(profileFormOverlay.instance().saving).toBeFalsy();
            done();
        });
    });
});

test('Overlay should call close on close', () =>{
    const profileFormOverlay = shallow(
        <ProfileFormOverlay
            onClose={jest.fn()}
            open={true}
        />
    );
    profileFormOverlay.find(Overlay).props().onClose();
    expect(profileFormOverlay.instance().props.onClose).toBeCalled();
});
