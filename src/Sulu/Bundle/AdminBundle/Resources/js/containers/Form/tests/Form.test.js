// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Form from '../Form';
import ResourceStore from '../../../stores/ResourceStore';
import metadataStore from '../stores/MetadataStore';
import fieldRegistry from '../registries/FieldRegistry';

jest.mock('../registries/FieldRegistry', () => ({
    get: jest.fn(),
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
    this.set = jest.fn();
    this.changeSchema = jest.fn();
}));

jest.mock('../stores/MetadataStore', () => ({
    getFields: jest.fn(),
}));

test('Should render form using renderer', () => {
    const submitSpy = jest.fn();
    const store = new ResourceStore('snippet', '1');
    metadataStore.getFields.mockReturnValue({});

    const form = render(<Form store={store} onSubmit={submitSpy} />);
    expect(form).toMatchSnapshot();
});

test('Should call onSubmit callback on submit', () => {
    const submitSpy = jest.fn();
    const store = new ResourceStore('snippet', '1');
    metadataStore.getFields.mockReturnValue({});

    const form = mount(<Form onSubmit={submitSpy} store={store} />);
    form.instance().submit();

    expect(submitSpy).toBeCalled();
});

test('Should pass schema and data to renderer', () => {
    const submitSpy = jest.fn();
    const schema = {};
    const store = new ResourceStore('snippet', '1');
    store.data = {
        title: 'Title',
        description: 'Description',
    };
    metadataStore.getFields.mockReturnValue(schema);
    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    expect(form.find('Renderer').props().schema).toBe(schema);
});

test('Should initialize resourceStore with correct schema', () => {
    fieldRegistry.get.mockReturnValue(() => null);
    const submitSpy = jest.fn();
    const store = new ResourceStore('snippet', '1');
    const schema = {
        title: {},
        slogan: {},
    };

    store.data = {
        title: 'Title',
        slogan: 'Slogan',
    };
    metadataStore.getFields.mockReturnValue(schema);
    mount(<Form onSubmit={submitSpy} store={store} />);

    expect(store.changeSchema).toBeCalledWith(schema);
});

test('Should set data on store when changed', () => {
    const submitSpy = jest.fn();
    const store = new ResourceStore('snippet', '1');
    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    form.find('Renderer').simulate('change', 'field', 'value');
    expect(store.set).toBeCalledWith('field', 'value');
});
