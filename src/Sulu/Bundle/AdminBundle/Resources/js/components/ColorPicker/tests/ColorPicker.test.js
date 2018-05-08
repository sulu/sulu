// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import pretty from 'pretty';
import ColorPicker from '../ColorPicker';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('ColorPicker should render', () => {
    const onChange = jest.fn();
    expect(render(<ColorPicker value={null} onChange={onChange} />)).toMatchSnapshot();
});

test('ColorPicker should render with placeholder', () => {
    const onChange = jest.fn();
    expect(render(<ColorPicker value={null} placeholder="My placeholder" onChange={onChange} />)).toMatchSnapshot();
});

test('ColorPicker should render with value', () => {
    const onChange = jest.fn();
    const value = '#abc';
    expect(render(<ColorPicker value={value} onChange={onChange} />)).toMatchSnapshot();
});

test('ColorPicker should render null value as empty string', () => {
    const onChange = jest.fn();
    expect(render(<ColorPicker value={null} onChange={onChange} />)).toMatchSnapshot();
});

test('ColorPicker should render error', () => {
    const onChange = jest.fn();
    expect(render(<ColorPicker value={null} onChange={onChange} valid={false} />)).toMatchSnapshot();
});

test('ColorPicker should render error when invalid value is set', () => {
    const onChange = jest.fn();
    const colorPicker = mount(<ColorPicker value={null} onChange={onChange} />);

    // check if showError is set correctly
    colorPicker.find('Input').instance().props.onChange('xxx', {target: {value: 'xxx'}});
    colorPicker.find('Input').instance().props.onBlur();
    colorPicker.update();
    expect(colorPicker.instance().showError).toBe(true);

    expect(render(colorPicker)).toMatchSnapshot();

    // now add a valid value
    colorPicker.find('Input').instance().props.onChange('#ccc', {target: {value: '#ccc'}});
    colorPicker.find('Input').instance().props.onBlur();
    colorPicker.update();
    expect(colorPicker.instance().showError).toBe(false);

    expect(render(colorPicker)).toMatchSnapshot();
});

test('ColorPicker should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const colorPicker = mount(<ColorPicker value={null} onChange={onChange} onBlur={onBlur} />);

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

test('ColorPicker should render with open overlay', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const colorPicker = mount(<ColorPicker value={null} onChange={onChange} onBlur={onBlur} />);

    colorPicker.find('Icon').simulate('click');
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();
});

test('ColorPicker should call the correct callbacks when value from overlay was selected', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const colorPicker = mount(<ColorPicker value={null} onChange={onChange} onBlur={onBlur} />);

    colorPicker.find('Icon').simulate('click');
    colorPicker.find('ColorPicker').at(1).prop('onChangeComplete')({hex: '#123123'});

    expect(onChange).toBeCalledWith('#123123');
    expect(onBlur).toBeCalled();
});
