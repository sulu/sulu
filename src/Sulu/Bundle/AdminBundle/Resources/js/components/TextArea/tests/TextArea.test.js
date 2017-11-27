// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import TextArea from '../TextArea';

test('TextArea should render', () => {
    const onChange = jest.fn();
    expect(render(<TextArea value="My value" onChange={onChange} />)).toMatchSnapshot();
});

test('TextArea should render with placeholder', () => {
    const onChange = jest.fn();
    expect(render(<TextArea placeholder="My placeholder" value="My value" onChange={onChange} />)).toMatchSnapshot();
});

test('TextArea should render with value', () => {
    const onChange = jest.fn();
    expect(render(<TextArea value="My value" onChange={onChange} />)).toMatchSnapshot();
});

test('TextArea should render null value as empty string', () => {
    const onChange = jest.fn();
    expect(render(<TextArea value={null} onChange={onChange} />)).toMatchSnapshot();
});

test('TextArea should call the callback when the TextArea changes', () => {
    const onChange = jest.fn();
    const textArea = shallow(<TextArea value="My value" onChange={onChange} />);
    textArea.find('textarea').simulate('change', {currentTarget: {value: 'my-value'}});
    expect(onChange).toHaveBeenCalledWith('my-value');
});
