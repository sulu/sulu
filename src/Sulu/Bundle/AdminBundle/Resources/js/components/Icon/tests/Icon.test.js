// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

test('Icon should call the callback on click', async() => {
    const onClick = jest.fn();
    render(<Icon className="test" name="su-pen" onClick={onClick} />);

    const icon = screen.queryByLabelText('su-pen');
    await userEvent.click(icon);

    expect(onClick).toBeCalled();
});

test('Icon should call the callback on when space is pressed', async() => {
    const onClick = jest.fn();
    render(<Icon className="test" name="su-pen" onClick={onClick} />);

    const icon = screen.queryByLabelText('su-pen');
    await userEvent.type(icon, '[Space]');

    expect(onClick).toBeCalled();
});

test('Icon should call the callback on when enter is pressed', async() => {
    const onClick = jest.fn();
    render(<Icon className="test" name="su-pen" onClick={onClick} />);

    const icon = screen.queryByLabelText('su-pen');
    await userEvent.type(icon, '[Enter]');

    expect(onClick).toBeCalled();
});
