// @flow
import mockReact from 'react';
import {render} from '@testing-library/react';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import SchemaFormStoreDecorator from 'sulu-admin-bundle/containers/Form/stores/SchemaFormStoreDecorator';
import {findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import RestoreFormOverlay from '../RestoreFormOverlay';

const React = mockReact;

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/containers/Form/stores/SchemaFormStoreDecorator',
    () => jest.fn(function(initializer) {
        return initializer({}, {});
    })
);

jest.mock('sulu-admin-bundle/containers/Form/stores/MemoryFormStore',
    () => jest.fn(function() {
        this.destroy = jest.fn();
        this.changeMultiple = jest.fn();
    })
);

jest.mock('sulu-admin-bundle/containers/Form/Form', () => class FormMock extends mockReact.Component<*> {
    render() {
        return <div>form container mock</div>;
    }
});

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({})),
}));

test('Component should render', () => {
    const {baseElement} = render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    expect(baseElement.innerHTML).toMatchSnapshot();
});

test('Component should not render without formKey', () => {
    const {container} = render(
        <RestoreFormOverlay
            formKey={null}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    expect(container.innerHTML).toMatchSnapshot();
});

test('Component should not render without trashItemId', () => {
    const {container} = render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId={null}
        />
    );

    expect(container.innerHTML).toMatchSnapshot();
});

test('Component should call close callback', () => {
    const onClose = jest.fn();

    const {instance: restoreFormOverlay} = renderWithRef(
        <RestoreFormOverlay
            formKey="test"
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    findElementByType(restoreFormOverlay.render(), 'FormOverlay').props.onClose();

    expect(onClose).toHaveBeenCalled();
});

test('Component should call confirm callback', () => {
    const onConfirm = jest.fn();

    const {instance: restoreFormOverlay} = renderWithRef(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    const data = {foo: 'bar'};
    restoreFormOverlay.formStore.data = data;

    findElementByType(restoreFormOverlay.render(), 'FormOverlay').props.onConfirm();

    expect(onConfirm).toHaveBeenCalledWith(data);
});

test('Component should create formStore, load restore data and set it to the formstore', () => {
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

    const {instance: restoreFormOverlay} = renderWithRef(
        <RestoreFormOverlay
            formKey="test-form-key"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    expect(SchemaFormStoreDecorator).toHaveBeenCalledWith(expect.anything(), 'test-form-key');
    expect(ResourceRequester.get).toHaveBeenCalledWith('trash_items', {'id': 'trash-item-123'});
    expect(restoreFormOverlay.formStore.changeMultiple).not.toHaveBeenCalled();
    expect(restoreFormOverlay.formStore.loading).toBeTruthy();

    return trashItemPromise.then(() => {
        expect(restoreFormOverlay.formStore.changeMultiple).toHaveBeenCalledWith(
            {key: 'test-key', parentId: 32}, {isServerValue: true}
        );
        expect(restoreFormOverlay.formStore.loading).toBeFalsy();
    });
});

test('Component should update formStore on changing form key', () => {
    const onConfirm = jest.fn();

    const {instance: restoreFormOverlay, rerender} = renderWithRef(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = restoreFormOverlay.formStore;
    expect(SchemaFormStoreDecorator).toHaveBeenCalledTimes(1);
    expect(SchemaFormStoreDecorator).toHaveBeenCalledWith(expect.anything(), 'test');
    expect(formStore.destroy).not.toHaveBeenCalled();

    rerender(
        <RestoreFormOverlay
            formKey="other"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    expect(SchemaFormStoreDecorator).toHaveBeenCalledTimes(2);
    expect(SchemaFormStoreDecorator).toHaveBeenCalledWith(expect.anything(), 'other');
    expect(formStore.destroy).toHaveBeenCalled();

    const newFormStore = restoreFormOverlay.formStore;
    expect(newFormStore).not.toBe(formStore);
    expect(newFormStore.destroy).not.toHaveBeenCalled();
});

test('Component should update formStore on reopen', () => {
    const onConfirm = jest.fn();

    const {instance: restoreFormOverlay, rerender} = renderWithRef(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = restoreFormOverlay.formStore;

    rerender(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={true}
            trashItemId="trash-item-123"
        />
    );
    expect(formStore.destroy).toHaveBeenCalled();

    const newFormStore = restoreFormOverlay.formStore;
    expect(newFormStore).not.toBe(formStore);
    expect(newFormStore.destroy).not.toHaveBeenCalled();
});

test('Component should destroy formStore on unmount', () => {
    const onConfirm = jest.fn();

    const {instance: restoreFormOverlay, unmount} = renderWithRef(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = restoreFormOverlay.formStore;

    unmount();

    expect(formStore.destroy).toHaveBeenCalled();
});
