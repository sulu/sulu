// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import moment from 'moment-timezone';
import userEvent from '@testing-library/user-event';
import DatePicker from '../DatePicker';

beforeEach(() => {
    const constantDate = new Date(Date.UTC(2017, 3, 15, 6, 32, 20));
    (Date: any).now = jest.fn().mockReturnValue(constantDate);

    moment.tz.setDefault('Europe/Vienna');
});

test('DatePicker should render', () => {
    const onChange = jest.fn();
    const {baseElement} = render(<DatePicker className="date-picker" onChange={onChange} value={undefined} />);

    expect(baseElement).toMatchSnapshot();
});

test('DatePicker should render date picker with time picker', () => {
    const onChange = jest.fn();
    const options = {
        timeFormat: true,
    };
    const {baseElement} = render(<DatePicker onChange={onChange} options={options} value={null} />);

    expect(baseElement).toMatchSnapshot();
});

test('DatePicker should render null value as empty string', () => {
    const onChange = jest.fn();
    const {container} = render(<DatePicker onChange={onChange} value={null} />);

    expect(container).toMatchSnapshot();
});

test('DatePicker should render date format only with month', () => {
    const onChange = jest.fn();
    const options = {
        dateFormat: 'MMMM',
    };
    const {container} = render(<DatePicker onChange={onChange} options={options} value={null} />);

    expect(container).toMatchSnapshot();
});

test('DatePicker should render date format only with year', () => {
    const onChange = jest.fn();
    const options = {
        dateFormat: 'YYYY',
    };
    const {container} = render(<DatePicker onChange={onChange} options={options} value={null} />);

    expect(container).toMatchSnapshot();
});

test('DatePicker should render error', () => {
    const onChange = jest.fn();
    const {container} = render(<DatePicker onChange={onChange} valid={false} value={null} />);

    expect(container).toMatchSnapshot();
});

test('DatePicker should show disabled Input when disabled', () => {
    const onChange = jest.fn();
    const value = new Date('2017-05-23');
    render(<DatePicker disabled={true} onChange={onChange} value={value} />);

    const input = screen.queryByDisplayValue('05/23/2017');
    expect(input).toBeDisabled();
});

test('DatePicker should pass input to inputRef prop', () => {
    const inputRefSpy = jest.fn();
    const value = new Date('2017-05-23');
    render(<DatePicker disabled={true} inputRef={inputRefSpy} onChange={jest.fn()} value={value} />);

    const input = screen.queryByDisplayValue('05/23/2017');
    expect(inputRefSpy).toBeCalledWith(input);
});

test('DatePicker should open overlay on icon-click', async() => {
    const onChange = jest.fn();
    const {baseElement} = render(<DatePicker onChange={onChange} value={undefined} />);

    const overlay = baseElement.querySelector('.rdt');
    expect(overlay).toBeInTheDocument();
    expect(overlay).not.toHaveClass('rdtOpen');

    const icon = screen.queryByLabelText('su-calendar');
    await userEvent.click(icon);

    expect(overlay).toHaveClass('rdtOpen');
});

test('DatePicker should not open overlay on icon-click when disabled', async() => {
    const onChange = jest.fn();
    const {baseElement} = render(<DatePicker disabled={true} onChange={onChange} value={undefined} />);

    const overlay = baseElement.querySelector('.rdt');
    expect(overlay).toBeInTheDocument();
    expect(overlay).not.toHaveClass('rdtOpen');

    const icon = screen.queryByLabelText('su-calendar');
    await userEvent.click(icon);

    expect(overlay).not.toHaveClass('rdtOpen');
});

test('DatePicker should render with placeholder', () => {
    const onChange = jest.fn();
    render(<DatePicker onChange={onChange} placeholder="My placeholder" value={null} />);

    const input = screen.queryByPlaceholderText('My placeholder');
    expect(input).toBeInTheDocument();
});

test('DatePicker should render with value', () => {
    const onChange = jest.fn();
    const value = new Date('2017-05-23');
    render(<DatePicker onChange={onChange} value={value} />);

    const input = screen.queryByDisplayValue('05/23/2017');
    expect(input).toBeInTheDocument();
});

test('DatePicker should try to guess incomplete value using format on blur.', async() => {
    const onChange = jest.fn();
    const options = {
        dateFormat: false,
        timeFormat: 'HH:mm',
    };
    render(<DatePicker onChange={onChange} options={options} placeholder="My placeholder" value={null} />);

    const input = screen.queryByPlaceholderText('My placeholder');
    await userEvent.type(input, '9');
    await userEvent.tab(); // tab away from input

    expect(onChange).toBeCalledWith(expect.any(Date));
    const newValue = onChange.mock.calls[0][0];

    const expectedMoment = moment('09:00', options.timeFormat);
    expect(expectedMoment.isValid()).toBe(true);

    const expectedDate = expectedMoment.toDate();
    expect(newValue && newValue.getHours()).toBe(expectedDate.getHours());
    expect(newValue && newValue.getMinutes()).toBe(expectedDate.getMinutes());
});

test('DatePicker should render error when invalid value is set', async() => {
    const onChange = jest.fn();
    const options = {
        dateFormat: 'YYYY',
    };
    render(<DatePicker onChange={onChange} options={options} placeholder="My placeholder" value={null} />);

    // check if showError is set correctly
    const input = screen.queryByPlaceholderText('My placeholder');
    await userEvent.type(input, 'xxx');
    await userEvent.tab(); // tab away from input

    expect(input.parentElement).toHaveClass('error');

    // now add a valid value
    await userEvent.clear(input);
    await userEvent.type(input, '2018');
    await userEvent.tab(); // tab away from input

    expect(input.parentElement).not.toHaveClass('error');
});

test('DatePicker should set class correctly when overlay was opened/closed', async() => {
    const onChange = jest.fn();
    const {baseElement} = render(<DatePicker onChange={onChange} placeholder="My placeholder" value={null} />);

    // overlay should be closed
    const overlay = baseElement.querySelector('.rdt');
    expect(overlay).toBeInTheDocument();
    expect(overlay).not.toHaveClass('rdtOpen');

    // open dialog and check if class is set
    const icon = screen.queryByLabelText('su-calendar');
    await userEvent.click(icon);
    expect(overlay).toHaveClass('rdtOpen');

    // choose a date and check if class was removed again
    const dateCell = screen.queryAllByText('26');
    await userEvent.click(dateCell[0]);
    expect(overlay).not.toHaveClass('rdtOpen');

    // check if value is in input
    const input = screen.queryByPlaceholderText('My placeholder');
    expect(input).toHaveValue('03/26/2017');
});
