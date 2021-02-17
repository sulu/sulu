// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import Iban from '../Iban';

test('Iban should render', () => {
    const onChange = jest.fn();
    expect(render(<Iban onChange={onChange} value={null} />)).toMatchSnapshot();
});

test('Iban should render with placeholder', () => {
    expect(render(<Iban onChange={jest.fn()} placeholder="My placeholder" value={null} />)).toMatchSnapshot();
});

test('Iban should render with value', () => {
    const iban = mount(<Iban onChange={jest.fn()} value="AT61 1904 3002 3457 3201" />);
    expect(iban.render()).toMatchSnapshot();
});

test('Iban should render when disabled', () => {
    expect(render(<Iban disabled={true} onChange={jest.fn()} value="AT61 1904 3002 3457 3201" />)).toMatchSnapshot();
});

test('Iban should render error', () => {
    expect(render(<Iban onChange={jest.fn()} valid={false} value={null} />)).toMatchSnapshot();
});

test('Iban should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const iban = mount(<Iban onBlur={onBlur} onChange={onChange} value={null} />);

    // provide invalid value
    iban.find('Input').instance().props.onChange('xxx', {target: {value: 'xxx'}});
    iban.find('Input').instance().props.onBlur();
    iban.update();
    expect(onChange).toBeCalledWith('xxx');
    expect(onBlur).toBeCalled();

    // provide one more invalid value
    iban.find('Input').instance().props.onChange('abc', {target: {value: 'abc'}});
    iban.find('Input').instance().props.onBlur();
    iban.update();
    expect(onChange).toBeCalledWith('abc');
    expect(onBlur).toBeCalled();

    // now add a valid value
    iban.find('Input').instance().props.onChange('AT611904300234573201', {target: {value: 'AT611904300234573201'}});
    iban.find('Input').instance().props.onBlur();
    iban.update();
    expect(onChange).toBeCalledWith('AT611904300234573201');
    expect(onBlur).toBeCalled();

    expect(onBlur).toHaveBeenCalledTimes(3);
});
