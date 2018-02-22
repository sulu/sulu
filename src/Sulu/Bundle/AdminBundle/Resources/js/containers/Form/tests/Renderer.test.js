// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Renderer from '../Renderer';

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
}));

test('Should render a grid', () => {
    const submitSpy = jest.fn();
    const changeSpy = jest.fn();
    const renderer = render(
        <Renderer
            data={{}}
            schema={{}}
            locale={undefined}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSubmit={submitSpy}
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

    const renderer = mount(
        <Renderer
            data={{}}
            locale={undefined}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            onSubmit={jest.fn()}
            schema={schema}
        />
    );

    renderer.find('Field').at(0).prop('onFinish')();
    expect(fieldFinishSpy).toHaveBeenCalledTimes(1);

    renderer.find('Field').at(1).prop('onFinish')();
    expect(fieldFinishSpy).toHaveBeenCalledTimes(2);
});

test('Should call onSubmit callback when submitted', () => {
    const submitSpy = jest.fn();
    const changeSpy = jest.fn();
    const renderer = mount(
        <Renderer
            data={{}}
            locale={undefined}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSubmit={submitSpy}
            schema={{}}
        />
    );

    renderer.prop('onSubmit')();
    expect(submitSpy).toBeCalled();
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
    const submitSpy = jest.fn();

    const renderer = render(
        <Renderer
            data={{}}
            locale={undefined}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSubmit={submitSpy}
            schema={schema}
        />
    );

    expect(renderer).toMatchSnapshot();
});

test('Should pass name and schema to fields', () => {
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
    const submitSpy = jest.fn();

    const renderer = shallow(
        <Renderer
            data={{}}
            locale={undefined}
            onChange={changeSpy}
            onFieldFinish={fieldFinishSpy}
            onSubmit={submitSpy}
            schema={schema}
        />
    );

    const fields = renderer.find('Field');

    expect(fields.at(0).prop('name')).toBe('text');
    expect(fields.at(0).prop('onChange')).toBe(changeSpy);
    expect(fields.at(0).prop('onFinish')).toBeInstanceOf(Function);
    expect(fields.at(0).prop('error')).toBe(undefined);
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
    const submitSpy = jest.fn();

    const renderer = shallow(
        <Renderer
            data={{}}
            errors={errors}
            locale={undefined}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSubmit={submitSpy}
            schema={schema}
        />
    );

    renderer.find('Field').at(0).simulate('finish', 'text');

    const fields = renderer.find('Field');

    expect(fields.at(0).prop('error')).toBe(textError);
    expect(fields.at(1).prop('error')).toBe(undefined);
});

test('Should render nested sections', () => {
    const changeSpy = jest.fn();
    const submitSpy = jest.fn();

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

    expect(render(
        <Renderer
            data={{}}
            locale={undefined}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSubmit={submitSpy}
            schema={schema}
        />
    )).toMatchSnapshot();
});
