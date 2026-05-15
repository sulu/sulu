// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceListStore from '../../../stores/ResourceListStore';
import ResourceSingleSelect from '../ResourceSingleSelect';

jest.mock('../../../stores/ResourceListStore', () => jest.fn());

function renderResourceSingleSelect(props: Object = {}) {
    return render(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
            {...props}
        />
    );
}

beforeEach(() => {
    jest.clearAllMocks();

    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [];
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
    });
});

test('Render in loading state', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementationOnce(function() {
        this.loading = true;
        this.data = undefined;
    });

    const {container} = renderResourceSingleSelect();

    expect(container).toMatchSnapshot();
    expect(ResourceListStore).toBeCalledWith('test', {limit: ''}, 'id');
});

test('Render in disabled state', () => {
    const {container} = renderResourceSingleSelect({disabled: true});

    expect(container).toMatchSnapshot();
});

test('Render with data', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    ResourceListStore.mockImplementationOnce(function() {
        this.loading = false;
        this.data = [
            {
                id: 1,
                name: 'Test 1',
            },
            {
                id: 2,
                name: 'Test 2',
            },
        ];
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
    });

    const {container} = renderResourceSingleSelect();

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(container).toMatchSnapshot();
    expect(document.body).toMatchSnapshot();
});

test('Render with data with editable option', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    ResourceListStore.mockImplementationOnce(function() {
        this.loading = false;
        this.data = [
            {
                id: 1,
                name: 'Test 1',
            },
            {
                id: 2,
                name: 'Test 2',
            },
        ];
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
    });

    const {container} = renderResourceSingleSelect({editable: true});

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(container).toMatchSnapshot();
    expect(document.body).toMatchSnapshot();
});

test('Render in value', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementationOnce(function() {
        this.loading = false;
        this.data = [
            {
                id: 1,
                name: 'Test 1',
            },
        ];
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
    });

    const {container} = renderResourceSingleSelect({
        disabled: true,
        value: 1,
    });

    expect(container).toMatchSnapshot();
});

test('Pass requestParameters to ResourceListStore', () => {
    const requestParameters = {
        flat: true,
    };

    renderResourceSingleSelect({
        disabled: true,
        requestParameters,
        value: 1,
    });

    expect(ResourceListStore).toBeCalledWith('test', {limit: '', flat: true}, 'id');
});

test('Trigger the change callback when the selection changes', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    ResourceListStore.mockImplementationOnce(function() {
        this.loading = false;
        this.data = [
            {
                id: 1,
                name: 'Test 1',
            },
            {
                id: 2,
                name: 'Test 2',
            },
        ];
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
    });

    const changeSpy = jest.fn();
    renderResourceSingleSelect({
        onChange: changeSpy,
        value: 1,
    });

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('Test 2'));

    expect(changeSpy).toHaveBeenCalledWith(2);
});

test('Trigger the change callback with undefined when the reset action is clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    renderResourceSingleSelect({
        onChange: changeSpy,
        value: 1,
    });

    await user.click(screen.getByLabelText('su-angle-down'));
    const resetAction = document.querySelector('ul.menu button.action');
    if (!resetAction) {
        throw new Error('Expected reset action button in menu');
    }

    await user.click(resetAction);

    expect(changeSpy).toHaveBeenCalledWith(undefined);
});

test('Updated data in EditOverlay should disappear when overlay is closed', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    ResourceListStore.mockImplementationOnce(function() {
        this.loading = false;
        this.data = [
            {id: 1, name: 'Test1'},
            {id: 2, name: 'Test2'},
        ];
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
    });

    renderResourceSingleSelect({
        editable: true,
        value: 1,
    });

    const resourceListStore = ((ResourceListStore: any).mock.instances[0]: any);

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('sulu_admin.edit'));

    const initialInputs = screen.getAllByRole('textbox');
    await user.clear(initialInputs[0]);
    await user.type(initialInputs[0], 'Test1 Update');

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[1]);
    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));

    const updatedInputs = screen.getAllByRole('textbox');
    await user.type(updatedInputs[1], 'Test3 Update');

    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(resourceListStore.deleteList).not.toBeCalled();
    expect(resourceListStore.patchList).not.toBeCalled();
});

test('Updated data in EditOverlay should be displayed in Select when overlay is confirmed', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    ResourceListStore.mockImplementationOnce(function() {
        this.loading = false;
        this.data = [
            {id: 1, name: 'Test1'},
            {id: 2, name: 'Test2'},
        ];
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
    });

    renderResourceSingleSelect({
        editable: true,
        value: 1,
    });

    const resourceListStore = ((ResourceListStore: any).mock.instances[0]: any);

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('sulu_admin.edit'));

    const initialInputs = screen.getAllByRole('textbox');
    await user.clear(initialInputs[0]);
    await user.type(initialInputs[0], 'Test1 Update');

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[1]);
    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));

    const updatedInputs = screen.getAllByRole('textbox');
    await user.type(updatedInputs[1], 'Test3 Update');

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.deleteList).toBeCalledWith([2]);
    expect(resourceListStore.patchList).toBeCalledWith([
        {name: 'Test3 Update'},
        {id: 1, name: 'Test1 Update'},
    ]);
});
