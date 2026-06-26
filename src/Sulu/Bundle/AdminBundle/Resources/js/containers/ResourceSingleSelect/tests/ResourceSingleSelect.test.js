// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceListStore from '../../../stores/ResourceListStore';
import ResourceSingleSelect from '../ResourceSingleSelect';

let mockSingleSelectProps: Object = {};
let mockResourceListStoreInstances: Array<Object> = [];

const mockReact = require('react');

jest.mock('../../../stores/ResourceListStore', () => jest.fn());

jest.mock('../../../utils/Translator');

jest.mock('../../../components/Loader', () => jest.fn((props) => (
    mockReact.createElement('div', {'data-size': props.size, 'data-testid': 'loader'})
)));

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
            {'data-testid': 'edit-overlay'},
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

jest.mock('../../../components/SingleSelect', () => {
    const SingleSelectMock: any = jest.fn((props) => {
        mockSingleSelectProps = props;

        return mockReact.createElement(
            'div',
            {
                'data-disabled': props.disabled ? 'true' : 'false',
                'data-testid': 'single-select',
                'data-value': props.value === undefined ? '' : props.value,
            },
            props.children
        );
    });

    SingleSelectMock.Action = (props) => (
        mockReact.createElement(
            'button',
            {
                onClick: props.onClick,
                type: 'button',
            },
            props.children
        )
    );

    SingleSelectMock.Option = (props) => (
        mockReact.createElement(
            'button',
            {
                onClick: () => mockSingleSelectProps.onChange(props.value),
                type: 'button',
            },
            props.children
        )
    );

    SingleSelectMock.Divider = () => mockReact.createElement('hr', {'data-testid': 'divider'});

    return SingleSelectMock;
});

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
    mockSingleSelectProps = {};
    mockResourceListStoreInstances = [];

    (ResourceListStore: any).mockImplementation(function(resourceKey, parameters, idProperty) {
        this.resourceKey = resourceKey;
        this.parameters = parameters;
        this.idProperty = idProperty;
        this.loading = false;
        this.data = [];
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
        mockResourceListStoreInstances.push(this);
    });
});

function mockResourceListStore(data, loading = false) {
    (ResourceListStore: any).mockImplementation(function(resourceKey, parameters, idProperty) {
        this.resourceKey = resourceKey;
        this.parameters = parameters;
        this.idProperty = idProperty;
        this.loading = loading;
        this.data = data;
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
        mockResourceListStoreInstances.push(this);
    });
}

function getEditLineInputs() {
    return screen.getAllByLabelText('edit-line-input');
}

async function setInputValue(input, value) {
    await userEvent.clear(input);
    await userEvent.type(input, value);
}

test('Render in loading state', () => {
    mockResourceListStore(undefined, true);

    render(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    );

    expect(screen.getByTestId('loader')).toHaveAttribute('data-size', '30');
    expect(ResourceListStore).toHaveBeenCalledWith('test', {limit: ''}, 'id');
});

test('Render in disabled state', () => {
    render(
        <ResourceSingleSelect
            disabled={true}
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    );

    expect(screen.getByTestId('single-select')).toHaveAttribute('data-disabled', 'true');
});

test('Render with data', () => {
    mockResourceListStore([
        {
            id: 1,
            name: 'Test 1',
        },
        {
            id: 2,
            name: 'Test 2',
        },
    ]);

    render(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    );

    expect(screen.getByText('sulu_admin.please_choose')).toBeInTheDocument();
    expect(screen.getByText('Test 1')).toBeInTheDocument();
    expect(screen.getByText('Test 2')).toBeInTheDocument();
});

test('Render with data with editable option', () => {
    mockResourceListStore([
        {
            id: 1,
            name: 'Test 1',
        },
        {
            id: 2,
            name: 'Test 2',
        },
    ]);

    render(
        <ResourceSingleSelect
            displayProperty="name"
            editable={true}
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    );

    expect(screen.getByText('Test 1')).toBeInTheDocument();
    expect(screen.getByText('Test 2')).toBeInTheDocument();
    expect(screen.getByTestId('divider')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.edit')).toBeInTheDocument();
});

test('Render in value', () => {
    mockResourceListStore([
        {
            id: 1,
            name: 'Test 1',
        },
    ]);

    render(
        <ResourceSingleSelect
            disabled={true}
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={1}
        />
    );

    expect(mockSingleSelectProps.value).toEqual(1);
    expect(screen.getByText('Test 1')).toBeInTheDocument();
});

test('Pass requestParameters to ResourceListStore', () => {
    mockResourceListStore([
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
    mockResourceListStore([
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

    render(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={changeSpy}
            resourceKey="test"
            value={1}
        />
    );

    await userEvent.click(screen.getByText('Test 2'));

    expect(changeSpy).toHaveBeenCalledWith(2);
});

test('Trigger the change callback with undefined when the reset action is clicked', async() => {
    const changeSpy = jest.fn();

    render(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={changeSpy}
            resourceKey="test"
            value={1}
        />
    );

    await userEvent.click(screen.getByText('sulu_admin.please_choose'));

    expect(changeSpy).toHaveBeenCalledWith(undefined);
});

test('Updated data in EditOverlay should disappear when overlay is closed', async() => {
    mockResourceListStore([
        {id: 1, name: 'Test1'},
        {id: 2, name: 'Test2'},
    ]);

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

    await userEvent.click(screen.getByText('sulu_admin.edit'));
    await setInputValue(getEditLineInputs()[0], 'Test1 Update');
    await userEvent.click(screen.getByLabelText('remove-1'));
    await userEvent.click(screen.getByText('sulu_admin.add'));
    await userEvent.type(getEditLineInputs()[1], 'Test3 Update');
    await userEvent.click(screen.getByLabelText('close'));

    expect(mockResourceListStoreInstances[0].deleteList).not.toHaveBeenCalled();
    expect(mockResourceListStoreInstances[0].patchList).not.toHaveBeenCalled();
});

test('Updated data in EditOverlay should be displayed in Select when overlay is confirmed', async() => {
    mockResourceListStore([
        {id: 1, name: 'Test1'},
        {id: 2, name: 'Test2'},
    ]);

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

    await userEvent.click(screen.getByText('sulu_admin.edit'));
    await setInputValue(getEditLineInputs()[0], 'Test1 Update');
    await userEvent.click(screen.getByLabelText('remove-1'));
    await userEvent.click(screen.getByText('sulu_admin.add'));
    await userEvent.type(getEditLineInputs()[1], 'Test3 Update');
    await userEvent.click(screen.getByTestId('confirm'));

    expect(mockResourceListStoreInstances[0].deleteList).toHaveBeenCalledWith([2]);
    expect(mockResourceListStoreInstances[0].patchList).toHaveBeenCalledWith([
        {name: 'Test3 Update'},
        {id: 1, 'name': 'Test1 Update'},
    ]);
});
