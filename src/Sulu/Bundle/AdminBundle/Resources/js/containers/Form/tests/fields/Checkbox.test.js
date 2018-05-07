// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Checkbox from '../../fields/Checkbox';
import CheckboxComponent from '../../../../components/Checkbox';
import Toggler from '../../../../components/Toggler';

test('Pass the value of true correctly to Checkbox component', () => {
    const checkbox = shallow(<Checkbox onChange={jest.fn()} value={true} />);
    expect(checkbox.find(CheckboxComponent).prop('checked')).toEqual(true);
});

test('Pass the value of false correctly to Checkbox component', () => {
    const checkbox = shallow(<Checkbox onChange={jest.fn()} value={false} />);
    expect(checkbox.find(CheckboxComponent).prop('checked')).toEqual(false);
});

test('Call onChange and onFinish on the changed callback of the Checkbox', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const checkbox = shallow(<Checkbox onChange={changeSpy} onFinish={finishSpy} value={false} />);
    checkbox.find(CheckboxComponent).simulate('change', true);

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});

test('Pass the value of true correctly to Toggler component', () => {
    const checkbox = shallow(<Checkbox onChange={jest.fn()} schemaOptions={{type: {value: 'toggler'}}} value={true} />);
    expect(checkbox.find(Toggler).prop('checked')).toEqual(true);
});

test('Pass the value of false correctly to Toggler component', () => {
    const checkbox = shallow(
        <Checkbox onChange={jest.fn()} schemaOptions={{type: {value: 'toggler'}}} value={false} />
    );
    expect(checkbox.find(Toggler).prop('checked')).toEqual(false);
});

test('Call onChange and onFinish on the changed callback of the Toggler', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const checkbox = shallow(
        <Checkbox onChange={changeSpy} onFinish={finishSpy} schemaOptions={{type: {value: 'toggler'}}} value={false} />
    );
    checkbox.find(Toggler).simulate('change', true);

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});
