/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Form from '../Form';

test('Should render form using renderer', () => {
    const form = render(<Form schema={{}} />);
    expect(form).toMatchSnapshot();
});

test('Should call onSubmit callback on submit', () => {
    const submitSpy = jest.fn();
    const form = mount(<Form schema={{}} onSubmit={submitSpy} />);
    form.instance().submit();

    expect(submitSpy).toBeCalled();
});

test('Should pass schema to renderer', () => {
    const schema = {};
    const form = shallow(<Form schema={schema} />);

    expect(form.find('Renderer').props().schema).toBe(schema);
});
