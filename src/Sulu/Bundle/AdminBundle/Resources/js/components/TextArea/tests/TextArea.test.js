// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import TextArea from '../TextArea';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('TextArea should render', () => {
    expect(render(<TextArea value="My value" onChange={jest.fn()} />)).toMatchSnapshot();
});

test('TextArea should render with error', () => {
    expect(render(<TextArea value="My value" valid={false} onChange={jest.fn()} />))
        .toMatchSnapshot();
});

test('TextArea should render with placeholder', () => {
    expect(render(<TextArea placeholder="My placeholder" value="My value" onChange={jest.fn()} />))
        .toMatchSnapshot();
});

test('TextArea should render with value', () => {
    expect(render(<TextArea value="My value" onChange={jest.fn()} />)).toMatchSnapshot();
});

test('TextArea should render null value as empty string', () => {
    expect(render(<TextArea value={null} onChange={jest.fn()} />)).toMatchSnapshot();
});

test('TextArea should render with value and character counter', () => {
    expect(render(<TextArea maxCharacters={10} onChange={jest.fn()} value="My value" />)).toMatchSnapshot();
});

test('TextArea should call onBlur when it loses focus', () => {
    const blurSpy = jest.fn();
    const textArea = shallow(<TextArea onChange={jest.fn()} onBlur={blurSpy} value="" />);

    textArea.find('textarea').simulate('blur');
    expect(blurSpy).toBeCalledWith();
});

test('TextArea should call the callback when the TextArea changes', () => {
    const changeSpy = jest.fn();
    const textArea = shallow(<TextArea value="My value" onChange={changeSpy} />);
    textArea.find('textarea').simulate('change', {currentTarget: {value: 'my-value'}});
    expect(changeSpy).toHaveBeenCalledWith('my-value');
});
