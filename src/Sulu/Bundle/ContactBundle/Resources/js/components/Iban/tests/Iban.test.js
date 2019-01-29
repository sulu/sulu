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
    const bic = mount(<Iban onChange={jest.fn()} value="AT61 1904 3002 3457 3201" />);
    expect(bic.render()).toMatchSnapshot();
});

test('Iban should render when disabled', () => {
    expect(render(<Iban disabled={true} onChange={jest.fn()} value="AT61 1904 3002 3457 3201" />)).toMatchSnapshot();
});

test('Iban should render error', () => {
    expect(render(<Iban onChange={jest.fn()} valid={false} value={null} />)).toMatchSnapshot();
});

test('Iban should render error when invalid value is set', () => {
    const bic = mount(<Iban onChange={jest.fn()} value={null} />);

    // check if showError is set correctly
    bic.find('Input').instance().props.onChange('xxx', {target: {value: 'xxx'}});
    bic.find('Input').instance().props.onBlur();
    bic.update();
    expect(bic.instance().showError).toBe(true);

    // now add a valid value
    bic.find('Input').instance().props.onChange('AT611904300234573201', {target: {value: 'AT611904300234573201'}});
    bic.find('Input').instance().props.onBlur();
    bic.update();
    expect(bic.instance().showError).toBe(false);
});

test('Iban should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const bic = mount(<Iban onBlur={onBlur} onChange={onChange} value={null} />);

    // provide invalid value
    bic.find('Input').instance().props.onChange('xxx', {target: {value: 'xxx'}});
    bic.find('Input').instance().props.onBlur();
    bic.update();
    expect(onChange).toBeCalledWith(undefined);
    expect(onBlur).toBeCalled();

    // provide one more invalid value
    bic.find('Input').instance().props.onChange('abc', {target: {value: 'abc'}});
    bic.find('Input').instance().props.onBlur();
    bic.update();
    expect(onChange).toBeCalledWith(undefined);
    expect(onBlur).toBeCalled();

    // now add a valid value
    bic.find('Input').instance().props.onChange('AT611904300234573201', {target: {value: 'AT611904300234573201'}});
    bic.find('Input').instance().props.onBlur();
    bic.update();
    expect(onChange).toBeCalledWith('AT611904300234573201');
    expect(onBlur).toBeCalled();

    expect(onBlur).toHaveBeenCalledTimes(3);
});
