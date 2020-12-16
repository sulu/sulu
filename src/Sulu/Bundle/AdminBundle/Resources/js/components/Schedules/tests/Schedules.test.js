// @flow
import React from 'react';
import {mount, shallow, render} from 'enzyme';
import Schedules from '../Schedules';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render Schedules', () => {
    const value = [
        {type: 'fixed'},
        {type: 'weekly'},
    ];

    expect(render(<Schedules onChange={jest.fn()} value={value} />)).toMatchSnapshot();
});

test('Disable blocks if schedules is disabled', () => {
    const schedules = shallow(<Schedules disabled={true} onChange={jest.fn()} value={[]} />);

    expect(schedules.find('BlockCollection').prop('disabled')).toEqual(true);
});

test('Change value when BlockCollection adds a new block', () => {
    const value = [];
    const changeSpy = jest.fn();
    const schedules = mount(<Schedules onChange={changeSpy} value={value} />);

    schedules.find('BlockCollection Button[icon="su-plus"] button').simulate('click');
    expect(changeSpy).toBeCalledWith([{type: 'fixed'}]);
});

test('Change value when a FixedSchedule in the BlockCollection changes', () => {
    const value = [{type: 'weekly'}, {type: 'fixed'}];
    const changeSpy = jest.fn();
    const schedules = mount(<Schedules onChange={changeSpy} value={value} />);

    const date = new Date(2020, 10, 11, 16, 16, 30);
    schedules.find('FixedSchedule Field[label="sulu_admin.start"] DatePicker').prop('onChange')(date);

    expect(changeSpy).toBeCalledWith([{type: 'weekly'}, {type: 'fixed', start: '2020-11-11T16:16:30'}]);
});

test('Change value when a WeeklySchedule in the BlockCollection changes', () => {
    const value = [{type: 'weekly'}, {type: 'fixed'}];
    const changeSpy = jest.fn();
    const schedules = mount(<Schedules onChange={changeSpy} value={value} />);

    const date = new Date(0, 0, 0, 18, 39, 15);
    schedules.find('WeeklySchedule Field[label="sulu_admin.start"] DatePicker').prop('onChange')(date);

    expect(changeSpy).toBeCalledWith([{type: 'weekly', start: '18:39:15'}, {type: 'fixed'}]);
});
