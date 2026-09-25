// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import CardCollection from '../../fields/CardCollection';

let mockFormProps: Object = {};

const mockReact = require('react');

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn(function() {
    this.isFieldModified = jest.fn();
}));

jest.mock('../../../../components/CardCollection', () => {
    const CardCollectionMock: any = jest.fn((props) => {
        const children = mockReact.Children.toArray(props.children);

        return mockReact.createElement(
            'div',
            null,
            mockReact.createElement('button', {onClick: props.onAdd, type: 'button'}, 'add'),
            children.map((child, index) => mockReact.createElement(
                'div',
                {key: index},
                child,
                mockReact.createElement('button', {onClick: () => props.onEdit(index), type: 'button'}, 'edit'),
                mockReact.createElement('button', {onClick: () => props.onRemove(index), type: 'button'}, 'remove')
            ))
        );
    });

    CardCollectionMock.Card = jest.fn((props) => mockReact.createElement('div', null, props.children));

    return CardCollectionMock;
});

jest.mock('../../../../components/Overlay', () => jest.fn((props) => {
    if (!props.open) {
        return null;
    }

    return mockReact.createElement(
        'div',
        {role: 'dialog'},
        mockReact.createElement('button', {onClick: props.onClose, type: 'button'}, 'close'),
        mockReact.createElement(
            'button',
            {
                disabled: props.confirmDisabled,
                onClick: props.onConfirm,
                type: 'button',
            },
            'confirm'
        ),
        props.children
    );
}));

jest.mock('../../../Form', () => {
    const mockReactForForm = require('react');

    const memoryFormStoreFactory = {
        createFromSchema: jest.fn((schema, jsonSchema, data = {}) => {
            const store: any = {
                data: {...data},
                dirty: true,
                schema,
                validate: jest.fn().mockReturnValue(true),
                destroy: jest.fn(),
            };

            store.change = jest.fn((name, value) => {
                store.data[name] = value;
                store.dirty = true;
            });

            return store;
        }),
    };

    const FormMock = mockReactForForm.forwardRef((props, ref) => {
        mockFormProps = props;

        mockReactForForm.useImperativeHandle(ref, () => ({
            submit: () => {
                if (props.store.validate()) {
                    props.onSubmit();
                }
            },
        }));

        return mockReactForForm.createElement(
            'div',
            null,
            Object.keys(props.store.schema).map((name) => mockReactForForm.createElement('input', {
                'aria-label': name,
                defaultValue: props.store.data[name] || '',
                key: name,
                onChange: (event) => props.store.change(name, event.target.value),
            }))
        );
    });

    return {
        __esModule: true,
        default: FormMock,
        memoryFormStoreFactory,
    };
});

beforeEach(() => {
    mockFormProps = {};
});

test('Render a CardCollection', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));

    const fieldTypeOptions = {
        renderCardContent: jest.fn((card) => card.firstName + ' ' + card.lastName),
        schema: {},
    };

    const value = [
        {
            firstName: 'Max', lastName: 'Mustermann',
        },
        {
            firstName: 'Erika',
            lastName: 'Mustermann',
        },
    ];

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(screen.getByText('Max Mustermann')).toBeInTheDocument();
    expect(screen.getByText('Erika Mustermann')).toBeInTheDocument();
});

test('Close the overlay when its close button is clicked', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));

    const changeSpy = jest.fn();

    const fieldTypeOptions = {
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

    const value = [
        {
            firstName: 'Max',
            lastName: 'Mustermann',
        },
        {
            firstName: 'Erika',
            lastName: 'Mustermann',
        },
    ];

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            value={value}
        />
    );

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    await user.click(screen.getByText('add'));
    expect(screen.getByRole('dialog')).toBeInTheDocument();

    await user.click(screen.getByText('close'));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Add a new card using the overlay', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));

    const changeSpy = jest.fn();

    const fieldTypeOptions = {
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

    const value = [
        {
            firstName: 'Max',
            lastName: 'Mustermann',
        },
        {
            firstName: 'Erika',
            lastName: 'Mustermann',
        },
    ];

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            value={value}
        />
    );

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    await user.click(screen.getByText('add'));
    expect(screen.getByRole('dialog')).toBeInTheDocument();

    mockFormProps.store.data.firstName = 'John';
    mockFormProps.store.data.lastName = 'Doe';
    await user.click(screen.getByText('confirm'));

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());

    expect(changeSpy).toHaveBeenCalledWith([...value, {firstName: 'John', lastName: 'Doe'}]);
});

test('Do not add a new card if validation fails', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));

    const changeSpy = jest.fn();

    const fieldTypeOptions = {
        jsonSchema: {
            required: ['firstName', 'lastName'],
        },
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

    const value = [];

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            value={value}
        />
    );

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    await user.click(screen.getByText('add'));
    expect(screen.getByRole('dialog')).toBeInTheDocument();

    mockFormProps.store.validate.mockReturnValue(false);

    mockFormProps.store.data.firstName = 'John';
    await user.click(screen.getByText('confirm'));

    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Edit an existing card using the overlay', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));

    const changeSpy = jest.fn();

    const fieldTypeOptions = {
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

    const value = [
        {
            firstName: 'Max',
            lastName: 'Mustermann',
        },
        {
            firstName: 'Erika',
            lastName: 'Mustermann',
        },
    ];

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            value={value}
        />
    );

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    await user.click(screen.getAllByText('edit')[0]);
    expect(screen.getByRole('dialog')).toBeInTheDocument();

    mockFormProps.store.data.firstName = 'John';
    mockFormProps.store.data.lastName = 'Doe';
    await user.click(screen.getByText('confirm'));

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());

    expect(changeSpy).toHaveBeenCalledWith([{firstName: 'John', lastName: 'Doe'}, value[1]]);
});

test('Remove an existing card', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));

    const changeSpy = jest.fn();

    const fieldTypeOptions = {
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

    const value = [
        {
            firstName: 'Max',
            lastName: 'Mustermann',
        },
        {
            firstName: 'Erika',
            lastName: 'Mustermann',
        },
    ];

    render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            value={value}
        />
    );

    await user.click(screen.getAllByText('remove')[1]);

    expect(changeSpy).toHaveBeenCalledWith([value[0]]);
});

test('Throw error when no renderCardContent function is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    expect(() => new CardCollection(({
        ...fieldTypeDefaultProps,
        formInspector,
    }: any))).toThrow(/"renderCardContent"/);
});

test('Throw error when no schema function is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const fieldTypeOptions = {
        renderCardContent: jest.fn(),
    };

    expect(() => new CardCollection(({
        ...fieldTypeDefaultProps,
        fieldTypeOptions,
        formInspector,
    }: any))).toThrow(/"schema"/);
});
