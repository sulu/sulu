// @flow
import React from 'react';
import {act} from 'react-dom/test-utils';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FieldFilter from '../FieldFilter';
import FieldFilterItemComponent from '../FieldFilterItem';
import listFieldFilterTypeRegistry from '../registries/listFieldFilterTypeRegistry';

jest.mock('../FieldFilterItem', () => jest.fn(function FieldFilterItem() {
    return <div />;
}));

jest.mock('../../../components/Button', () => jest.fn(function Button(props) {
    return (
        <button onClick={props.onClick} type="button">
            {props.children || props.icon}
        </button>
    );
}));

jest.mock('../../../components/ArrowMenu', () => {
    const ArrowMenu: any = jest.fn(function ArrowMenu(props) {
        return (
            <div>
                {props.anchorElement}
                {props.children}
            </div>
        );
    });
    ArrowMenu.Section = jest.fn(function Section(props) {
        return <div>{props.children}</div>;
    });
    ArrowMenu.Action = jest.fn(function Action(props) {
        function handleClick() {
            props.onClick(props.value);
        }

        return (
            <button
                disabled={props.disabled}
                // eslint-disable-next-line react/jsx-no-bind
                onClick={handleClick}
                type="button"
            >
                {props.children}
            </button>
        );
    });

    return ArrowMenu;
});

jest.mock('../registries/listFieldFilterTypeRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
}));

function getLatestFieldFilterItemProps(column) {
    const calls = (FieldFilterItemComponent: any).mock.calls;
    const matchingProps = calls.map(([props]) => props).filter((props) => props.column === column);

    return matchingProps[matchingProps.length - 1];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render empty FieldFilter', () => {
    const schema = {};
    const value = {};

    const {asFragment} = render(<FieldFilter fields={schema} onChange={jest.fn()} value={value} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Render FieldFilter with schema and value', () => {
    const schema = {
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

    const value = {
        firstName: undefined,
        lastName: undefined,
    };

    render(<FieldFilter fields={schema} onChange={jest.fn()} value={value} />);
    expect(FieldFilterItemComponent).toHaveBeenCalledTimes(2);
    expect(getLatestFieldFilterItemProps('firstName')).toEqual(expect.objectContaining({
        column: 'firstName',
        filterType: 'text',
        filterTypeParameters: {test: 'value'},
        label: 'First name',
        value: undefined,
    }));
    expect(getLatestFieldFilterItemProps('lastName')).toEqual(expect.objectContaining({
        column: 'lastName',
        filterType: 'text',
        filterTypeParameters: null,
        label: 'Last name',
        value: undefined,
    }));
});

test('Show filter options in disabled state if a filter for them was already added', async() => {
    const user = userEvent.setup();

    listFieldFilterTypeRegistry.get.mockReturnValue(class {
        getFormNode = jest.fn();
        getValueNode = jest.fn();
        setValue = jest.fn();
    });

    const changeSpy = jest.fn();

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

    const value = {
        firstName: undefined,
    };

    render(<FieldFilter fields={schema} onChange={changeSpy} value={value} />);
    await user.click(screen.getByRole('button', {name: 'su-filter'}));

    expect(screen.getByRole('button', {name: 'First name'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'Last name'})).toBeEnabled();
});

test('Call onChange with new filter chip when Action in ArrowMenu was clicked', async() => {
    const user = userEvent.setup();

    listFieldFilterTypeRegistry.get.mockReturnValue(class {
        getFormNode = jest.fn();
        getValueNode = jest.fn();
        setValue = jest.fn();
    });

    const changeSpy = jest.fn();

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

    const value = {
        firstName: undefined,
    };

    render(<FieldFilter fields={schema} onChange={changeSpy} value={value} />);
    await user.click(screen.getByRole('button', {name: 'su-filter'}));
    await user.click(screen.getByRole('button', {name: 'Last name'}));

    expect(changeSpy).toBeCalledWith({firstName: undefined, lastName: undefined});
});

test('Call onChange with new filter value when onChange from FieldFilterItem is called', () => {
    listFieldFilterTypeRegistry.get.mockReturnValue(class {
        getFormNode = jest.fn();
        getValueNode = jest.fn();
        setValue = jest.fn();
    });

    const changeSpy = jest.fn();

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

    const value = {
        firstName: undefined,
    };

    render(<FieldFilter fields={schema} onChange={changeSpy} value={value} />);

    expect(getLatestFieldFilterItemProps('firstName').open).toEqual(false);

    act(() => {
        getLatestFieldFilterItemProps('firstName').onClick('firstName');
    });
    expect(getLatestFieldFilterItemProps('firstName').open).toEqual(true);

    act(() => {
        getLatestFieldFilterItemProps('firstName').onChange('firstName', 'Max');
    });

    expect(changeSpy).toBeCalledWith({firstName: 'Max'});
    expect(getLatestFieldFilterItemProps('firstName').open).toEqual(false);
});

test('Call onChange without filter chip for which delete icon was clicked', () => {
    listFieldFilterTypeRegistry.get.mockReturnValue(class {
        getFormNode = jest.fn();
        getValueNode = jest.fn();
        setValue = jest.fn();
    });

    const changeSpy = jest.fn();

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

    const value = {
        firstName: 'First Name',
        lastName: 'Last Name',
    };

    render(<FieldFilter fields={schema} onChange={changeSpy} value={value} />);

    act(() => {
        getLatestFieldFilterItemProps('lastName').onDelete('lastName');
    });

    expect(changeSpy).toBeCalledWith({firstName: 'First Name'});
});
