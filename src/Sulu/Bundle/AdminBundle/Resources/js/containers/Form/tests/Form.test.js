// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Form from '../Form';
import ResourceStore from '../../../stores/ResourceStore';
import FormStore from '../stores/FormStore';
import metadataStore from '../stores/MetadataStore';

jest.mock('../registries/FieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'text_line':
                return require('../../../components/Input').default;
        }
    }),
}));

jest.mock('../stores/FormStore', () => jest.fn(function() {
    this.data = {};
    this.validate = jest.fn();
    this.schema = {};
    this.set = jest.fn();
    this.change = jest.fn();
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn());

jest.mock('../stores/MetadataStore', () => ({
    getSchema: jest.fn(),
}));

test('Should render form using renderer', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));

    const form = render(<Form store={store} onSubmit={submitSpy} />);
    expect(form).toMatchSnapshot();
});

test('Should call onSubmit callback on submit', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={submitSpy} store={store} />);

    const submitButtonClick = jest.fn();
    form.instance().submitButton.click = submitButtonClick;

    form.instance().submit();

    const preventDefault = jest.fn();
    form.find('form').prop('onSubmit')({preventDefault});

    expect(submitButtonClick).toBeCalled();
    expect(preventDefault).toBeCalled();
    expect(submitSpy).toBeCalled();
});

test('Should validate form when a field has finished being edited', () => {
    const store = new FormStore(new ResourceStore('snippet', '1'));
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={jest.fn()} store={store} />);

    form.find('Renderer').prop('onFieldFinish')();

    expect(store.validate).toBeCalledWith();
});

test('Should pass schema, data and showAllErrors flag to Renderer', () => {
    const store = new FormStore(new ResourceStore('snippet', '1'));
    store.schema = {};
    store.data.title = 'Title';
    store.data.description = 'Description';
    const form = shallow(<Form onSubmit={jest.fn()} store={store} />);

    expect(form.find('Renderer').props()).toEqual(expect.objectContaining({
        data: store.data,
        schema: store.schema,
    }));
});

test('Should apss showAllErrors flag to Renderer when form has been submitted', () => {
    const store = new FormStore(new ResourceStore('snippet', '1'));
    const form = shallow(<Form onSubmit={jest.fn()} store={store} />);

    expect(form.find('Renderer').prop('showAllErrors')).toEqual(false);
    form.find('form').simulate('submit', {preventDefault: jest.fn()});
    expect(form.find('Renderer').prop('showAllErrors')).toEqual(true);
});

test('Should change data on store when changed', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));
    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    form.find('Renderer').simulate('change', 'field', 'value');
    expect(store.change).toBeCalledWith('field', 'value');
});

test('Should change data on store without sections', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));
    store.schema = {
        section1: {
            label: 'Section 1',
            type: 'section',
            items: {
                item11: {
                    label: 'Item 1.1',
                    type: 'text_line',
                },
                section11: {
                    label: 'Section 1.1',
                    type: 'section',
                },
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                },
            },
        },
    };

    const form = mount(<Form store={store} onSubmit={submitSpy} />);
    form.find('Input').at(0).instance().handleChange({currentTarget: {value: 'value!'}});

    expect(store.change).toBeCalledWith('item11', 'value!');
});
