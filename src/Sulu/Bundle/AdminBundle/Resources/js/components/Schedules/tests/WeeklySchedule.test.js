// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import WeeklySchedule from '../WeeklySchedule';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render a WeeklySchedule', () => {
    expect(
        render(<WeeklySchedule index={0} onChange={jest.fn()} value={{days: ['monday', 'friday'], type: 'weekly'}} />)
    ).toMatchSnapshot();
});

test('Change start date', () => {
    const date = new Date(0, 0, 0, 14, 48, 7);

    const changeSpy = jest.fn();
    const fixedSchedule = shallow(
        <WeeklySchedule index={0} onChange={changeSpy} value={{end: '12:00:00', type: 'weekly'}} />
    );

    fixedSchedule.find('Field[label="sulu_admin.start"] DatePicker').simulate('change', date);
    expect(changeSpy).toBeCalledWith(0, {end: '12:00:00', start: '14:48:07', type: 'weekly'});
});

test('Change end date', () => {
    const date = new Date(0, 0, 0, 16, 17, 21);

    const changeSpy = jest.fn();
    const fixedSchedule = shallow(
        <WeeklySchedule index={2} onChange={changeSpy} value={{start: '14:48:07', type: 'weekly'}} />
    );

    fixedSchedule.find('Field[label="sulu_admin.end"] DatePicker').simulate('change', date);
    expect(changeSpy).toBeCalledWith(2, {end: '16:17:21', start: '14:48:07', type: 'weekly'});
});

test('Change selected days', () => {
    const changeSpy = jest.fn();
    const fixedSchedule = shallow(
        <WeeklySchedule index={2} onChange={changeSpy} value={{days: ['monday'], type: 'weekly'}} />
    );

    fixedSchedule.find('Field[label="sulu_admin.weekdays"] MultiSelect').simulate('change', ['wednesday', 'thursday']);
    expect(changeSpy).toBeCalledWith(2, {days: ['wednesday', 'thursday'], type: 'weekly'});
});
