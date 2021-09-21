// @flow
import mockReact from 'react';
import {shallow, mount} from 'enzyme';
import {memoryFormStoreFactory} from 'sulu-admin-bundle/containers/Form';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import RestoreFormOverlay from '../RestoreFormOverlay';

const React = mockReact;

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn((formKey) => ({
        formKey,
        data: {},
        destroy: jest.fn(),
    })),
}));

jest.mock('sulu-admin-bundle/containers/Form/Form', () => class FormMock extends mockReact.Component<*> {
    render() {
        return <div>form container mock</div>;
    }
});

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({})),
}));

test('Component should render', () => {
    const restoreFormOverlay = mount(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    expect(restoreFormOverlay.render()).toMatchSnapshot();
});

test('Component should not render without formKey', () => {
    const restoreFormOverlay = mount(
        <RestoreFormOverlay
            formKey={null}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    expect(restoreFormOverlay.render()).toMatchSnapshot();
});

test('Component should not render without trashItemId', () => {
    const restoreFormOverlay = mount(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId={null}
        />
    );

    expect(restoreFormOverlay.render()).toMatchSnapshot();
});

test('Component should call close callback', () => {
    const onClose = jest.fn();

    const restoreFormOverlay = shallow(
        <RestoreFormOverlay
            formKey="test"
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    restoreFormOverlay.find('FormOverlay').prop('onClose')();

    expect(onClose).toHaveBeenCalled();
});

test('Component should call confirm callback', () => {
    const onConfirm = jest.fn();

    const restoreFormOverlay = shallow(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    const data = {foo: 'bar'};
    restoreFormOverlay.instance().formStore.data = data;

    restoreFormOverlay.find('FormOverlay').prop('onConfirm')();

    expect(onConfirm).toHaveBeenCalledWith(data);
});

test('Component should create formStore, load restore data and set it to the formstore', () => {
    const mockedStore = {
        changeMultiple: jest.fn(),
        loading: undefined,
    };
    memoryFormStoreFactory.createFromFormKey.mockReturnValueOnce(mockedStore);

    const trashItemPromise = Promise.resolve({
        id: 5,
        resourceKey: 'categories',
        resourceId: '33',
        restoreData: {
            key: 'test-key',
            parentId: 32,
        },
    });
    ResourceRequester.get.mockReturnValue(trashItemPromise);

    shallow(
        <RestoreFormOverlay
            formKey="test-form-key"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    expect(memoryFormStoreFactory.createFromFormKey).toBeCalledWith('test-form-key');
    expect(ResourceRequester.get).toBeCalledWith('trash_items', {'id': 'trash-item-123'});
    expect(mockedStore.changeMultiple).not.toBeCalled();
    expect(mockedStore.loading).toBeTruthy();

    return trashItemPromise.then(() => {
        expect(mockedStore.changeMultiple).toBeCalledWith({key: 'test-key', parentId: 32}, {isServerValue: true});
        expect(mockedStore.loading).toBeFalsy();
    });
});

test('Component should update formStore on changing form key', () => {
    const onConfirm = jest.fn();

    const restoreFormOverlay = shallow(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = restoreFormOverlay.instance().formStore;
    expect(formStore.formKey).toBe('test');

    restoreFormOverlay.setProps({formKey: 'other'});
    expect(formStore.destroy).toHaveBeenCalled();

    const newFormStore = restoreFormOverlay.instance().formStore;
    expect(newFormStore).not.toBe(formStore);
    expect(newFormStore.formKey).toBe('other');
    expect(newFormStore.destroy).not.toHaveBeenCalled();
});

test('Component should update formStore on reopen', () => {
    const onConfirm = jest.fn();

    const restoreFormOverlay = shallow(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = restoreFormOverlay.instance().formStore;

    restoreFormOverlay.setProps({open: true});
    expect(formStore.destroy).toHaveBeenCalled();

    const newFormStore = restoreFormOverlay.instance().formStore;
    expect(newFormStore).not.toBe(formStore);
    expect(newFormStore.destroy).not.toHaveBeenCalled();
});

test('Component should destroy formStore on unmount', () => {
    const onConfirm = jest.fn();

    const restoreFormOverlay = shallow(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = restoreFormOverlay.instance().formStore;

    restoreFormOverlay.unmount();

    expect(formStore.destroy).toHaveBeenCalled();
});
