/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Form from '../Form';

test('Should render form using renderer', () => {
    const form = render(<Form schema={{}} store={{}} />);
    expect(form).toMatchSnapshot();
});

test('Should call onSubmit callback on submit', () => {
    const submitSpy = jest.fn();
    const form = mount(<Form schema={{}} onSubmit={submitSpy} store={{}} />);
    form.instance().submit();

    expect(submitSpy).toBeCalled();
});

test('Should pass schema and data to renderer', () => {
    const schema = {};
    const data = {
        title: 'Title',
        description: 'Description',
    };
    const store = {data};
    const form = shallow(<Form schema={schema} store={store} />);

    expect(form.find('Renderer').props().schema).toBe(schema);
});

test('Should set data on store when changed', () => {
    const schema = {};
    const store = {
        set: jest.fn(),
    };
    const form = shallow(<Form schema={schema} store={store} />);

    form.find('Renderer').simulate('change', 'field', 'value');
    expect(store.set).toBeCalledWith('field', 'value');
});
