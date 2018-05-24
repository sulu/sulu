// @flow
import React from 'react';
import {shallow} from 'enzyme';
import SingleSelection from '../../fields/SingleSelection';

test('Pass correct props to AutoComplete', () => {
    const value = {
        test: 'value',
    };

    const fieldTypeOptions = {
        displayProperty: 'name',
        resourceKey: 'accounts',
        searchProperties: ['name', 'number'],
    };

    const singleSelection = shallow(
        <SingleSelection fieldTypeOptions={fieldTypeOptions} onChange={jest.fn()} value={value} />
    );

    expect(singleSelection.find('AutoComplete').props()).toEqual(expect.objectContaining({
        displayProperty: 'name',
        resourceKey: 'accounts',
        searchProperties: ['name', 'number'],
        value,
    }));
});

test('Call onChange and onFinish when AutoComplete changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const value = {
        test: 'value',
    };

    const fieldTypeOptions = {
        displayProperty: 'name',
        resourceKey: 'accounts',
        searchProperties: ['name', 'number'],
    };

    const singleSelection = shallow(
        <SingleSelection fieldTypeOptions={fieldTypeOptions} onChange={changeSpy} onFinish={finishSpy} value={value} />
    );

    singleSelection.find('AutoComplete').simulate('change', undefined);

    expect(changeSpy).toBeCalledWith(undefined);
    expect(finishSpy).toBeCalledWith();
});
