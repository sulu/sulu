/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render} from 'enzyme';
import Form from '../Form';

test('Should render form using renderer', () => {
    const form = render(<Form />);
    expect(form).toMatchSnapshot();
});

test('Should call onSubmit callback on submit', () => {
    const submitSpy = jest.fn();
    const form = mount(<Form onSubmit={submitSpy} />);
    form.instance().submit();

    expect(submitSpy).toBeCalled();
});
