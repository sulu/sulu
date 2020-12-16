// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Schedules from '../../fields/Schedules';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass correct props to Schedules component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const disabled = false;
    const value = [{type: 'fixed'}, {type: 'weekly'}];
    const schedules = shallow(
        <Schedules {...fieldTypeDefaultProps} disabled={disabled} formInspector={formInspector} value={value} />
    );

    expect(schedules.props()).toEqual(expect.objectContaining({
        disabled,
        value,
    }));
});

test('Call onChange and onFinish handler when Schedules change', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const schedules = shallow(
        <Schedules {...fieldTypeDefaultProps} formInspector={formInspector} onChange={changeSpy} onFinish={finishSpy} />
    );

    schedules.find('Schedules').simulate('change', [{type: 'fixed'}]);

    expect(changeSpy).toBeCalledWith([{type: 'fixed'}]);
    expect(finishSpy).toBeCalledWith();
});
