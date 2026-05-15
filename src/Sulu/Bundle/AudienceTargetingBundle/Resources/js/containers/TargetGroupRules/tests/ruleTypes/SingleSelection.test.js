// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {SingleSelection as SingleSelectionComponent} from 'sulu-admin-bundle/containers';
import SingleSelection from '../../ruleTypes/SingleSelection';

jest.mock('sulu-admin-bundle/containers', () => ({
    SingleSelection: jest.fn(() => null),
}));

function getLatestSingleSelectionProps() {
    const calls = (SingleSelectionComponent: any).mock.calls;
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test.each([
    [
        'column_list',
        ['title', 'description'],
        'empty1',
        'su-document',
        'test1',
        'overlayTitle1',
        'snippets',
        {test1: 'Test'},
        'Test',
    ],
    [
        'table',
        ['name'],
        'empty2',
        'su-contact',
        'test2',
        'overlayTitle2',
        'contacts',
        {test2: 'Test2'},
        'Test2',
    ],
])('Pass correct values to SingleSelection #%#', (
    adapter,
    displayProperties,
    emptyText,
    icon,
    name,
    overlayTitle,
    resourceKey,
    value,
    result
) => {
    const options = {adapter, displayProperties, emptyText, icon, name, overlayTitle, resourceKey};
    render(<SingleSelection onChange={jest.fn()} options={options} value={value} />);
    const singleSelectionProps = getLatestSingleSelectionProps();

    expect(singleSelectionProps.value).toEqual(result);
    expect(singleSelectionProps.adapter).toEqual(adapter);
    expect(singleSelectionProps.displayProperties).toEqual(displayProperties);
    expect(singleSelectionProps.emptyText).toEqual(emptyText);
    expect(singleSelectionProps.icon).toEqual(icon);
    expect(singleSelectionProps.overlayTitle).toEqual(overlayTitle);
    expect(singleSelectionProps.resourceKey).toEqual(resourceKey);
});

test.each([
    ['test1', 'value1'],
    ['test2', 'value2'],
])('Pass correct value for "%s" using "%s" in onChange', (name, value) => {
    const changeSpy = jest.fn();
    const options = {
        adapter: 'table',
        displayProperties: [],
        emptyText: '',
        icon: '',
        name,
        overlayTitle: '',
        resourceKey: 'snippets',
    };

    render(<SingleSelection onChange={changeSpy} options={options} value={{}} />);
    getLatestSingleSelectionProps().onChange(value);

    expect(changeSpy).toBeCalledWith({[name]: value});
});
