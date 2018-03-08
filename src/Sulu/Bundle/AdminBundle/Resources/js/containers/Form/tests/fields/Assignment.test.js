// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Assignment from '../../fields/Assignment';

test('Should pass props correctly to component', () => {
    const changeSpy = jest.fn();
    const value = [1, 6, 8];
    const fieldOptions = {
        icon: '',
        label: 'Select snippets',
        title: 'Snippets',
        resourceKey: 'snippets',
    };
    const assignment = shallow(<Assignment onChange={changeSpy} fieldOptions={fieldOptions} value={value} />);

    expect(assignment.find('Assignment').props()).toEqual(expect.objectContaining({
        label: 'Select snippets',
        onChange: changeSpy,
        resourceKey: 'snippets',
        title: 'Snippets',
        value,
    }));
});

test('Should pass empty array if value is not given', () => {
    const changeSpy = jest.fn();
    const fieldOptions = {
        resourceKey: 'pages',
    };
    const assignment = shallow(<Assignment onChange={changeSpy} fieldOptions={fieldOptions} value={undefined} />);

    expect(assignment.find('Assignment').props()).toEqual(expect.objectContaining({
        onChange: changeSpy,
        resourceKey: 'pages',
        value: [],
    }));
});

test('Should throw an error if no fieldOptions are passed', () => {
    expect(() => shallow(<Assignment onChange={jest.fn()} value={undefined} />)).toThrowError(/"resourceKey"/);
});

test('Should throw an error if no resourceKey is passed in fieldOptions', () => {
    expect(() => shallow(<Assignment onChange={jest.fn()} fieldOptions={{}} value={undefined} />))
        .toThrowError(/"resourceKey"/);
});
