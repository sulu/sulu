// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import FormInspector from '../../FormInspector';
import MemoryFormStore from '../../stores/MemoryFormStore';
import CardCollection from '../../fields/CardCollection';
import Overlay from '../../../../components/Overlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../components/Overlay', () => jest.fn(function Overlay(props) {
    return props.open ? (
        <div>
            <button onClick={props.onConfirm} type="button">
                confirm-overlay
            </button>
            <button onClick={props.onClose} type="button">
                close-overlay
            </button>
            {props.children}
        </div>
    ) : null;
}));

jest.mock('../../stores/MemoryFormStore', () => jest.fn(function(data, schema) {
    this.data = data;
    this.schema = schema;
    this.change = jest.fn().mockImplementation((name, value) => {
        this.data[name] = value;
    });
    this.validate = jest.fn().mockReturnValue(true);
    this.isFieldModified = jest.fn().mockReturnValue(false);
    this.destroy = jest.fn();
    this.types = {};
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
    return new FormInspector(({isFieldModified: jest.fn()}: any));
}

function getLatestOverlayProps() {
    const calls = ((Overlay: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getLatestMemoryFormStore() {
    const instances = ((MemoryFormStore: any).mock.instances: any);
    return instances[instances.length - 1];
}

function renderCardCollection(props: Object = {}) {
    const fieldTypeOptions = props.fieldTypeOptions || {
        renderCardContent: jest.fn((card) => card.firstName + ' ' + card.lastName),
        schema: {},
    };

    return render(
        <CardCollection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={createFormInspector()}
            onChange={props.onChange || jest.fn()}
            value={props.value}
        />
    );
}

function expectRenderToThrow(renderFn: () => void, message: RegExp) {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
    expect(renderFn).toThrow(message);
    consoleErrorSpy.mockRestore();
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render a CardCollection', () => {
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

    const {asFragment} = renderCardCollection({
        fieldTypeOptions,
        value,
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Close the overlay when its close button is clicked', async() => {
    const user = userEvent.setup();
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

    renderCardCollection({
        fieldTypeOptions,
        onChange: changeSpy,
        value,
    });

    expect(getLatestOverlayProps().open).toEqual(false);
    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    expect(getLatestOverlayProps().open).toEqual(true);

    await user.click(screen.getByRole('button', {name: 'close-overlay'}));
    expect(getLatestOverlayProps().open).toEqual(false);

    expect(changeSpy).not.toBeCalled();
});

test('Add a new card using the overlay', async() => {
    const user = userEvent.setup();
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

    renderCardCollection({
        fieldTypeOptions,
        onChange: changeSpy,
        value,
    });

    expect(getLatestOverlayProps().open).toEqual(false);
    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    expect(getLatestOverlayProps().open).toEqual(true);

    const formStore = getLatestMemoryFormStore();
    formStore.data = {
        firstName: 'John',
        lastName: 'Doe',
    };
    await user.click(screen.getByRole('button', {name: 'confirm-overlay'}));

    expect(getLatestOverlayProps().open).toEqual(false);
    expect(changeSpy).toBeCalledWith([...value, {firstName: 'John', lastName: 'Doe'}]);
});

test('Do not add a new card if validation fails', async() => {
    const user = userEvent.setup();
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

    renderCardCollection({
        fieldTypeOptions,
        onChange: changeSpy,
        value,
    });

    expect(getLatestOverlayProps().open).toEqual(false);
    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    expect(getLatestOverlayProps().open).toEqual(true);

    const formStore = getLatestMemoryFormStore();
    formStore.validate.mockReturnValue(false);
    formStore.data = {
        firstName: 'John',
    };

    await user.click(screen.getByRole('button', {name: 'confirm-overlay'}));

    expect(getLatestOverlayProps().open).toEqual(true);
    expect(changeSpy).not.toBeCalled();
});

test('Edit an existing card using the overlay', async() => {
    const user = userEvent.setup();
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

    renderCardCollection({
        fieldTypeOptions,
        onChange: changeSpy,
        value,
    });

    expect(getLatestOverlayProps().open).toEqual(false);
    await user.click(screen.getAllByRole('button', {name: 'su-pen'})[0]);
    expect(getLatestOverlayProps().open).toEqual(true);

    const formStore = getLatestMemoryFormStore();
    formStore.data = {
        firstName: 'John',
        lastName: 'Doe',
    };

    await user.click(screen.getByRole('button', {name: 'confirm-overlay'}));

    expect(getLatestOverlayProps().open).toEqual(false);
    expect(changeSpy).toBeCalledWith([{firstName: 'John', lastName: 'Doe'}, value[1]]);
});

test('Edit an existing card using the overlay', async() => {
    const user = userEvent.setup();
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

    renderCardCollection({
        fieldTypeOptions,
        onChange: changeSpy,
        value,
    });

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[1]);

    expect(changeSpy).toBeCalledWith([value[0]]);
});

test('Throw error when no renderCardContent function is passed', () => {
    expectRenderToThrow(
        () => render(
            <CardCollection {...fieldTypeDefaultProps} formInspector={createFormInspector()} />
        ),
        /"renderCardContent"/
    );
});

test('Throw error when no schema function is passed', () => {
    const fieldTypeOptions = {
        renderCardContent: jest.fn(),
    };

    expectRenderToThrow(
        () => render(
            <CardCollection
                {...fieldTypeDefaultProps}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={createFormInspector()}
            />
        ),
        /"schema"/
    );
});
