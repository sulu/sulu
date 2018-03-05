// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Assignment from '../../fields/Assignment';

test('Should pass props correctly to component', () => {
    const changeSpy = jest.fn();
    const value = [1, 6, 8];
    const assignment = shallow(<Assignment onChange={changeSpy} value={value} />);

    expect(assignment.find('Assignment').props()).toEqual(expect.objectContaining({
        onChange: changeSpy,
        value,
    }));
});

test('Should pass empty array if value is not given', () => {
    const changeSpy = jest.fn();
    const assignment = shallow(<Assignment onChange={changeSpy} value={undefined} />);

    expect(assignment.find('Assignment').props()).toEqual(expect.objectContaining({
        onChange: changeSpy,
        value: [],
    }));
});
