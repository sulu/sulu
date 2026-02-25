// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import MemoryFormStore from '../../stores/MemoryFormStore';
import ResourceFormStore from '../../stores/ResourceFormStore';
import CardCollection from '../../fields/CardCollection';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../components/Overlay', () => {
    const React = require('react');

    return jest.fn(function OverlayMock({children, onClose, onConfirm, open}) {
        if (!open) {
            return null;
        }

        return React.createElement(
            'div',
            {'data-testid': 'overlay'},
            React.createElement('button', {onClick: onClose, type: 'button'}, 'overlay-close'),
            React.createElement('button', {onClick: onConfirm, type: 'button'}, 'overlay-confirm'),
            children
        );
    });
});

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/MemoryFormStore', () => jest.fn(function(data, schema) {
    this.data = data;
    this.schema = schema;
    this.change = jest.fn().mockImplementation((name, value) => {
        this.data[name] = value;
    });
    this.validate = jest.fn().mockReturnValue(true);
    this.destroy = jest.fn();
    this.types = {};
}));
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn(function() {
    this.isFieldModified = jest.fn();
}));

jest.mock('../../registries/fieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'text_line':
                return require('../../../../components/Input').default;
        }
    }),
    getOptions: jest.fn(),
}));

function createFormInspector() {
    return new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
}

function createFieldTypeOptions() {
    return {
        renderCardContent: jest.fn((card) => card.firstName + ' ' + card.lastName),
        schema: {
            firstName: {
                name: 'firstName',
                type: 'text_line',
            },
            lastName: {
                name: 'lastName',
                type: 'text_line',
            },
        },
    };
}

function createValue() {
    return [
        {
            firstName: 'Max',
            lastName: 'Mustermann',
        },
        {
            firstName: 'Erika',
            lastName: 'Mustermann',
        },
    ];
}

afterEach(() => {
    jest.clearAllMocks();
});

function getLatestFormStore() {
    // $FlowFixMe - jest mock instances are dynamically typed
    return MemoryFormStore.mock.instances[MemoryFormStore.mock.instances.length - 1];
}

test('Render a CardCollection', () => {
    const {asFragment} = render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={createFieldTypeOptions()}
            formInspector={createFormInspector()}
            value={createValue()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Close the overlay when its close button is clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={createFieldTypeOptions()}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            value={createValue()}
        />
    );

    expect(screen.queryByRole('button', {name: 'overlay-confirm'})).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));
    expect(screen.getByRole('button', {name: 'overlay-confirm'})).toBeInTheDocument();
    const formStore = getLatestFormStore();

    await user.click(screen.getByRole('button', {name: 'overlay-close'}));
    expect(screen.queryByRole('button', {name: 'overlay-confirm'})).not.toBeInTheDocument();
    expect(formStore.destroy).toBeCalled();

    expect(changeSpy).not.toBeCalled();
});

test('Add a new card using the overlay', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const value = createValue();

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={createFieldTypeOptions()}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            value={value}
        />
    );

    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));
    const formStore = getLatestFormStore();
    formStore.change('firstName', 'John');
    formStore.change('lastName', 'Doe');
    formStore.dirty = true;
    await user.click(screen.getByRole('button', {name: 'overlay-confirm'}));

    expect(changeSpy).toBeCalledWith([...value, {firstName: 'John', lastName: 'Doe'}]);
    expect(formStore.destroy).toBeCalled();
});

test('Do not add a new card if validation fails', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const fieldTypeOptions = {
        ...createFieldTypeOptions(),
        jsonSchema: {
            required: ['firstName', 'lastName'],
        },
    };

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            value={[]}
        />
    );

    await user.click(screen.getByRole('button', {name: /sulu_admin.add/}));
    const formStore = getLatestFormStore();
    formStore.validate.mockReturnValue(false);
    formStore.change('firstName', 'John');
    formStore.dirty = true;
    await user.click(screen.getByRole('button', {name: 'overlay-confirm'}));

    expect(screen.getByRole('button', {name: 'overlay-confirm'})).toBeInTheDocument();
    expect(changeSpy).not.toBeCalled();
    expect(formStore.destroy).not.toBeCalled();
});

test('Edit an existing card using the overlay', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const value = createValue();

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={createFieldTypeOptions()}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            value={value}
        />
    );

    await user.click(screen.getAllByLabelText('su-pen')[0]);

    const formStore = getLatestFormStore();
    formStore.change('firstName', 'John');
    formStore.change('lastName', 'Doe');
    formStore.dirty = true;
    await user.click(screen.getByRole('button', {name: 'overlay-confirm'}));

    expect(changeSpy).toBeCalledWith([{firstName: 'John', lastName: 'Doe'}, value[1]]);
    expect(formStore.destroy).toBeCalled();
});

test('Remove an existing card', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const value = createValue();

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={createFieldTypeOptions()}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            value={value}
        />
    );

    await user.click(screen.getAllByLabelText('su-trash-alt')[1]);

    expect(changeSpy).toBeCalledWith([value[0]]);
});

test('Throw error when no renderCardContent function is passed', () => {
    expect(() => render(<CardCollection {...fieldTypeDefaultProps} formInspector={createFormInspector()} />)).toThrow(
        /"renderCardContent"/
    );
});

test('Throw error when no schema function is passed', () => {
    const fieldTypeOptions = {
        renderCardContent: jest.fn(),
    };

    expect(
        () => render(
            <CardCollection
                {...fieldTypeDefaultProps}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={createFormInspector()}
            />
        )
    ).toThrow(/"schema"/);
});
