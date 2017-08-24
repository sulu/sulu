/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow, render} from 'enzyme';
import React from 'react';
import Checkbox from '../Checkbox';

test('The component should render in light skin', () => {
    const checkbox = render(<Checkbox skin="light" />);
    expect(checkbox).toMatchSnapshot();
});

test('The component should render in dark skin', () => {
    const checkbox = render(<Checkbox skin="dark" />);
    expect(checkbox).toMatchSnapshot();
});


test('The component pass the props correctly to the generic checkbox', () => {
    const onChange = () => 'my-on-change';
    const checkbox = shallow(
        <Checkbox
            onChange={onChange}
            value="my-value"
            name="my-name"
            checked={true}>My label</Checkbox>
    );
    const genericCheckbox = checkbox.find('GenericCheckbox');
    expect(genericCheckbox.props().value).toBe('my-value');
    expect(genericCheckbox.props().name).toBe('my-name');
    expect(genericCheckbox.props().checked).toBe(true);
    expect(genericCheckbox.props().children).toBe('My label');
    expect(genericCheckbox.props().onChange()).toBe('my-on-change');
});
