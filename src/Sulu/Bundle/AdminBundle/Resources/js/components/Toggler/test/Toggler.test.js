/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow} from 'enzyme';
import React from 'react';
import Toggler from '../Toggler';

test('The component pass the props correctly to the generic checkbox', () => {
    const onChange = jest.fn().mockReturnValue('my-on-change');
    const toggler = shallow(
        <Toggler
            checked={true}
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
    expect(switchComponent.props().children).toBe('My label');
    expect(switchComponent.props().onChange()).toBe('my-on-change');
});
