// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import pretty from 'pretty';
import Assignment from '../Assignment';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    const body = document.body;

    if (body) {
        body.innerHTML = '';
    }
});

test('Show with default plus icon', () => {
    expect(render(<Assignment />)).toMatchSnapshot();
});

test('Show with passed icon', () => {
    expect(render(<Assignment icon="su-document" />)).toMatchSnapshot();
});

test('Should open an overlay', () => {
    const assignment = mount(<Assignment />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const body = document.body;
    expect(pretty(body ? body.innerHTML : null)).toMatchSnapshot();
});

test('Should close an overlay using the close button', () => {
    const assignment = mount(<Assignment />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const closeButton = document.querySelector('.su-x');
    if (closeButton) {
        closeButton.click();
    }

    assignment.update();
    expect(assignment.find('DatagridOverlay').prop('open')).toEqual(false);
});

test('Should close an overlay using the confirm button', () => {
    const assignment = mount(<Assignment />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const confirmButton = document.querySelector('button.primary');
    if (confirmButton) {
        confirmButton.click();
    }

    assignment.update();
    expect(assignment.find('DatagridOverlay').prop('open')).toEqual(false);
});
