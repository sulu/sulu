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
    const date = new Date();

    const changeSpy = jest.fn();
    const fixedSchedule = shallow(
        <WeeklySchedule index={0} onChange={changeSpy} value={{end: date, type: 'weekly'}} />
    );

    fixedSchedule.find('Field[label="sulu_admin.start"] DatePicker').simulate('change', date);
    expect(changeSpy).toBeCalledWith(0, {end: date, start: date, type: 'weekly'});
});

test('Change end date', () => {
    const date = new Date();

    const changeSpy = jest.fn();
    const fixedSchedule = shallow(
        <WeeklySchedule index={2} onChange={changeSpy} value={{start: date, type: 'weekly'}} />
    );

    fixedSchedule.find('Field[label="sulu_admin.end"] DatePicker').simulate('change', date);
    expect(changeSpy).toBeCalledWith(2, {end: date, start: date, type: 'weekly'});
});

test('Change selected days', () => {
    const changeSpy = jest.fn();
    const fixedSchedule = shallow(
        <WeeklySchedule index={2} onChange={changeSpy} value={{days: ['monday'], type: 'weekly'}} />
    );

    fixedSchedule.find('Field[label="sulu_admin.weekdays"] MultiSelect').simulate('change', ['wednesday', 'thursday']);
    expect(changeSpy).toBeCalledWith(2, {days: ['wednesday', 'thursday'], type: 'weekly'});
});
