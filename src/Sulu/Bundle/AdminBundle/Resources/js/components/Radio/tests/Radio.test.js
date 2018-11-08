// @flow
import {shallow, render} from 'enzyme';
import React from 'react';
import Radio from '../Radio';

test('The component should render in light skin', () => {
    const radio = render(<Radio skin="light" />);
    expect(radio).toMatchSnapshot();
});

test('The component should render in dark skin', () => {
    const radio = render(<Radio skin="dark" />);
    expect(radio).toMatchSnapshot();
});

test('The component should render in disabled state', () => {
    const radio = render(<Radio disabled={true} />);
    expect(radio).toMatchSnapshot();
});

test('The component pass the props correctly to the generic checkbox', () => {
    const checkbox = shallow(
        <Radio
            checked={true}
            disabled={true}
            name="my-name"
            value="my-value"
        >
            My label
        </Radio>
    );
    const switchComponent = checkbox.find('Switch');
    expect(switchComponent.props().value).toBe('my-value');
    expect(switchComponent.props().name).toBe('my-name');
    expect(switchComponent.props().checked).toBe(true);
    expect(switchComponent.props().disabled).toBe(true);
    expect(switchComponent.props().children).toBe('My label');
});

test('The component pass the the value to the change callback', () => {
    const onChange = jest.fn();
    const checkbox = shallow(
        <Radio onChange={onChange} value="my-value">My label</Radio>
    );
    const switchComponent = checkbox.find('Switch');
    switchComponent.props().onChange(true, 'my-value');
    expect(onChange).toHaveBeenCalledWith('my-value');
});
