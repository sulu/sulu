// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import log from 'loglevel';
import Icon from '../Icon';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

test('Icon should render', () => {
    expect(render(<Icon name="su-save" />)).toMatchSnapshot();
});

test('Icon should not render with invalid icon', () => {
    const icon = mount(<Icon name="xxx" />);
    expect(icon.render()).toMatchSnapshot();
    expect(log.warn).toHaveBeenCalled();
});

test('Icon should not render with empty string', () => {
    const icon = mount(<Icon name="" />);
    expect(icon.render()).toMatchSnapshot();
    expect(log.warn).toHaveBeenCalled();
});

test('Icon should render with class names', () => {
    expect(render(<Icon className="test" name="su-pen" />)).toMatchSnapshot();
});

test('Icon should render with onClick handler, role and tabindex', () => {
    const onClickSpy = jest.fn();
    expect(render(<Icon className="test" name="su-save" onClick={onClickSpy} />)).toMatchSnapshot();
});

test('Icon should call the callback on click', () => {
    const onClick = jest.fn();
    const stopPropagation = jest.fn();
    const icon = shallow(<Icon className="test" name="su-pen" onClick={onClick} />);
    icon.simulate('click', { stopPropagation });
    expect(onClick).toBeCalled();
    expect(stopPropagation).toBeCalled();
});
