// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Assignment from '../../fields/Assignment';

test('Should pass props correctly to component', () => {
    const changeSpy = jest.fn();
    const preSelectedIds = [1, 6, 8];
    const assignment = shallow(<Assignment onChange={changeSpy} value={preSelectedIds} />);

    expect(assignment.find('Assignment').props()).toEqual(expect.objectContaining({
        onChange: changeSpy,
        preSelectedIds,
    }));
});

test('Should pass empty array if value is not given', () => {
    const changeSpy = jest.fn();
    const assignment = shallow(<Assignment onChange={changeSpy} value={undefined} />);

    expect(assignment.find('Assignment').props()).toEqual(expect.objectContaining({
        onChange: changeSpy,
        preSelectedIds: [],
    }));
});
