// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import Bic from '../Bic';

test('Bic should render', () => {
    const onChange = jest.fn();
    expect(render(<Bic onChange={onChange} value={null} />)).toMatchSnapshot();
});

test('Bic should render with placeholder', () => {
    expect(render(<Bic onChange={jest.fn()} placeholder="My placeholder" value={null} />)).toMatchSnapshot();
});

test('Bic should render with value', () => {
    const bic = mount(<Bic onChange={jest.fn()} value="BBBBCCLLXXX" />);
    expect(bic.render()).toMatchSnapshot();
});

test('Bic should render when disabled', () => {
    expect(render(<Bic disabled={true} onChange={jest.fn()} value="BBBBCCLLXXX" />)).toMatchSnapshot();
});

test('Bic should render error', () => {
    expect(render(<Bic onChange={jest.fn()} valid={false} value={null} />)).toMatchSnapshot();
});

test('Bic should render error when invalid value is set', () => {
    const bic = mount(<Bic onChange={jest.fn()} value={null} />);

    // check if showError is set correctly
    bic.find('Input').instance().props.onChange('xxx', {target: {value: 'xxx'}});
    bic.find('Input').instance().props.onBlur();
    bic.update();
    expect(bic.instance().showError).toBe(true);

    // now add a valid value
    bic.find('Input').instance().props.onChange('BBBBCCLLXXX', {target: {value: 'BBBBCCLLXXX'}});
    bic.find('Input').instance().props.onBlur();
    bic.update();
    expect(bic.instance().showError).toBe(false);
});

test('Bic should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const bic = mount(<Bic onBlur={onBlur} onChange={onChange} value={null} />);

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
    bic.find('Input').instance().props.onChange('BBBBCCLLXXX', {target: {value: 'BBBBCCLLXXX'}});
    bic.find('Input').instance().props.onBlur();
    bic.update();
    expect(onChange).toBeCalledWith('BBBBCCLLXXX');
    expect(onBlur).toBeCalled();

    expect(onBlur).toHaveBeenCalledTimes(3);
});
