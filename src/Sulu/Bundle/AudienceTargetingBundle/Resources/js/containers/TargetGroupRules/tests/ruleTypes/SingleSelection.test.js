// @flow
import React from 'react';
import {shallow} from 'enzyme';
import SingleSelection from '../../ruleTypes/SingleSelection';

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
    const singleSelection = shallow(<SingleSelection onChange={jest.fn()} options={options} value={value} />);

    expect(singleSelection.find('SingleSelection').prop('value')).toEqual(result);
    expect(singleSelection.find('SingleSelection').prop('adapter')).toEqual(adapter);
    expect(singleSelection.find('SingleSelection').prop('displayProperties')).toEqual(displayProperties);
    expect(singleSelection.find('SingleSelection').prop('emptyText')).toEqual(emptyText);
    expect(singleSelection.find('SingleSelection').prop('icon')).toEqual(icon);
    expect(singleSelection.find('SingleSelection').prop('overlayTitle')).toEqual(overlayTitle);
    expect(singleSelection.find('SingleSelection').prop('resourceKey')).toEqual(resourceKey);
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

    const singleSelection = shallow(<SingleSelection onChange={changeSpy} options={options} value={{}} />);
    singleSelection.find('SingleSelection').prop('onChange')(value);

    expect(changeSpy).toBeCalledWith({[name]: value});
});
