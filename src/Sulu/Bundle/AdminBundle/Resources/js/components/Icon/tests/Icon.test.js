// @flow
import React from 'react';
import {createEvent, fireEvent, render, screen} from '@testing-library/react';
import log from 'loglevel';
import Icon from '../Icon';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

test('Icon should render', () => {
    const {container} = render(<Icon name="su-save" />);
    expect(container).toMatchSnapshot();
});

test('Icon should not render with invalid icon', () => {
    const {container} = render(<Icon name="xxx" />);
    expect(container).toMatchSnapshot();
    expect(log.warn).toHaveBeenCalled();
});

test('Icon should not render with empty string', () => {
    const {container} = render(<Icon name="" />);
    expect(container).toMatchSnapshot();
    expect(log.warn).toHaveBeenCalled();
});

test('Icon should render with class names', () => {
    const {container} = render(<Icon className="test" name="su-pen" />);
    expect(container).toMatchSnapshot();
});

test('Icon should render with onClick handler, role and tabindex', () => {
    const onClickSpy = jest.fn();
    const {container} = render(<Icon className="test" name="su-save" onClick={onClickSpy} />);
    expect(container).toMatchSnapshot();
});

test('Icon should call the callback on click', () => {
    const onClick = jest.fn();
    const stopPropagation = jest.fn();
    render(<Icon className="test" name="su-pen" onClick={onClick} />);
    const icon = screen.queryByLabelText('su-pen');

    const clickEvent = createEvent.click(icon, {stopPropagation});
    clickEvent.stopPropagation = stopPropagation;
    fireEvent(icon, clickEvent);
    console.log(clickEvent);
    expect(clickEvent.stopPropagation).toBeCalled();
    expect(onClick).toBeCalled();
});

// test('Icon should call the callback on when space is pressed', () => {
//     const onClick = jest.fn();
//     const stopPropagation = jest.fn();
//     const icon = shallow(<Icon className="test" name="su-pen" onClick={onClick} />);
//     icon.simulate('keypress', {key: ' ', stopPropagation});
//     expect(onClick).toBeCalled();
//     expect(stopPropagation).toBeCalled();
// });

// test('Icon should call the callback on when enter is pressed', () => {
//     const onClick = jest.fn();
//     const stopPropagation = jest.fn();
//     const icon = shallow(<Icon className="test" name="su-pen" onClick={onClick} />);
//     icon.simulate('keypress', {key: 'Enter', stopPropagation});
//     expect(onClick).toBeCalled();
//     expect(stopPropagation).toBeCalled();
// });
