// @flow
import * as mobx from 'mobx';
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceListStore from '../../../stores/ResourceListStore';
import EditOverlay from '../EditOverlay';

jest.mock('../../../stores/ResourceListStore', () => jest.fn(function() {
    this.data = [];
    this.patchList = jest.fn();
    this.deleteList = jest.fn();
}));

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

function createResourceListStore(data: Array<Object> = []) {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = data;

    return resourceListStore;
}

async function setLineValue(lineIndex: number, value: string, user: any) {
    const input = screen.getAllByRole('textbox')[lineIndex];

    await user.clear(input);

    if (value) {
        await user.type(input, value);
    }
}

test('Render data in EditLines', () => {
    const resourceListStore = createResourceListStore([
        {
            id: 1,
            title: 'Test 1',
        },
        {
            id: 2,
            title: 'Test 2',
        },
    ]);

    const {baseElement} = render(
        <EditOverlay
            displayProperty="title"
            idProperty="id"
            onClose={jest.fn()}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Render data in EditLines with other properties', () => {
    const resourceListStore = createResourceListStore([
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ]);

    const {baseElement} = render(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={jest.fn()}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Should only delete items from ResourceStoreList if data is only deleted', async() => {
    const resourceListStore = createResourceListStore([
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ]);

    const closeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).not.toHaveBeenCalled();
    expect(resourceListStore.deleteList).toHaveBeenCalledWith([1]);
    expect(closeSpy).toHaveBeenCalled();
});

test('Should only update ResourceStoreList if data is only changed and not deleted', async() => {
    const resourceListStore = createResourceListStore([
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ]);

    const closeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    await setLineValue(1, 'Test 2 Update', user);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).toHaveBeenCalledWith([
        {position: 'Test 2 Update', uuid: 2},
    ]);
    expect(resourceListStore.deleteList).not.toHaveBeenCalled();
    expect(closeSpy).toHaveBeenCalled();
});

test('Should update ResourceStoreList if data is changed and confirm button is clicked', async() => {
    const resourceListStore = createResourceListStore([
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ]);

    const closeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(screen.getAllByRole('textbox')).toHaveLength(2);

    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));

    expect(screen.getAllByRole('textbox')).toHaveLength(4);

    await setLineValue(1, 'Test 2 Update', user);
    await setLineValue(2, 'Test 3', user);
    await setLineValue(3, 'Test 4', user);

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).toHaveBeenCalledWith([
        {position: 'Test 3'},
        {position: 'Test 4'},
        {position: 'Test 2 Update', uuid: 2},
    ]);
    expect(resourceListStore.deleteList).toHaveBeenCalledWith([1]);
    expect(closeSpy).toHaveBeenCalled();
});

test('An empty field should not be added', async() => {
    const resourceListStore = createResourceListStore([
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ]);

    const closeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(screen.getAllByRole('textbox')).toHaveLength(2);

    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));

    expect(screen.getAllByRole('textbox')).toHaveLength(4);

    await setLineValue(2, 'Test 3', user);

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).toHaveBeenCalledWith([
        {position: 'Test 3'},
    ]);
    expect(resourceListStore.deleteList).toHaveBeenCalledWith([1]);
    expect(closeSpy).toHaveBeenCalled();
});

test('Adding the same field as already existing should not add it', async() => {
    const resourceListStore = createResourceListStore([
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ]);

    const closeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    await setLineValue(2, 'Test 2', user);
    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).not.toHaveBeenCalled();
    expect(resourceListStore.deleteList).toHaveBeenCalledWith([1]);
    expect(closeSpy).toHaveBeenCalled();
});

test('Adding the same field twice should add it only once', async() => {
    const resourceListStore = createResourceListStore([
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ]);

    const closeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(screen.getAllByRole('textbox')).toHaveLength(2);

    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));

    expect(screen.getAllByRole('textbox')).toHaveLength(4);

    await setLineValue(2, 'Test 3', user);
    await setLineValue(3, 'Test 3', user);

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).toHaveBeenCalledWith([
        {position: 'Test 3'},
    ]);
    expect(resourceListStore.deleteList).toHaveBeenCalledWith([1]);
    expect(closeSpy).toHaveBeenCalled();
});

test('Call disposer when component unmounts', () => {
    const disposerSpy = jest.fn();
    const autorunSpy = jest.spyOn(mobx, 'autorun').mockImplementation((callback) => {
        callback();

        return disposerSpy;
    });

    const resourceListStore = createResourceListStore([]);

    const {unmount} = render(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={jest.fn()}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    unmount();

    expect(disposerSpy).toHaveBeenCalledWith();

    autorunSpy.mockRestore();
});
