// @flow
import mockReact from 'react';
import {render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import SchemaFormStoreDecorator from 'sulu-admin-bundle/containers/Form/stores/SchemaFormStoreDecorator';
import RestoreFormOverlay from '../RestoreFormOverlay';

const React = mockReact;
let mockFormStore;

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
        mockFormStore = this;
    })
);

jest.mock('sulu-admin-bundle/containers/Form/Form', () => class FormMock extends mockReact.Component<*> {
    submit() {
        this.props.onSubmit();
    }

    render() {
        return <div>form container mock</div>;
    }
});

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({})),
}));

beforeEach(() => {
    mockFormStore = undefined;
});

function getLatestFormStore() {
    if (!mockFormStore) {
        throw new Error('Expected a form store to be created');
    }

    return mockFormStore;
}

function getOverlayCloseButton() {
    const header = screen.getByText('sulu_trash.restore_element').closest('header');

    if (!header) {
        throw new Error('Expected restore overlay header to be rendered');
    }

    return within(header).getByRole('button', {name: 'su-times'});
}

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

    expect(container).toBeEmptyDOMElement();
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

    expect(container).toBeEmptyDOMElement();
});

test('Component should call close callback', async() => {
    const user = userEvent.setup();
    const onClose = jest.fn();

    render(
        <RestoreFormOverlay
            formKey="test"
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    await user.click(getOverlayCloseButton());

    expect(onClose).toHaveBeenCalled();
});

test('Component should call confirm callback', async() => {
    const user = userEvent.setup();
    const onConfirm = jest.fn();

    render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    const data = {foo: 'bar'};
    getLatestFormStore().data = data;

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(onConfirm).toHaveBeenCalledWith(data);
});

test('Component should create formStore, load restore data and set it to the formstore', async() => {
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

    render(
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
    expect(getLatestFormStore().changeMultiple).not.toHaveBeenCalled();
    expect(getLatestFormStore().loading).toBeTruthy();

    await trashItemPromise;
    await waitFor(() => {
        expect(getLatestFormStore().changeMultiple).toHaveBeenCalledWith(
            {key: 'test-key', parentId: 32}, {isServerValue: true}
        );
    });
    expect(getLatestFormStore().loading).toBeFalsy();
});

test('Component should update formStore on changing form key', () => {
    const onConfirm = jest.fn();

    const {rerender} = render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = getLatestFormStore();
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

    const newFormStore = getLatestFormStore();
    expect(newFormStore).not.toBe(formStore);
    expect(newFormStore.destroy).not.toHaveBeenCalled();
});

test('Component should update formStore on reopen', () => {
    const onConfirm = jest.fn();

    const {rerender} = render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = getLatestFormStore();

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

    const newFormStore = getLatestFormStore();
    expect(newFormStore).not.toBe(formStore);
    expect(newFormStore.destroy).not.toHaveBeenCalled();
});

test('Component should destroy formStore on unmount', () => {
    const onConfirm = jest.fn();

    const {unmount} = render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = getLatestFormStore();

    unmount();

    expect(formStore.destroy).toHaveBeenCalled();
});
