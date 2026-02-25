// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {Input as InputComponent} from 'sulu-admin-bundle/components';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import Input from '../../ruleTypes/Input';

jest.mock('sulu-admin-bundle/components', () => ({
    Input: jest.fn(function MockInput(props) {
        return (
            <input
                data-testid="target-group-rules-input"
                readOnly={true}
                value={props.value || ''}
            />
        );
    }),
}));

test.each([
    ['test1', 'value1'],
    ['test2', 'value2'],
])('Pass the change callback for "%s" with a value of "%s"', (name, value) => {
    const changeSpy = jest.fn();

    render(<Input onChange={changeSpy} options={{name}} value={{}} />);
    getLatestMockProps(InputComponent).onChange(value);

    expect(changeSpy).toBeCalledWith({[name]: value});
});

test.each([
    ['test1', 'value1'],
    ['test2', 'value2'],
])('Pass value for "%s" with a value of "%s" correctly to Input', (name, value) => {
    render(<Input onChange={jest.fn()} options={{name}} value={{[name]: value}} />);
    expect(getLatestMockProps(InputComponent).value).toEqual(value);
});
