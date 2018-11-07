// @flow
import React from 'react';
import {mount} from 'enzyme';
import moment from 'moment-timezone';
import pretty from 'pretty';
import DatePicker from '../DatePicker';

beforeEach(() => {
    const constantDate = new Date(Date.UTC(2017, 3, 15, 6, 32, 20));
    (Date: any).now = jest.fn().mockReturnValue(constantDate);

    moment.tz.setDefault('Europe/Vienna');
});

test('DatePicker should render', () => {
    const onChange = jest.fn();
    const datePicker = mount(<DatePicker onChange={onChange} value={null} />);

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should open overlay on icon-click', () => {
    const onChange = jest.fn();
    const datePicker = mount(<DatePicker onChange={onChange} value={null} />);
    datePicker.find('Icon').simulate('click');

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should not open overlay on icon-click when disabled', () => {
    const onChange = jest.fn();
    const datePicker = mount(<DatePicker disabled={true} onChange={onChange} value={null} />);
    datePicker.find('Icon').simulate('click');

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should render with placeholder', () => {
    const onChange = jest.fn();
    const datePicker = mount(<DatePicker onChange={onChange} placeholder="My placeholder" value={null} />);

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should render with value', () => {
    const onChange = jest.fn();
    const value = new Date('2017-05-23');
    const datePicker = mount(<DatePicker onChange={onChange} value={value} />);

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should render null value as empty string', () => {
    const onChange = jest.fn();
    const datePicker = mount(<DatePicker onChange={onChange} value={null} />);

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should render date format only with month', () => {
    const onChange = jest.fn();
    const options = {
        dateFormat: 'MMMM',
    };
    const datePicker = mount(<DatePicker onChange={onChange} options={options} value={null} />);

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should render date format only with year', () => {
    const onChange = jest.fn();
    const options = {
        dateFormat: 'YYYY',
    };
    const datePicker = mount(<DatePicker onChange={onChange} options={options} value={null} />);

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should render date picker with time picker', () => {
    const onChange = jest.fn();
    const options = {
        timeFormat: true,
    };
    const datePicker = mount(<DatePicker onChange={onChange} options={options} value={null} />);

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should render when disabled', () => {
    const onChange = jest.fn();
    const value = new Date('2017-05-23');
    const datePicker = mount(<DatePicker disabled={true} onChange={onChange} value={value} />);

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should render error', () => {
    const onChange = jest.fn();
    const datePicker = mount(<DatePicker onChange={onChange} valid={false} value={null} />);

    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    datePicker.unmount();
});

test('DatePicker should render error when invalid value is set', () => {
    const onChange = jest.fn();
    const options = {
        dateFormat: 'YYYY',
    };
    const datePicker = mount(<DatePicker onChange={onChange} options={options} value={null} />);

    // check if showError is set correctly
    datePicker.find('Input').instance().props.onChange('xxx', {target: {value: 'xxx'}});
    datePicker.find('Input').instance().props.onBlur();
    datePicker.update();
    expect(datePicker.instance().showError).toBe(true);

    // snapshot
    expect(datePicker.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();

    // now add a valid value
    datePicker.find('Input').instance().props.onChange('2018', {target: {value: '2018'}});
    datePicker.find('Input').instance().props.onBlur();
    datePicker.update();
    expect(datePicker.instance().showError).toBe(false);

    datePicker.unmount();
});

test('DatePicker should set class correctly when overlay was opened/closed', () => {
    const onChange = jest.fn();
    const input = mount(<DatePicker onChange={onChange} value={null} />);

    // overlay should be closed
    expect(input.find('div.rdt').hasClass('rdtOpen')).toBe(false);

    // open dialog and check if class is set
    input.find('Input Icon span').simulate('click');
    expect(input.find('div.rdt').hasClass('rdtOpen')).toBe(true);

    // choose a date and check if class was removed again
    input.find('.rdtPicker tbody tr td').first().simulate('click');
    expect(input.find('div.rdt').hasClass('rdtOpen')).toBe(false);

    // check if value is in input
    expect(input.find('Input').prop('value')).toBe('03/26/2017');
});
