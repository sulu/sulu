// @flow
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

function getElement(selector: string) {
    const body = document.body;
    if (!body) {
        throw new Error('Expected document.body to exist');
    }

    const element = body.querySelector(selector);
    if (!element) {
        throw new Error('Expected element "' + selector + '"');
    }

    return element;
}

function renderEditOverlay(props: Object = {}) {
    return render(
        <EditOverlay
            displayProperty="title"
            idProperty="id"
            onClose={jest.fn()}
            open={true}
            resourceListStore={new ResourceListStore('accounts')}
            title="Add something"
            {...props}
        />
    );
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render data in EditLines', () => {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            id: 1,
            title: 'Test 1',
        },
        {
            id: 2,
            title: 'Test 2',
        },
    ];

    renderEditOverlay({
        resourceListStore,
    });

    expect(getElement('header')).toMatchSnapshot();
    expect(getElement('article .overlay')).toMatchSnapshot();
});

test('Render data in EditLines with other properties', () => {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    renderEditOverlay({
        displayProperty: 'position',
        idProperty: 'uuid',
        resourceListStore,
    });

    expect(getElement('header')).toMatchSnapshot();
    expect(getElement('article .overlay')).toMatchSnapshot();
});

test('Should only delete items from  ResourceStoreList if data is only deleted', async() => {
    const user = userEvent.setup();
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    renderEditOverlay({
        displayProperty: 'position',
        idProperty: 'uuid',
        resourceListStore,
    });

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).not.toBeCalled();
    expect(resourceListStore.deleteList).toBeCalledWith([1]);
});

test('Should only update ResourceStoreList if data is only changed and not deleted', async() => {
    const user = userEvent.setup();
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    renderEditOverlay({
        displayProperty: 'position',
        idProperty: 'uuid',
        resourceListStore,
    });

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).not.toBeCalled();
    expect(resourceListStore.deleteList).toBeCalledWith([1]);
});

test('Should update ResourceStoreList if data is changed and confirm button is clicked', async() => {
    const user = userEvent.setup();
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    renderEditOverlay({
        displayProperty: 'position',
        idProperty: 'uuid',
        resourceListStore,
    });

    expect(screen.getAllByRole('textbox')).toHaveLength(2);
    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));
    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));
    expect(screen.getAllByRole('textbox')).toHaveLength(4);

    const inputs = screen.getAllByRole('textbox');
    await user.clear(inputs[1]);
    await user.type(inputs[1], 'Test 2 Update');
    await user.type(inputs[2], 'Test 3');
    await user.type(inputs[3], 'Test 4');

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).toBeCalledWith([
        {position: 'Test 3'},
        {position: 'Test 4'},
        {position: 'Test 2 Update', uuid: 2},
    ]);

    expect(resourceListStore.deleteList).toBeCalledWith([1]);
});

test('An empty field should not be added', async() => {
    const user = userEvent.setup();
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    renderEditOverlay({
        displayProperty: 'position',
        idProperty: 'uuid',
        resourceListStore,
    });

    expect(screen.getAllByRole('textbox')).toHaveLength(2);
    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));
    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));
    expect(screen.getAllByRole('textbox')).toHaveLength(4);

    await user.type(screen.getAllByRole('textbox')[2], 'Test 3');

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).toBeCalledWith([
        {position: 'Test 3'},
    ]);

    expect(resourceListStore.deleteList).toBeCalledWith([1]);
});

test('Adding the same field as already existing should not add it', async() => {
    const user = userEvent.setup();
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    renderEditOverlay({
        displayProperty: 'position',
        idProperty: 'uuid',
        resourceListStore,
    });

    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));
    await user.type(screen.getAllByRole('textbox')[2], 'Test 2');
    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).not.toBeCalledWith();
});

test('Adding the same field twice should add it only once', async() => {
    const user = userEvent.setup();
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    renderEditOverlay({
        displayProperty: 'position',
        idProperty: 'uuid',
        resourceListStore,
    });

    expect(screen.getAllByRole('textbox')).toHaveLength(2);
    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));
    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));
    expect(screen.getAllByRole('textbox')).toHaveLength(4);

    await user.type(screen.getAllByRole('textbox')[2], 'Test 3');
    await user.type(screen.getAllByRole('textbox')[3], 'Test 3');

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[0]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.patchList).toBeCalledWith([
        {position: 'Test 3'},
    ]);

    expect(resourceListStore.deleteList).toBeCalledWith([1]);
});

test('Call disposer when component unmounts', () => {
    const resourceListStore = new ResourceListStore('accounts');
    const ref = React.createRef();

    const {unmount} = renderEditOverlay({
        // $FlowFixMe
        ref,
        displayProperty: 'position',
        idProperty: 'uuid',
        resourceListStore,
    });

    const updateDataDisposerSpy = jest.fn();
    if (ref.current) {
        // $FlowFixMe
        ref.current.updateDataDisposer = updateDataDisposerSpy;
    }

    unmount();

    expect(updateDataDisposerSpy).toBeCalledWith();
});
