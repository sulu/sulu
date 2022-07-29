// @flow
import React from 'react';
import {mount} from 'enzyme';
import ColorPicker from '../ColorPicker';

test('ColorPicker should render', () => {
    const colorPicker = mount(<ColorPicker onChange={jest.fn()} placeholder="My placeholder" value="#abc" />);
    colorPicker.find('Icon').simulate('click');
    expect(colorPicker.render()).toMatchSnapshot();
});

test('ColorPicker should disable Input when disabled', () => {
    const colorPicker = mount(<ColorPicker disabled={true} onChange={jest.fn()} value={undefined} />);

    expect(colorPicker.find('Input').prop('onIconClick')).toEqual(undefined);
    expect(colorPicker.find('Input').prop('disabled')).toEqual(true);
});

test('ColorPicker should render error', () => {
    const colorPicker = mount(<ColorPicker onChange={jest.fn()} valid={false} value={null} />);
    expect(colorPicker.find('Input').prop('valid')).toEqual(false);
});

test('ColorPicker should show error when invalid value is set', () => {
    const onChange = jest.fn();
    const colorPicker = mount(<ColorPicker onChange={onChange} value={null} />);

    colorPicker.find('Input').instance().props.onChange('xxx', {target: {value: 'xxx'}});
    colorPicker.find('Input').instance().props.onBlur();
    colorPicker.update();
    expect(colorPicker.find('Input').prop('valid')).toEqual(false);

    colorPicker.find('Input').instance().props.onChange('#ccc', {target: {value: '#ccc'}});
    colorPicker.find('Input').instance().props.onBlur();
    colorPicker.update();
    expect(colorPicker.find('Input').prop('valid')).toBe(true);
});

test('ColorPicker should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const colorPicker = mount(<ColorPicker onBlur={onBlur} onChange={onChange} value={null} />);

    // provide invalid value
    colorPicker.find('Input').instance().props.onChange('xxx', {target: {value: 'xxx'}});
    colorPicker.find('Input').instance().props.onBlur();
    colorPicker.update();
    expect(onChange).toBeCalledWith(undefined);
    expect(onBlur).toBeCalled();

    // provide one more invalid value
    colorPicker.find('Input').instance().props.onChange('abc', {target: {value: 'abc'}});
    colorPicker.find('Input').instance().props.onBlur();
    colorPicker.update();
    expect(onChange).toBeCalledWith(undefined);
    expect(onBlur).toBeCalled();

    // now add a valid value
    colorPicker.find('Input').instance().props.onChange('#abcabc', {target: {value: '#abcabc'}});
    colorPicker.find('Input').instance().props.onBlur();
    colorPicker.update();
    expect(onChange).toBeCalledWith('#abcabc');
    expect(onBlur).toBeCalled();

    expect(onBlur).toHaveBeenCalledTimes(3);
});

test('ColorPicker should call the correct callbacks when value from overlay was selected', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const colorPicker = mount(<ColorPicker onBlur={onBlur} onChange={onChange} value={null} />);

    colorPicker.find('Icon').simulate('click');
    colorPicker.find('ColorPicker').at(1).prop('onChangeComplete')({hex: '#123123'});

    expect(onChange).toBeCalledWith('#123123');
    expect(onBlur).toBeCalled();
});
