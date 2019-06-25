// @flow
import React from 'react';
import {shallow} from 'enzyme';
import SingleSelect from '../../ruleTypes/SingleSelect';

test.each([
    [[{id: 'firefox', name: 'Firefox'}]],
    [[{id: 'firefox', name: 'Firefox'}, {id: 'chrome', name: 'Chrome'}]],
    [[{id: 'ie', name: 'Internet Explorer'}, {id: 'firefox', name: 'Firefox'}]],
])('Option should be listed in Select #%#', (options) => {
    const singleSelect = shallow(<SingleSelect onChange={jest.fn()} options={{options}} value={{}} />);

    options.forEach((option, index) => {
        expect(singleSelect.find('Option').at(index).prop('value')).toEqual(option.id);
        expect(singleSelect.find('Option').at(index).prop('children')).toEqual(option.name);
    });
});

test.each([
    ['name1', 'value1'],
    ['name2', 'value2'],
])('Call onChange for "%s" with a value of "%s"', (name, value) => {
    const changeSpy = jest.fn();

    const singleSelect = shallow(<SingleSelect onChange={changeSpy} options={{name, options: []}} value={{}} />);
    singleSelect.find('SingleSelect').prop('onChange')(value);

    expect(changeSpy).toBeCalledWith({[name]: value});
});

test.each([
    ['name1', 'value1'],
    ['name2', 'value2'],
])('Display correct value for "%s" with a value of "%s"', (name, value) => {
    const changeSpy = jest.fn();

    const singleSelect = shallow(
        <SingleSelect onChange={changeSpy} options={{name, options: []}} value={{[name]: value}} />
    );

    expect(singleSelect.find('SingleSelect').prop('value')).toEqual(value);
});
