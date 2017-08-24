/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow} from 'enzyme';
import React from 'react';
import Toggler from '../Toggler';

test('The component pass the props correctly to the generic checkbox', () => {
    const onChange = () => 'my-on-change';
    const toggler = shallow(
        <Toggler
            onChange={onChange}
            value="my-value"
            name="my-name"
            checked={true}>My label</Toggler>
    );
    const genericCheckbox = toggler.find('GenericCheckbox');
    expect(genericCheckbox.props().value).toBe('my-value');
    expect(genericCheckbox.props().name).toBe('my-name');
    expect(genericCheckbox.props().checked).toBe(true);
    expect(genericCheckbox.props().children).toBe('My label');
    expect(genericCheckbox.props().onChange()).toBe('my-on-change');
});
