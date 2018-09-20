// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Renderer from '../Renderer';
import FormInspector from '../FormInspector';
import FormStore from '../stores/FormStore';
import ResourceStore from '../../../stores/ResourceStore';
import Field from '../Field';

jest.mock('../FormInspector', () => jest.fn(function() {
    this.isFieldModified = jest.fn();
}));
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

test('Should call onFieldFinish callback when editing a field has finished', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text_line',
            visible: true,
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
            visible: true,
        },
    };
    const fieldFinishSpy = jest.fn();
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = mount(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            schema={schema}
            schemaPath=""
        />
    );

    renderer.find(Field).at(0).prop('onFinish')('/text', '/text');
    expect(fieldFinishSpy).toHaveBeenLastCalledWith('/text', '/text');

    renderer.find(Field).at(1).prop('onFinish')('/datetime', '/datetime');
    expect(fieldFinishSpy).toHaveBeenLastCalledWith('/datetime', '/datetime');
});

test('Should render field types based on schema', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text_line',
            visible: true,
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
            visible: true,
        },
    };

    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
            schemaPath=""
        />
    );

    expect(renderer).toMatchSnapshot();
});

test('Should not render fields when the schema contains a visible flag of false', () => {
    const schema = {
        highlight: {
            items: {
                title: {
                    type: 'text_line',
                    visible: true,
                },
                url: {
                    type: 'text_line',
                    visible: false,
                },
            },
            type: 'section',
            visible: true,
        },
        highlight2: {
            items: {
                title: {
                    type: 'text_line',
                    visible: true,
                },
            },
            type: 'section',
            visible: false,
        },
        text: {
            label: 'Text',
            type: 'text_line',
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
            visible: false,
        },
    };

    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
            schemaPath=""
        />
    );

    expect(renderer).toMatchSnapshot();
});

test('Should pass correct schemaPath to fields', () => {
    const schema = {
        highlight: {
            items: {
                title: {
                    type: 'text_line',
                    visible: true,
                },
                url: {
                    type: 'text_line',
                    visible: true,
                },
            },
            type: 'section',
            visible: true,
        },
        article: {
            type: 'text_line',
            visible: true,
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = shallow(
        <Renderer
            data={{}}
            dataPath="/block/0"
            formInspector={formInspector}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            schema={schema}
            schemaPath="/test"
        />
    );

    expect(renderer.find('Field').at(0).prop('schemaPath')).toEqual('/test/highlight/items/title');
    expect(renderer.find('Field').at(0).prop('dataPath')).toEqual('/block/0/title');
    expect(renderer.find('Field').at(1).prop('schemaPath')).toEqual('/test/highlight/items/url');
    expect(renderer.find('Field').at(1).prop('dataPath')).toEqual('/block/0/url');
    expect(renderer.find('Field').at(2).prop('schemaPath')).toEqual('/test/article');
    expect(renderer.find('Field').at(2).prop('dataPath')).toEqual('/block/0/article');
});

test('Should pass name, schema and formInspector to fields', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text_line',
            visible: true,
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
            visible: true,
        },
    };

    const changeSpy = jest.fn();
    const fieldFinishSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const renderer = shallow(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={fieldFinishSpy}
            schema={schema}
            schemaPath=""
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
            visible: true,
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
            visible: true,
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
    formInspector.isFieldModified.mockImplementation((dataPath) => {
        return dataPath === '/text' ? true : false;
    });

    const renderer = shallow(
        <Renderer
            data={{}}
            dataPath=""
            errors={errors}
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
            schemaPath=""
        />
    );

    const fields = renderer.find('Field');

    expect(fields.at(0).prop('error')).toBe(textError);
    expect(fields.at(1).prop('error')).toBe(undefined);
});

test('Should pass all errors to fields if showAllErrors is set to true', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text_line',
            visible: true,
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
            visible: true,
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
            dataPath=""
            errors={errors}
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
            schemaPath=""
            showAllErrors={true}
        />
    );

    renderer.find(Field).at(0).prop('onFinish')('text');

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
                    visible: true,
                },
                section11: {
                    label: 'Section 1.1',
                    type: 'section',
                    visible: true,
                },
            },
            visible: true,
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                    visible: true,
                },
            },
            visible: true,
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    expect(render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
            schemaPath=""
        />
    )).toMatchSnapshot();
});

test('Should render sections with size', () => {
    const changeSpy = jest.fn();

    const schema = {
        section1: {
            label: 'Section 1',
            type: 'section',
            size: 8,
            items: {
                item11: {
                    label: 'Item 1.1',
                    type: 'text_line',
                    visible: true,
                },
            },
            visible: true,
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            size: 4,
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                    visible: true,
                },
            },
            visible: true,
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    expect(render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
            schemaPath=""
        />
    )).toMatchSnapshot();
});

test('Should render sections without label', () => {
    const changeSpy = jest.fn();

    const schema = {
        section1: {
            type: 'section',
            size: 8,
            items: {
                item11: {
                    label: 'Item 1.1',
                    type: 'text_line',
                    visible: true,
                },
            },
            visible: true,
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            size: 4,
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                    visible: true,
                },
            },
            visible: true,
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    expect(render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={schema}
            schemaPath=""
        />
    )).toMatchSnapshot();
});
