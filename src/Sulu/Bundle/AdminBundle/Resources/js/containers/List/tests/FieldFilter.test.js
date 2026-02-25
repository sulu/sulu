// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import bindValueToOnChange from '../../../utils/TestHelper/bindValueToOnChange';
import FieldFilter from '../FieldFilter';

jest.mock('../registries/listFieldFilterTypeRegistry', () => {
    const TextFieldFilterType = jest.requireActual('../fieldFilterTypes/TextFieldFilterType').default;

    return {
        get: jest.fn(() => TextFieldFilterType),
        getOptions: jest.fn(() => ({})),
    };
});

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const schema = {
    firstName: {
        filterType: 'text',
        filterTypeParameters: null,
        transformerTypeParameters: {},
        label: 'First name',
        sortable: true,
        type: 'string',
        visibility: 'yes',
    },
    lastName: {
        filterType: 'text',
        filterTypeParameters: null,
        transformerTypeParameters: {},
        label: 'Last name',
        sortable: true,
        type: 'string',
        visibility: 'yes',
    },
};

beforeEach(() => {
    jest.clearAllMocks();
});

const getFilterMenuButton = () => {
    const button = document.querySelector('.filterButton button');

    if (!(button instanceof HTMLButtonElement)) {
        throw new Error('Expected filter menu button to be rendered');
    }

    return button;
};

test('Render empty FieldFilter', () => {
    render(<FieldFilter fields={{}} onChange={jest.fn()} value={{}} />);

    expect(screen.queryByRole('button')).not.toBeInTheDocument();
});

test('Render FieldFilter with schema and value', async() => {
    render(
        <FieldFilter
            fields={{
                ...schema,
                firstName: {
                    ...schema.firstName,
                    filterTypeParameters: {test: 'value'},
                },
            }}
            onChange={jest.fn()}
            value={{
                firstName: undefined,
                lastName: undefined,
            }}
        />
    );

    expect(await screen.findByRole('button', {name: /First name:/})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /Last name:/})).toBeInTheDocument();
});

test('Show filter options in disabled state if a filter for them was already added', async() => {
    const user = userEvent.setup();
    render(
        <FieldFilter
            fields={schema}
            onChange={jest.fn()}
            value={{firstName: undefined}}
        />
    );

    await user.click(getFilterMenuButton());
    expect(screen.getByRole('button', {name: 'First name'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'Last name'})).toBeEnabled();
});

test('Call onChange with new filter chip when Action in ArrowMenu was clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    render(
        <FieldFilter
            fields={schema}
            onChange={changeSpy}
            value={{firstName: undefined}}
        />
    );

    await user.click(getFilterMenuButton());
    await user.click(screen.getByRole('button', {name: 'Last name'}));

    expect(changeSpy).toBeCalledWith({firstName: undefined, lastName: undefined});
});

test('Call onChange with new filter value when onChange from FieldFilterItem is called', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    render(bindValueToOnChange(
        <FieldFilter fields={schema} onChange={changeSpy} value={{firstName: undefined}} />
    ));

    await user.click(await screen.findByRole('button', {name: /First name:/}));
    await user.type(screen.getByRole('textbox'), 'Max');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(changeSpy).toHaveBeenLastCalledWith({firstName: {eq: 'Max'}});
});

test('Call onChange without filter chip for which delete icon was clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    render(
        <FieldFilter
            fields={schema}
            onChange={changeSpy}
            value={{
                firstName: {eq: 'First Name'},
                lastName: {eq: 'Last Name'},
            }}
        />
    );

    const lastNameChip = await screen.findByRole('button', {name: /Last name: Last Name/});
    await user.click(within(lastNameChip).getByRole('button', {name: 'su-times'}));

    expect(changeSpy).toBeCalledWith({firstName: {eq: 'First Name'}});
});
