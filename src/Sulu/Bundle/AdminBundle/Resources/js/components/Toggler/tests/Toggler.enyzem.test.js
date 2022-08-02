// @flow
import {render, shallow} from 'enzyme';
import React from 'react';
import Toggler from '../Toggler';

test('The component should render in the default state', () => {
    const toggler = render(
        <Toggler
            checked={true}
            name="my-name"
            onChange={jest.fn()}
            value="my-value"
        >
            Label
        </Toggler>);

    expect(toggler).toMatchSnapshot();
});

test('The component should render in disabled state', () => {
    const toggler = render(
        <Toggler
            checked={true}
            disabled={true}
            name="my-name"
            onChange={jest.fn()}
            value="my-value"
        >
            Label
        </Toggler>);

    expect(toggler).toMatchSnapshot();
});

test('The component pass the props correctly to the generic checkbox', () => {
    const onChange = jest.fn();
    const toggler = shallow(
        <Toggler
            checked={true}
            disabled={true}
            name="my-name"
            onChange={onChange}
            value="my-value"
        >
            My label
        </Toggler>
    );
    const switchComponent = toggler.find('Switch');
    expect(switchComponent.props().value).toBe('my-value');
    expect(switchComponent.props().name).toBe('my-name');
    expect(switchComponent.props().checked).toBe(true);
    expect(switchComponent.props().disabled).toBe(true);
    expect(switchComponent.props().children).toBe('My label');
});
