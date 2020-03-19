// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import FieldFilter from '../FieldFilter';
import listFieldFilterTypeRegistry from '../registries/listFieldFilterTypeRegistry';

jest.mock('../registries/listFieldFilterTypeRegistry', () => ({
    get: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render empty FieldFilter', () => {
    const schema = {};
    const value = {};

    expect(render(<FieldFilter fields={schema} onChange={jest.fn()} value={value} />)).toMatchSnapshot();
});

test('Render FieldFilter with schema and value', () => {
    const schema = {
        firstName: {
            filterType: 'text',
            filterTypeParameters: {test: 'value'},
            label: 'First name',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        lastName: {
            filterType: 'text',
            filterTypeParameters: null,
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

    const fieldFilter = shallow(<FieldFilter fields={schema} onChange={jest.fn()} value={value} />);
    expect(fieldFilter.find('FieldFilterItem')).toHaveLength(2);
    expect(fieldFilter.find('FieldFilterItem').at(0).props()).toEqual(expect.objectContaining({
        column: 'firstName',
        filterType: 'text',
        filterTypeParameters: {test: 'value'},
        label: 'First name',
        value: undefined,
    }));
    expect(fieldFilter.find('FieldFilterItem').at(1).props()).toEqual(expect.objectContaining({
        column: 'lastName',
        filterType: 'text',
        filterTypeParameters: null,
        label: 'Last name',
        value: undefined,
    }));
});

test('Show filter options in disabled state if a filter for them was already added', () => {
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
            label: 'First name',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        lastName: {
            filterType: 'text',
            filterTypeParameters: null,
            label: 'Last name',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };

    const value = {
        firstName: undefined,
    };

    const fieldFilter = mount(<FieldFilter fields={schema} onChange={changeSpy} value={value} />);

    fieldFilter.find('Button[icon="su-filter"]').simulate('click');

    expect(fieldFilter.find('ArrowMenu Action[value="firstName"]').prop('disabled')).toEqual(true);
    expect(fieldFilter.find('ArrowMenu Action[value="lastName"]').prop('disabled')).toEqual(false);
});

test('Call onChange with new filter chip when Action in ArrowMenu was clicked', () => {
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
            label: 'First name',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        lastName: {
            filterType: 'text',
            filterTypeParameters: null,
            label: 'Last name',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };

    const value = {
        firstName: undefined,
    };

    const fieldFilter = mount(<FieldFilter fields={schema} onChange={changeSpy} value={value} />);
    fieldFilter.find('Button[icon="su-filter"]').simulate('click');
    fieldFilter.find('ArrowMenu Action[value="lastName"]').simulate('click');

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
            label: 'First name',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        lastName: {
            filterType: 'text',
            filterTypeParameters: null,
            label: 'Last name',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };

    const value = {
        firstName: undefined,
    };

    const fieldFilter = mount(<FieldFilter fields={schema} onChange={changeSpy} value={value} />);

    expect(fieldFilter.find('FieldFilterItem[column="firstName"]').prop('open')).toEqual(false);
    fieldFilter.find('FieldFilterItem[column="firstName"]').prop('onClick')('firstName');
    fieldFilter.update();
    expect(fieldFilter.find('FieldFilterItem[column="firstName"]').prop('open')).toEqual(true);

    fieldFilter.find('FieldFilterItem[column="firstName"]').prop('onChange')('firstName', 'Max');

    fieldFilter.update();
    expect(changeSpy).toBeCalledWith({firstName: 'Max'});
    expect(fieldFilter.find('FieldFilterItem[column="firstName"]').prop('open')).toEqual(false);
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
            label: 'First name',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        lastName: {
            filterType: 'text',
            filterTypeParameters: null,
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

    const fieldFilter = mount(<FieldFilter fields={schema} onChange={changeSpy} value={value} />);

    fieldFilter.find('Chip[value="lastName"] Icon[name="su-times"]').simulate('click');

    expect(changeSpy).toBeCalledWith({firstName: 'First Name'});
});
