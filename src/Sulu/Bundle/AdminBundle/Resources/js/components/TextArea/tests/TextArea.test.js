// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import TextArea from '../TextArea';

test('TextArea should render', () => {
    expect(render(<TextArea onChange={jest.fn()} value="My value" />)).toMatchSnapshot();
});

test('TextArea should render with error', () => {
    expect(render(<TextArea onChange={jest.fn()} valid={false} value="My value" />))
        .toMatchSnapshot();
});

test('TextArea should render with placeholder', () => {
    expect(render(<TextArea onChange={jest.fn()} placeholder="My placeholder" value="My value" />))
        .toMatchSnapshot();
});

test('TextArea should render with value', () => {
    expect(render(<TextArea onChange={jest.fn()} value="My value" />)).toMatchSnapshot();
});

test('TextArea should render null value as empty string', () => {
    expect(render(<TextArea onChange={jest.fn()} value={null} />)).toMatchSnapshot();
});

test('TextArea should call onBlur when it loses focus', () => {
    const blurSpy = jest.fn();
    const textArea = shallow(<TextArea onBlur={blurSpy} onChange={jest.fn()} value="" />);

    textArea.find('textarea').simulate('blur');
    expect(blurSpy).toBeCalledWith();
});

test('TextArea should call the callback when the TextArea changes', () => {
    const changeSpy = jest.fn();
    const textArea = shallow(<TextArea onChange={changeSpy} value="My value" />);
    textArea.find('textarea').simulate('change', {currentTarget: {value: 'my-value'}});
    expect(changeSpy).toHaveBeenCalledWith('my-value');
});
