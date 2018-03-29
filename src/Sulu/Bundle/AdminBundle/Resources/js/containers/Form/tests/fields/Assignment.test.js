// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import Assignment from '../../fields/Assignment';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import ResourceStore from '../../../../stores/ResourceStore';

jest.mock('../../FormInspector', () => jest.fn(function() {
    this.locale = 'en';
}));
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../../../stores/ResourceStore', () => jest.fn());

test('Should pass props correctly to component', () => {
    const changeSpy = jest.fn();
    const locale = observable.box('en');
    const value = [1, 6, 8];
    const fieldTypeOptions = {
        adapter: 'table',
        displayProperties: ['id', 'title'],
        icon: '',
        label: 'Select snippets',
        overlayTitle: 'Snippets',
        resourceKey: 'snippets',
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const assignment = shallow(
        <Assignment
            formInspector={formInspector}
            fieldTypeOptions={fieldTypeOptions}
            onChange={changeSpy}
            value={value}
        />
    );

    expect(assignment.find('Assignment').props()).toEqual(expect.objectContaining({
        adapter: 'table',
        displayProperties: ['id', 'title'],
        label: 'Select snippets',
        locale: 'en',
        onChange: changeSpy,
        resourceKey: 'snippets',
        overlayTitle: 'Snippets',
        value,
    }));
});

test('Should pass empty array if value is not given', () => {
    const changeSpy = jest.fn();
    const fieldOptions = {
        adapter: 'column_list',
        resourceKey: 'pages',
    };
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const assignment = shallow(
        <Assignment
            formInspector={formInspector}
            fieldTypeOptions={fieldOptions}
            onChange={changeSpy}
            value={undefined}
        />
    );

    expect(assignment.find('Assignment').props()).toEqual(expect.objectContaining({
        adapter: 'column_list',
        onChange: changeSpy,
        resourceKey: 'pages',
        value: [],
    }));
});

test('Should throw an error if no fieldOptions are passed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));
    expect(() => shallow(<Assignment formInspector={formInspector} onChange={jest.fn()} value={undefined} />))
        .toThrowError(/a "resourceKey" and a "adapter"/);
});

test('Should throw an error if no resourceKey is passed in fieldOptions', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    expect(() => shallow(
        <Assignment
            formInspector={formInspector}
            fieldTypeOptions={{}}
            onChange={jest.fn()}
            value={undefined}
        />
    )).toThrowError(/"resourceKey"/);
});

test('Should throw an error if no adapter is passed in fieldTypeOptions', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    expect(() => shallow(
        <Assignment
            formInspector={formInspector}
            onChange={jest.fn()}
            fieldTypeOptions={{resourceKey: 'test'}}
            value={undefined}
        />
    )).toThrowError(/"adapter"/);
});
