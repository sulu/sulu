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
    const date = new Date(2020, 10, 11, 16, 6, 57);

    const changeSpy = jest.fn();
    const fixedSchedule = shallow(
        <FixedSchedule index={0} onChange={changeSpy} value={{end: '2013-09-16T11:36:26', type: 'fixed'}} />
    );

    fixedSchedule.find('Field[label="sulu_admin.start"] DatePicker').simulate('change', date);
    expect(changeSpy).toBeCalledWith(0, {end: '2013-09-16T11:36:26', start: '2020-11-11T16:06:57', type: 'fixed'});
});

test('Change end date', () => {
    const date = new Date(2020, 10, 11, 16, 6, 57);

    const changeSpy = jest.fn();
    const fixedSchedule = shallow(
        <FixedSchedule index={2} onChange={changeSpy} value={{start: '2013-09-16T11:36:26', type: 'fixed'}} />
    );

    fixedSchedule.find('Field[label="sulu_admin.end"] DatePicker').simulate('change', date);
    expect(changeSpy).toBeCalledWith(2, {end: '2020-11-11T16:06:57', start: '2013-09-16T11:36:26', type: 'fixed'});
});
