// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {SingleSelect as SingleSelectComponent} from 'sulu-admin-bundle/components';
import SingleSelect from '../../ruleTypes/SingleSelect';

jest.mock('sulu-admin-bundle/components', () => {
    const SingleSelectMock = jest.fn(() => null);
    (SingleSelectMock: any).Option = jest.fn(() => null);

    return {
        SingleSelect: SingleSelectMock,
    };
});

function getLatestSingleSelectProps() {
    const calls = (SingleSelectComponent: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getOptionProps(index: number) {
    const singleSelectProps = getLatestSingleSelectProps();
    const options = React.Children.toArray(singleSelectProps.children);
    return options[index].props;
}

beforeEach(() => {
    jest.clearAllMocks();
});

test.each([
    [[{id: 'firefox', name: 'Firefox'}]],
    [[{id: 'firefox', name: 'Firefox'}, {id: 'chrome', name: 'Chrome'}]],
    [[{id: 'ie', name: 'Internet Explorer'}, {id: 'firefox', name: 'Firefox'}]],
])('Option should be listed in Select #%#', (options) => {
    render(<SingleSelect onChange={jest.fn()} options={{options}} value={{}} />);

    options.forEach((option, index) => {
        const optionProps = getOptionProps(index);
        expect(optionProps.value).toEqual(option.id);
        expect(optionProps.children).toEqual(option.name);
    });
});

test.each([
    ['name1', 'value1'],
    ['name2', 'value2'],
])('Call onChange for "%s" with a value of "%s"', (name, value) => {
    const changeSpy = jest.fn();

    render(<SingleSelect onChange={changeSpy} options={{name, options: []}} value={{}} />);
    getLatestSingleSelectProps().onChange(value);

    expect(changeSpy).toBeCalledWith({[name]: value});
});

test.each([
    ['name1', 'value1'],
    ['name2', 'value2'],
])('Display correct value for "%s" with a value of "%s"', (name, value) => {
    render(<SingleSelect onChange={jest.fn()} options={{name, options: []}} value={{[name]: value}} />);

    expect(getLatestSingleSelectProps().value).toEqual(value);
});
