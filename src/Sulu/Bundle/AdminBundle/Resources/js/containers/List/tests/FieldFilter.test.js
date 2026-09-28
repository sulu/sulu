// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FieldFilter from '../FieldFilter';
import listFieldFilterTypeRegistry from '../registries/listFieldFilterTypeRegistry';

jest.mock('../registries/listFieldFilterTypeRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../utils/Translator');

const SCHEMA = {
    firstName: {
        filterType: 'text',
        filterTypeParameters: {test: 'value'},
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

class FilterInput extends React.Component<Object> {
    handleChange = (event) => {
        this.props.onChange(event.currentTarget.value);
    };

    render() {
        return <input aria-label="filter-input" onChange={this.handleChange} />;
    }
}

function createFilterType() {
    return jest.fn((onChange, filterTypeParameters, value, options) => ({
        confirm: jest.fn(),
        destroy: jest.fn(),
        filterTypeParameters,
        getFormNode: jest.fn(() => <FilterInput onChange={onChange} />),
        getValueNode: jest.fn((value) => Promise.resolve(value)),
        options,
        setValue: jest.fn(),
        value,
    }));
}

function getButtonByIcon(icon: string): HTMLButtonElement {
    const iconElement = screen.getByLabelText(icon);
    const button = iconElement.parentElement;

    if (!(button instanceof HTMLButtonElement)) {
        throw new Error('Button with icon "' + icon + '" was not rendered.');
    }

    return button;
}

beforeEach(() => {
    jest.clearAllMocks();
    listFieldFilterTypeRegistry.get.mockReturnValue(createFilterType());
    listFieldFilterTypeRegistry.getOptions.mockReturnValue({});
});

test('Render empty FieldFilter', () => {
    render(<FieldFilter fields={{}} onChange={jest.fn()} value={{}} />);

    expect(screen.queryByLabelText('su-filter')).not.toBeInTheDocument();
});

test('Render FieldFilter with schema and value', () => {
    render(
        <FieldFilter
            fields={SCHEMA}
            onChange={jest.fn()}
            value={{firstName: undefined, lastName: undefined}}
        />
    );

    expect(screen.getByRole('button', {name: /First name:/})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /Last name:/})).toBeInTheDocument();
});

test('Show filter options in disabled state if a filter for them was already added', async() => {
    const user = userEvent.setup();

    render(
        <FieldFilter
            fields={SCHEMA}
            onChange={jest.fn()}
            value={{firstName: undefined}}
        />
    );

    await user.click(getButtonByIcon('su-filter'));

    expect(screen.getByRole('button', {name: 'First name'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'Last name'})).toBeEnabled();
});

test('Call onChange with new filter chip when Action in ArrowMenu was clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(
        <FieldFilter
            fields={SCHEMA}
            onChange={changeSpy}
            value={{firstName: undefined}}
        />
    );

    await user.click(getButtonByIcon('su-filter'));
    await user.click(screen.getByRole('button', {name: 'Last name'}));

    expect(changeSpy).toHaveBeenCalledWith({firstName: undefined, lastName: undefined});
});

test('Call onChange with new filter value when onChange from FieldFilterItem is called', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(
        <FieldFilter
            fields={SCHEMA}
            onChange={changeSpy}
            value={{firstName: undefined}}
        />
    );

    await user.click(screen.getByRole('button', {name: /First name:/}));
    await user.type(screen.getByLabelText('filter-input'), 'Max');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(changeSpy).toHaveBeenCalledWith({firstName: 'Max'});
    expect(screen.queryByLabelText('filter-input')).not.toBeInTheDocument();
});

test('Call onChange without filter chip for which delete icon was clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(
        <FieldFilter
            fields={SCHEMA}
            onChange={changeSpy}
            value={{firstName: 'First Name', lastName: 'Last Name'}}
        />
    );

    await user.click(within(screen.getByRole('button', {name: /Last name:/})).getByLabelText('su-times'));

    expect(changeSpy).toHaveBeenCalledWith({firstName: 'First Name'});
});
