// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Renderer from '../Renderer';
import FormInspector from '../FormInspector';
import FormStore from '../stores/FormStore';
import ResourceStore from '../../../stores/ResourceStore';

jest.mock('../FormInspector', () => jest.fn());
jest.mock('../stores/FormStore', () => jest.fn());
jest.mock('../../../stores/ResourceStore', () => jest.fn());

jest.mock('../registries/FieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'text_line':
                return function Text() {
                    return <input type="text" />;
                };
            case 'datetime':
                return function DateTime() {
                    return <input type="datetime" />;
                };
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

test('Should render a grid', () => {
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = render(
        <Renderer
            data={{}}
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={{}}
        />
    );
    expect(renderer).toMatchSnapshot();
});

test('Should call onFieldFinish callback when editing a field has finished', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text_line',
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
        },
    };
    const fieldFinishSpy = jest.fn();
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = mount(
        <Renderer
            data={{}}
            formInspector={formInspector}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            schema={schema}
        />
    );

    renderer.find('Field').at(0).prop('onFinish')();
    expect(fieldFinishSpy).toHaveBeenCalledTimes(1);

    renderer.find('Field').at(1).prop('onFinish')();
    expect(fieldFinishSpy).toHaveBeenCalledTimes(2);
});

test('Should render field types based on schema', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text_line',
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
        },
    };

    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = render(
        <Renderer
            data={{}}
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
        />
    );

    expect(renderer).toMatchSnapshot();
});

test('Should pass name, schema and formInspector to fields', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text_line',
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
        },
    };

    const changeSpy = jest.fn();
    const fieldFinishSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = shallow(
        <Renderer
            data={{}}
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={fieldFinishSpy}
            schema={schema}
        />
    );

    const fields = renderer.find('Field');

    expect(fields.at(0).prop('formInspector')).toBe(formInspector);
    expect(fields.at(0).prop('name')).toBe('text');
    expect(fields.at(0).prop('onChange')).toBe(changeSpy);
    expect(fields.at(0).prop('onFinish')).toBeInstanceOf(Function);
    expect(fields.at(0).prop('error')).toBe(undefined);
    expect(fields.at(1).prop('formInspector')).toBe(formInspector);
    expect(fields.at(1).prop('name')).toBe('datetime');
    expect(fields.at(1).prop('onChange')).toBe(changeSpy);
    expect(fields.at(1).prop('onFinish')).toBeInstanceOf(Function);
    expect(fields.at(1).prop('error')).toBe(undefined);
});

test('Should pass errors to fields that have already been modified at least once', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text_line',
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
        },
    };

    const textError = {
        keyword: 'required',
        parameters: {},
    };
    const datetimeError = {
        keyword: 'minLength',
        parameters: {},
    };
    const errors = {
        text: textError,
        datetime: datetimeError,
    };

    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = shallow(
        <Renderer
            data={{}}
            errors={errors}
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
        />
    );

    renderer.find('Field').at(0).simulate('finish', 'text');

    const fields = renderer.find('Field');

    expect(fields.at(0).prop('error')).toBe(textError);
    expect(fields.at(1).prop('error')).toBe(undefined);
});

test('Should pass all errors to fields if showAllErrors is set to true', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text_line',
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
        },
    };

    const textError = {
        keyword: 'required',
        parameters: {},
    };
    const datetimeError = {
        keyword: 'minLength',
        parameters: {},
    };
    const errors = {
        text: textError,
        datetime: datetimeError,
    };

    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = shallow(
        <Renderer
            data={{}}
            errors={errors}
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
            showAllErrors={true}
        />
    );

    renderer.find('Field').at(0).simulate('finish', 'text');

    const fields = renderer.find('Field');

    expect(fields.at(0).prop('error')).toBe(textError);
    expect(fields.at(1).prop('error')).toBe(datetimeError);
});

test('Should render nested sections', () => {
    const changeSpy = jest.fn();

    const schema = {
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

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    expect(render(
        <Renderer
            data={{}}
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
        />
    )).toMatchSnapshot();
});
