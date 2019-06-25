// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Input from '../../ruleTypes/Input';

test.each([
    ['test1', 'value1'],
    ['test2', 'value2'],
])('Pass the change callback for "%s" with a value of "%s"', (name, value) => {
    const changeSpy = jest.fn();

    const input = shallow(<Input onChange={changeSpy} options={{name}} value={{}} />);
    input.find('Input').prop('onChange')(value);
    expect(changeSpy).toBeCalledWith({[name]: value});
});

test.each([
    ['test1', 'value1'],
    ['test2', 'value2'],
])('Pass value for "%s" with a value of "%s" correctly to Input', (name, value) => {
    const input = shallow(<Input onChange={jest.fn()} options={{name}} value={{[name]: value}} />);
    expect(input.find('Input').prop('value')).toEqual(value);
});
