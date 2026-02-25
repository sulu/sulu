// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import {SingleSelect as SingleSelectComponent} from 'sulu-admin-bundle/components';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import SingleSelect from '../../ruleTypes/SingleSelect';

jest.mock('sulu-admin-bundle/components', () => {
    const SingleSelectComponent = function SingleSelectMock(props) {
        return (
            <select
                data-testid="single-select"
                defaultValue={props.value || ''}
            >
                {props.children}
            </select>
        );
    };
    const SingleSelectMock: any = jest.fn(SingleSelectComponent);

    function OptionMock(props) {
        return (
            <option value={props.value}>
                {props.children}
            </option>
        );
    }

    SingleSelectMock.Option = OptionMock;

    return {
        SingleSelect: SingleSelectMock,
    };
});

test.each([
    [[{id: 'firefox', name: 'Firefox'}]],
    [[{id: 'firefox', name: 'Firefox'}, {id: 'chrome', name: 'Chrome'}]],
    [[{id: 'ie', name: 'Internet Explorer'}, {id: 'firefox', name: 'Firefox'}]],
])('Option should be listed in Select #%#', (options) => {
    render(<SingleSelect onChange={jest.fn()} options={{options}} value={{}} />);
    const selectOptions = screen.getAllByRole('option');

    options.forEach((option, index) => {
        expect(selectOptions[index]).toHaveValue(option.id);
        expect(selectOptions[index]).toHaveTextContent(option.name);
    });
});

test.each([
    ['name1', 'value1'],
    ['name2', 'value2'],
])('Call onChange for "%s" with a value of "%s"', (name, value) => {
    const changeSpy = jest.fn();

    render(<SingleSelect onChange={changeSpy} options={{name, options: []}} value={{}} />);
    const singleSelectProps: any = getLatestMockProps(SingleSelectComponent);
    singleSelectProps.onChange(value);

    expect(changeSpy).toBeCalledWith({[name]: value});
});

test.each([
    ['name1', 'value1'],
    ['name2', 'value2'],
])('Display correct value for "%s" with a value of "%s"', (name, value) => {
    const changeSpy = jest.fn();

    render(
        <SingleSelect onChange={changeSpy} options={{name, options: []}} value={{[name]: value}} />
    );

    const singleSelectProps: any = getLatestMockProps(SingleSelectComponent);
    expect(singleSelectProps.value).toEqual(value);
});
