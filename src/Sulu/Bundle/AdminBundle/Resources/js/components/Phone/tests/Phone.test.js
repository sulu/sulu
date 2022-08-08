// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Phone from '../Phone';

test('Phone should render', () => {
    const onChange = jest.fn();
    const {container} = render(<Phone onChange={onChange} value={null} />);
    expect(container).toMatchSnapshot();
});

test('Phone should render with placeholder', () => {
    const onChange = jest.fn();
    const {container} = render(<Phone onChange={onChange} placeholder="My placeholder" value={null} />);
    expect(container).toMatchSnapshot();
});

test('Phone should render with value', () => {
    const onChange = jest.fn();
    const value = 'test@test.com';
    const {container} = render(<Phone onChange={onChange} value={value} />);
    expect(container).toMatchSnapshot();
});

test('Phone should render null value as empty string', () => {
    const onChange = jest.fn();
    const {container} = render(<Phone onChange={onChange} value={null} />);
    expect(container).toMatchSnapshot();
});

test('Phone should render error', () => {
    const onChange = jest.fn();
    const {container} = render(<Phone onChange={onChange} valid={false} value={null} />);
    expect(container).toMatchSnapshot();
});

test('Phone should render when disabled', () => {
    const onChange = jest.fn();
    const {container} = render(<Phone disabled={true} onChange={onChange} valid={false} value="‚+43245" />);
    expect(container).toMatchSnapshot();
});

test('Phone should trigger callbacks correctly', async() => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(<Phone onBlur={onBlur} onChange={onChange} value={null} />);

    const input = screen.queryByRole('textbox');

    await userEvent.change(input, {target: {value: '+123'}});
    await userEvent.blur(input);

    expect(onChange).toBeCalledWith('+123', expect.anything());
    expect(onChange).toHaveBeenCalledTimes(1);
    expect(onBlur).toBeCalled();
    expect(onBlur).toHaveBeenCalledTimes(1);
});

test('Phone should not set onIconClick when value is not set', async() => {
    const redirectSpy = jest.fn();
    delete window.location;
    window.location = {assign: redirectSpy};

    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(<Phone onBlur={onBlur} onChange={onChange} value={null} />);

    const icon = screen.queryByLabelText('su-phone');
    await userEvent.click(icon);

    expect(redirectSpy).not.toHaveBeenCalled();
});

test('Phone should set onIconClick when value is set', async() => {
    const redirectSpy = jest.fn();
    delete window.location;
    window.location = {assign: redirectSpy};

    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(<Phone onBlur={onBlur} onChange={onChange} value="+123" />);

    const icon = screen.queryByLabelText('su-phone');
    await userEvent.click(icon);

    expect(redirectSpy).toHaveBeenCalled();
});

test('Phone should set onIconClick when value is valid and window should be opened', async() => {
    const redirectSpy = jest.fn();
    delete window.location;
    window.location = {assign: redirectSpy};

    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(<Phone onBlur={onBlur} onChange={onChange} value="+123" />);

    const icon = screen.queryByLabelText('su-phone');
    await userEvent.click(icon);

    expect(redirectSpy).toHaveBeenLastCalledWith('tel:+123');
});
