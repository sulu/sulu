// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import FixedSchedule from '../FixedSchedule';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render a FixedSchedule', () => {
    expect(render(<FixedSchedule index={0} onChange={jest.fn()} value={{type: 'fixed'}} />)).toMatchSnapshot();
});

test('Change start date', () => {
    const date = new Date();

    const changeSpy = jest.fn();
    const fixedSchedule = shallow(
        <FixedSchedule index={0} onChange={changeSpy} value={{end: date, type: 'fixed'}} />
    );

    fixedSchedule.find('Field[label="sulu_admin.start"] DatePicker').simulate('change', date);
    expect(changeSpy).toBeCalledWith(0, {end: date, start: date, type: 'fixed'});
});

test('Change end date', () => {
    const date = new Date();

    const changeSpy = jest.fn();
    const fixedSchedule = shallow(
        <FixedSchedule index={2} onChange={changeSpy} value={{start: date, type: 'fixed'}} />
    );

    fixedSchedule.find('Field[label="sulu_admin.end"] DatePicker').simulate('change', date);
    expect(changeSpy).toBeCalledWith(2, {end: date, start: date, type: 'fixed'});
});
