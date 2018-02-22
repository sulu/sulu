// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import TextArea from '../TextArea';

test('TextArea should render', () => {
    expect(render(<TextArea value="My value" onChange={jest.fn()} onFinish={jest.fn()} />)).toMatchSnapshot();
});

test('TextArea should render with error', () => {
    const error = {
        keyword: 'required',
        parameters: {},
    };
    expect(render(<TextArea value="My value" error={error} onChange={jest.fn()} onFinish={jest.fn()} />))
        .toMatchSnapshot();
});

test('TextArea should render with placeholder', () => {
    expect(render(<TextArea placeholder="My placeholder" value="My value" onChange={jest.fn()} onFinish={jest.fn()} />))
        .toMatchSnapshot();
});

test('TextArea should render with value', () => {
    expect(render(<TextArea value="My value" onChange={jest.fn()} onFinish={jest.fn()} />)).toMatchSnapshot();
});

test('TextArea should render null value as empty string', () => {
    expect(render(<TextArea value={null} onChange={jest.fn()} onFinish={jest.fn()} />)).toMatchSnapshot();
});

test('TextArea should call onFinish when it loses focus', () => {
    const finishSpy = jest.fn();
    const textArea = shallow(<TextArea onChange={jest.fn()} onFinish={finishSpy} value="" />);

    textArea.find('textarea').simulate('blur');
    expect(finishSpy).toBeCalledWith();
});

test('TextArea should call the callback when the TextArea changes', () => {
    const changeSpy = jest.fn();
    const textArea = shallow(<TextArea value="My value" onChange={changeSpy} onFinish={jest.fn()} />);
    textArea.find('textarea').simulate('change', {currentTarget: {value: 'my-value'}});
    expect(changeSpy).toHaveBeenCalledWith('my-value');
});
