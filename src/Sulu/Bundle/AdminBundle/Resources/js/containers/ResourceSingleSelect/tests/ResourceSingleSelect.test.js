// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceListStore from '../../../stores/ResourceListStore';
import ResourceSingleSelect from '../ResourceSingleSelect';

jest.mock('../../../stores/ResourceListStore', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

function mockResourceListStoreData(data: ?Array<Object>, loading: boolean = false) {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = loading;
        this.data = data === undefined ? [] : data;
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
    });
}

function getLastResourceListStoreInstance() {
    // $FlowFixMe
    return ResourceListStore.mock.instances[ResourceListStore.mock.instances.length - 1];
}

async function setLineValue(lineIndex: number, value: string, user: any) {
    const input = screen.getAllByRole('textbox')[lineIndex];

    await user.clear(input);

    if (value) {
        await user.type(input, value);
    }
}

test('Render in loading state', () => {
    mockResourceListStoreData(undefined, true);

    const {asFragment} = render(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();
    expect(ResourceListStore).toHaveBeenCalledWith('test', {limit: ''}, 'id');
});

test('Render in disabled state', () => {
    mockResourceListStoreData([]);

    const {asFragment} = render(
        <ResourceSingleSelect
            disabled={true}
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render with data', async() => {
    mockResourceListStoreData([
        {
            id: 1,
            name: 'Test 1',
        },
        {
            id: 2,
            name: 'Test 2',
        },
    ]);

    const user = userEvent.setup();

    const {asFragment} = render(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();

    await user.click(screen.getAllByRole('button', {name: /sulu_admin\.please_choose/})[0]);

    expect(screen.getByRole('button', {name: 'Test 1'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Test 2'})).toBeInTheDocument();
    expect(document.body).toMatchSnapshot();
});

test('Render with data with editable option', async() => {
    mockResourceListStoreData([
        {
            id: 1,
            name: 'Test 1',
        },
        {
            id: 2,
            name: 'Test 2',
        },
    ]);

    const user = userEvent.setup();

    const {asFragment} = render(
        <ResourceSingleSelect
            displayProperty="name"
            editable={true}
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();

    await user.click(screen.getAllByRole('button', {name: /sulu_admin\.please_choose/})[0]);

    expect(screen.getByRole('button', {name: 'sulu_admin.edit'})).toBeInTheDocument();
    expect(document.body).toMatchSnapshot();
});

test('Render in value', () => {
    mockResourceListStoreData([
        {
            id: 1,
            name: 'Test 1',
        },
    ]);

    const {asFragment} = render(
        <ResourceSingleSelect
            disabled={true}
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={1}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Pass requestParameters to ResourceListStore', () => {
    mockResourceListStoreData([
        {
            id: 1,
            name: 'Test 1',
        },
    ]);

    const requestParameters = {
        flat: true,
    };

    render(
        <ResourceSingleSelect
            disabled={true}
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            requestParameters={requestParameters}
            resourceKey="test"
            value={1}
        />
    );

    expect(ResourceListStore).toHaveBeenCalledWith('test', {limit: '', flat: true}, 'id');
});

test('Trigger the change callback when the selection changes', async() => {
    mockResourceListStoreData([
        {
            id: 1,
            name: 'Test 1',
        },
        {
            id: 2,
            name: 'Test 2',
        },
    ]);

    const changeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={changeSpy}
            resourceKey="test"
            value={1}
        />
    );

    await user.click(screen.getByRole('button', {name: /Test 1/}));
    await user.click(screen.getByRole('button', {name: /Test 2/}));

    expect(changeSpy).toHaveBeenCalledWith(2);
});

test('Trigger the change callback with undefined when the reset action is clicked', async() => {
    mockResourceListStoreData([
        {
            id: 1,
            name: 'Test 1',
        },
        {
            id: 2,
            name: 'Test 2',
        },
    ]);

    const changeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={changeSpy}
            resourceKey="test"
            value={1}
        />
    );

    await user.click(screen.getByRole('button', {name: /Test 1/}));
    await user.click(screen.getByRole('button', {name: /sulu_admin\.please_choose/}));

    expect(changeSpy).toHaveBeenCalledWith(undefined);
});

test('Updated data in EditOverlay should disappear when overlay is closed', async() => {
    mockResourceListStoreData([
        {id: 1, name: 'Test1'},
        {id: 2, name: 'Test2'},
    ]);

    const user = userEvent.setup();

    render(
        <ResourceSingleSelect
            displayProperty="name"
            editable={true}
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={1}
        />
    );

    const resourceListStore = getLastResourceListStoreInstance();

    await user.click(screen.getByRole('button', {name: /Test1/}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.edit'}));

    await setLineValue(0, 'Test1 Update', user);
    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[1]);
    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    await setLineValue(1, 'Test3 Update', user);
    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(resourceListStore.deleteList).not.toHaveBeenCalled();
    expect(resourceListStore.patchList).not.toHaveBeenCalled();
});

test('Updated data in EditOverlay should be displayed in Select when overlay is confirmed', async() => {
    mockResourceListStoreData([
        {id: 1, name: 'Test1'},
        {id: 2, name: 'Test2'},
    ]);

    const user = userEvent.setup();

    render(
        <ResourceSingleSelect
            displayProperty="name"
            editable={true}
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={1}
        />
    );

    const resourceListStore = getLastResourceListStoreInstance();

    await user.click(screen.getByRole('button', {name: /Test1/}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.edit'}));

    await setLineValue(0, 'Test1 Update', user);
    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[1]);
    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    await setLineValue(1, 'Test3 Update', user);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.deleteList).toHaveBeenCalledWith([2]);
    expect(resourceListStore.patchList).toHaveBeenCalledWith([
        {name: 'Test3 Update'},
        {id: 1, name: 'Test1 Update'},
    ]);
});
