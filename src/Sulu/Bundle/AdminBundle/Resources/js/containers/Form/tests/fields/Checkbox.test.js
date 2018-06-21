// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import Checkbox from '../../fields/Checkbox';
import CheckboxComponent from '../../../../components/Checkbox';
import Toggler from '../../../../components/Toggler';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass the value of true correctly to Checkbox component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const checkbox = shallow(
        <Checkbox formInspector={formInspector} onChange={jest.fn()} schemaPath="" value={true} />
    );
    expect(checkbox.find(CheckboxComponent).prop('checked')).toEqual(true);
});

test('Pass the value of false correctly to Checkbox component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const checkbox = shallow(
        <Checkbox formInspector={formInspector} onChange={jest.fn()} schemaPath="" value={false} />
    );
    expect(checkbox.find(CheckboxComponent).prop('checked')).toEqual(false);
});

test('Call onChange and onFinish on the changed callback of the Checkbox', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const checkbox = shallow(
        <Checkbox formInspector={formInspector} onChange={changeSpy} onFinish={finishSpy} schemaPath="" value={false} />
    );
    checkbox.find(CheckboxComponent).simulate('change', true);

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});

test('Pass the value of true correctly to Toggler component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const checkbox = shallow(
        <Checkbox
            formInspector={formInspector}
            onChange={jest.fn()}
            schemaOptions={{type: {value: 'toggler'}}}
            schemaPath=""
            value={true}
        />
    );
    expect(checkbox.find(Toggler).prop('checked')).toEqual(true);
});

test('Pass the value of false correctly to Toggler component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const checkbox = shallow(
        <Checkbox
            formInspector={formInspector}
            onChange={jest.fn()}
            schemaOptions={{type: {value: 'toggler'}}}
            schemaPath=""
            value={false}
        />
    );
    expect(checkbox.find(Toggler).prop('checked')).toEqual(false);
});

test('Call onChange and onFinish on the changed callback of the Toggler', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const checkbox = shallow(
        <Checkbox
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={{type: {value: 'toggler'}}}
            schemaPath=""
            value={false}
        />
    );
    checkbox.find(Toggler).simulate('change', true);

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});
