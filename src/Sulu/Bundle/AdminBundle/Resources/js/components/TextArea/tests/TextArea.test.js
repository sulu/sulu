// @flow
import React from 'react';
import {render} from '@testing-library/react';
import TextArea from '../TextArea';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('TextArea should render', () => {
    const {container} = render(<TextArea onChange={jest.fn()} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render with error', () => {
    const {container} = render(<TextArea onChange={jest.fn()} valid={false} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render with placeholder', () => {
    const {container} = render(<TextArea onChange={jest.fn()} placeholder="My placeholder" value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render with value', () => {
    const {container} = render(<TextArea onChange={jest.fn()} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render when disabled', () => {
    const {container} = render(<TextArea disabled={true} onChange={jest.fn()} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render null value as empty string', () => {
    const {container} = render(<TextArea onChange={jest.fn()} value={null} />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render with value and character counter', () => {
    const {container} = render(<TextArea maxCharacters={10} onChange={jest.fn()} value="My value" />);
    expect(container).toMatchSnapshot();
});

// test('TextArea should call onBlur when it loses focus', () => {
//     const blurSpy = jest.fn();
//     const textArea = shallow(<TextArea onBlur={blurSpy} onChange={jest.fn()} value="" />);

//     textArea.find('textarea').simulate('blur');
//     expect(blurSpy).toBeCalledWith();
// });

// test('TextArea should call onChange when the TextArea changes', () => {
//     const changeSpy = jest.fn();
//     const textArea = shallow(<TextArea onChange={changeSpy} value="My value" />);
//     textArea.find('textarea').simulate('change', {currentTarget: {value: 'my-value'}});
//     expect(changeSpy).toHaveBeenCalledWith('my-value');
// });

// test('TextArea should call onChange with undefined when the TextArea changes to empty', () => {
//     const changeSpy = jest.fn();
//     const textArea = shallow(<TextArea onChange={changeSpy} value="My value" />);
//     textArea.find('textarea').simulate('change', {currentTarget: {value: ''}});
//     expect(changeSpy).toHaveBeenCalledWith(undefined);
// });
