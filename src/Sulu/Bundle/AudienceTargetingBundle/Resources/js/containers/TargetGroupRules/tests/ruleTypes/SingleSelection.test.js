// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SingleSelection from '../../ruleTypes/SingleSelection';

let mockSingleSelectionProps: Object = {};

const mockReact = require('react');

jest.mock('sulu-admin-bundle/containers', () => ({
    SingleSelection: jest.fn((props) => {
        mockSingleSelectionProps = props;

        return mockReact.createElement(
            'button',
            {
                onClick: () => props.onChange('changed-value'),
                type: 'button',
            },
            props.value || 'empty'
        );
    }),
}));

beforeEach(() => {
    mockSingleSelectionProps = {};
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

    expect(mockSingleSelectionProps.value).toEqual(result);
    expect(mockSingleSelectionProps.adapter).toEqual(adapter);
    expect(mockSingleSelectionProps.displayProperties).toEqual(displayProperties);
    expect(mockSingleSelectionProps.emptyText).toEqual(emptyText);
    expect(mockSingleSelectionProps.icon).toEqual(icon);
    expect(mockSingleSelectionProps.overlayTitle).toEqual(overlayTitle);
    expect(mockSingleSelectionProps.resourceKey).toEqual(resourceKey);
});

test.each([
    ['test1', 'value1'],
    ['test2', 'value2'],
])('Pass correct value for "%s" using "%s" in onChange', async(name) => {
    const user = userEvent.setup();
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

    await user.click(screen.getByRole('button'));

    expect(changeSpy).toHaveBeenCalledWith({[name]: 'changed-value'});
});
