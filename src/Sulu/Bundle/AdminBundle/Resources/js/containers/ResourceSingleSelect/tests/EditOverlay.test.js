// @flow
import React from 'react';
import {autorun} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceListStore from '../../../stores/ResourceListStore';
import EditOverlay from '../EditOverlay';

const mockReact = require('react');

jest.mock('mobx', () => {
    const actualMobx = jest.requireActual('mobx');

    return {
        ...actualMobx,
        autorun: jest.fn(actualMobx.autorun),
    };
});

jest.mock('../../../stores/ResourceListStore', () => jest.fn(function() {
    this.data = [];
    this.patchList = jest.fn();
    this.deleteList = jest.fn();
    this.loading = false;
}));

jest.mock('../../../utils/Translator');

jest.mock('../../../components/Button', () => jest.fn((props) => (
    mockReact.createElement(
        'button',
        {
            'data-icon': props.icon,
            'data-skin': props.skin,
            onClick: props.onClick,
            type: 'button',
        },
        props.children || props.icon
    )
)));

jest.mock('../../../components/Overlay', () => jest.fn((props) => (
    props.open
        ? mockReact.createElement(
            'section',
            {'data-testid': 'overlay'},
            mockReact.createElement('h2', {}, props.title),
            mockReact.createElement('button', {
                'aria-label': 'close',
                onClick: props.onClose,
                type: 'button',
            }),
            props.children,
            mockReact.createElement(
                'button',
                {
                    'data-testid': 'confirm',
                    onClick: props.onConfirm,
                    type: 'button',
                },
                props.confirmText
            )
        )
        : null
)));

jest.mock('../EditLine', () => jest.fn((props) => (
    mockReact.createElement(
        'div',
        {'data-testid': 'edit-line'},
        mockReact.createElement('input', {
            'aria-label': 'edit-line-input',
            onChange: (event) => props.onChange(props.id, event.currentTarget.value || undefined),
            ref: props.inputRef,
            value: props.value || '',
        }),
        mockReact.createElement(
            'button',
            {
                'aria-label': 'remove-' + props.id,
                onClick: () => props.onRemove(props.id),
                type: 'button',
            },
            'Remove'
        )
    )
)));

beforeEach(() => {
    (autorun: any).mockImplementation(jest.requireActual('mobx').autorun);
});

function createResourceListStore(data) {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = data;

    return resourceListStore;
}

function getEditLineInputs() {
    return screen.getAllByLabelText('edit-line-input');
}

async function setInputValue(input, value) {
    await userEvent.clear(input);
    await userEvent.type(input, value);
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

    render(
        <EditOverlay
            displayProperty="title"
            idProperty="id"
            onClose={jest.fn()}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(screen.getByText('Add something')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Test 1')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Test 2')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.add')).toBeInTheDocument();
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

    render(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={jest.fn()}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(screen.getByText('Add something')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Test 1')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Test 2')).toBeInTheDocument();
});

test('Should only delete items from  ResourceStoreList if data is only deleted', async() => {
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

    await userEvent.click(screen.getByLabelText('remove-0'));

    await userEvent.click(screen.getByTestId('confirm'));

    expect(resourceListStore.patchList).not.toHaveBeenCalled();
    expect(resourceListStore.deleteList).toHaveBeenCalledWith([1]);
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

    await setInputValue(getEditLineInputs()[0], 'Test 1 Update');

    await userEvent.click(screen.getByTestId('confirm'));

    expect(resourceListStore.patchList).toHaveBeenCalledWith([
        {position: 'Test 1 Update', uuid: 1},
    ]);

    expect(resourceListStore.deleteList).not.toHaveBeenCalled();
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

    expect(screen.getAllByTestId('edit-line')).toHaveLength(2);
    await userEvent.click(screen.getByText('sulu_admin.add'));
    await userEvent.click(screen.getByText('sulu_admin.add'));
    expect(screen.getAllByTestId('edit-line')).toHaveLength(4);

    await setInputValue(getEditLineInputs()[1], 'Test 2 Update');
    await userEvent.type(getEditLineInputs()[2], 'Test 3');
    await userEvent.type(getEditLineInputs()[3], 'Test 4');

    await userEvent.click(screen.getByLabelText('remove-0'));

    await userEvent.click(screen.getByTestId('confirm'));

    expect(resourceListStore.patchList).toHaveBeenCalledWith([
        {position: 'Test 3'},
        {position: 'Test 4'},
        {position: 'Test 2 Update', uuid: 2},
    ]);

    expect(resourceListStore.deleteList).toHaveBeenCalledWith([1]);
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

    expect(screen.getAllByTestId('edit-line')).toHaveLength(2);
    await userEvent.click(screen.getByText('sulu_admin.add'));
    await userEvent.click(screen.getByText('sulu_admin.add'));
    expect(screen.getAllByTestId('edit-line')).toHaveLength(4);

    await userEvent.type(getEditLineInputs()[2], 'Test 3');

    await userEvent.click(screen.getByLabelText('remove-0'));

    await userEvent.click(screen.getByTestId('confirm'));

    expect(resourceListStore.patchList).toHaveBeenCalledWith([
        {position: 'Test 3'},
    ]);

    expect(resourceListStore.deleteList).toHaveBeenCalledWith([1]);
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

    await userEvent.click(screen.getByText('sulu_admin.add'));
    await userEvent.type(getEditLineInputs()[2], 'Test 2');
    await userEvent.click(screen.getByLabelText('remove-0'));
    await userEvent.click(screen.getByTestId('confirm'));

    expect(resourceListStore.patchList).not.toHaveBeenCalled();
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

    expect(screen.getAllByTestId('edit-line')).toHaveLength(2);
    await userEvent.click(screen.getByText('sulu_admin.add'));
    await userEvent.click(screen.getByText('sulu_admin.add'));
    expect(screen.getAllByTestId('edit-line')).toHaveLength(4);

    await userEvent.type(getEditLineInputs()[2], 'Test 3');
    await userEvent.type(getEditLineInputs()[3], 'Test 3');

    await userEvent.click(screen.getByLabelText('remove-0'));

    await userEvent.click(screen.getByTestId('confirm'));

    expect(resourceListStore.patchList).toHaveBeenCalledWith([
        {position: 'Test 3'},
    ]);

    expect(resourceListStore.deleteList).toHaveBeenCalledWith([1]);
});

test('Call disposer when component unmounts', () => {
    const resourceListStore = createResourceListStore([]);

    const updateDataDisposerSpy = jest.fn();
    (autorun: any).mockImplementation((callback) => {
        callback();

        return updateDataDisposerSpy;
    });

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

    expect(updateDataDisposerSpy).toHaveBeenCalledWith();
});
