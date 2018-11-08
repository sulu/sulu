// @flow
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

test('The component should render disabled', () => {
    const checkbox = render(<Checkbox disabled={true} />);
    expect(checkbox).toMatchSnapshot();
});

test('The component pass the props correctly to the generic checkbox', () => {
    const onChange = jest.fn();
    const checkbox = shallow(
        <Checkbox
            checked={true}
            name="my-name"
            onChange={onChange}
            value="my-value"
        >
            My label
        </Checkbox>
    );
    const switchComponent = checkbox.find('Switch');
    expect(switchComponent.props().value).toBe('my-value');
    expect(switchComponent.props().name).toBe('my-name');
    expect(switchComponent.props().checked).toBe(true);
    expect(switchComponent.props().children).toBe('My label');
});
