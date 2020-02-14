// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import FieldFilter from '../FieldFilter';

test('Render empty FieldFilter', () => {
    const schema = {};
    const value = {};

    expect(render(<FieldFilter fields={schema} onChange={jest.fn()} value={value} />)).toMatchSnapshot();
});

test('Render FieldFilter with schema and value', () => {
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
        lastName: undefined,
    };

    expect(render(<FieldFilter fields={schema} onChange={jest.fn()} value={value} />)).toMatchSnapshot();
});

test('Show filter options in disabled state if a filter for them was already added', () => {
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

    fieldFilter.find('Button[icon="fa-filter"]').simulate('click');

    expect(fieldFilter.find('ArrowMenu Action[value="firstName"]').prop('disabled')).toEqual(true);
    expect(fieldFilter.find('ArrowMenu Action[value="lastName"]').prop('disabled')).toEqual(false);
});

test('Call onChange with new filter chip when Action in ArrowMenu was clicked', () => {
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
    fieldFilter.find('Button[icon="fa-filter"]').simulate('click');
    fieldFilter.find('ArrowMenu Action[value="lastName"]').simulate('click');

    expect(changeSpy).toBeCalledWith({firstName: undefined, lastName: undefined});
});

test('Call onChange without filter chip for which delete icon was clicked', () => {
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
        lastName: undefined,
    };

    const fieldFilter = mount(<FieldFilter fields={schema} onChange={changeSpy} value={value} />);

    fieldFilter.find('Chip[value="lastName"] Icon[name="su-times"]').simulate('click');

    expect(changeSpy).toBeCalledWith({firstName: undefined});
});
