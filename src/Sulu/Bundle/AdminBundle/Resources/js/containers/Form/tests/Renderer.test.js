// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import Renderer from '../Renderer';
import FormInspector from '../FormInspector';
import ResourceFormStore from '../stores/ResourceFormStore';
import Field from '../Field';

jest.mock('../../../services/Router/Router', () => jest.fn());
jest.mock('../FormInspector', () => jest.fn(function() {
    this.isFieldModified = jest.fn();
}));
jest.mock('../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../../stores/ResourceStore', () => jest.fn());

jest.mock('../registries/fieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'text_line':
                return function Text({value}) {
                    return <input onChange={jest.fn()} type="text" value={value} />;
                };
            case 'datetime':
                return function DateTime({value}) {
                    return <input onChange={jest.fn()} type="datetime" value={value} />;
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
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
        },
    };
    const fieldFinishSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    const renderer = mount(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
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
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
        },
    };

    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    const renderer = render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    );

    expect(renderer).toMatchSnapshot();
});

test('Should render nested field types with based on schema', () => {
    const schema = {
        'test/text': {
            label: 'Text',
            type: 'text_line',
        },
        'test/datetime': {
            label: 'Datetime',
            type: 'datetime',
        },
    };

    const data = {
        test: {
            text: 'Text',
            datetime: 'DateTime',
        },
    };

    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    const renderer = render(
        <Renderer
            data={data}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={data}
        />
    );

    expect(renderer).toMatchSnapshot();
});

test('Should not render fields when the schema contains a visibleCondition evaluating false', () => {
    const schema = {
        highlight: {
            items: {
                title: {
                    type: 'text_line',
                    visibleCondition: 'test == "Test"',
                },
                url: {
                    type: 'text_line',
                    visibleCondition: 'test == false',
                },
            },
            type: 'section',
            visibleCondition: 'test == "Test"',
        },
        highlight2: {
            items: {
                title: {
                    type: 'text_line',
                    visibleCondition: 'test == "Test"',
                },
            },
            type: 'section',
            visibleCondition: 'test == false',
        },
        text: {
            label: 'Text',
            type: 'text_line',
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
            visibleCondition: 'test == false',
        },
    };

    const data = {test: 'Test'};

    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    const renderer = render(
        <Renderer
            data={data}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={data}
        />
    );

    expect(renderer).toMatchSnapshot();
});

test('Should pass correct dataPath and schemaPath to fields', () => {
    const schema = {
        highlight: {
            items: {
                title: {
                    type: 'text_line',
                },
                url: {
                    type: 'text_line',
                },
            },
            type: 'section',
        },
        article: {
            type: 'text_line',
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    const renderer = shallow(
        <Renderer
            data={{}}
            dataPath="/block/0"
            formInspector={formInspector}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath="/test"
            value={{}}
        />
    );

    expect(renderer.find('Field').at(0).prop('schemaPath')).toEqual('/test/highlight/items/title');
    expect(renderer.find('Field').at(0).prop('dataPath')).toEqual('/block/0/title');
    expect(renderer.find('Field').at(1).prop('schemaPath')).toEqual('/test/highlight/items/url');
    expect(renderer.find('Field').at(1).prop('dataPath')).toEqual('/block/0/url');
    expect(renderer.find('Field').at(2).prop('schemaPath')).toEqual('/test/article');
    expect(renderer.find('Field').at(2).prop('dataPath')).toEqual('/block/0/article');
});

test('Should pass correct data and value to fields', () => {
    const schema = {
        highlight: {
            items: {
                title: {
                    type: 'text_line',
                },
                url: {
                    type: 'text_line',
                },
            },
            type: 'section',
        },
        article: {
            type: 'text_line',
        },
    };

    const value = {
        title: 'Some title',
        url: 'some-url',
        article: 'Some article',
    };

    const data = {
        title: 'Page title',
        block: value,
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    const renderer = shallow(
        <Renderer
            data={data}
            dataPath="/block/0"
            formInspector={formInspector}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath="/test"
            value={value}
        />
    );

    expect(renderer.find('Field').at(0).prop('data')).toEqual(data);
    expect(renderer.find('Field').at(0).prop('value')).toEqual('Some title');
    expect(renderer.find('Field').at(1).prop('data')).toEqual(data);
    expect(renderer.find('Field').at(1).prop('value')).toEqual('some-url');
    expect(renderer.find('Field').at(2).prop('data')).toEqual(data);
    expect(renderer.find('Field').at(2).prop('value')).toEqual('Some article');
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
    const successSpy = jest.fn();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    const renderer = shallow(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={fieldFinishSpy}
            onSuccess={successSpy}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    );

    const fields = renderer.find('Field');

    expect(fields.at(0).prop('formInspector')).toBe(formInspector);
    expect(fields.at(0).prop('name')).toBe('text');
    expect(fields.at(0).prop('onChange')).toBe(changeSpy);
    expect(fields.at(0).prop('onFinish')).toBeInstanceOf(Function);
    expect(fields.at(0).prop('error')).toBe(undefined);
    expect(fields.at(0).prop('router')).toBe(undefined);
    expect(fields.at(1).prop('formInspector')).toBe(formInspector);
    expect(fields.at(1).prop('name')).toBe('datetime');
    expect(fields.at(1).prop('onChange')).toBe(changeSpy);
    expect(fields.at(1).prop('onFinish')).toBeInstanceOf(Function);
    expect(fields.at(1).prop('onSuccess')).toBe(successSpy);
    expect(fields.at(1).prop('error')).toBe(undefined);
    expect(fields.at(1).prop('router')).toBe(undefined);
});

test('Should pass router to fields if given', () => {
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

    const router = new Router();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    const renderer = shallow(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={fieldFinishSpy}
            onSuccess={undefined}
            router={router}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    );

    const fields = renderer.find('Field');

    expect(fields.at(0).prop('router')).toBe(router);
    expect(fields.at(1).prop('router')).toBe(router);
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

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
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
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
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

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    const renderer = shallow(
        <Renderer
            data={{}}
            dataPath=""
            errors={errors}
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            showAllErrors={true}
            value={{}}
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

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    expect(render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    )).toMatchSnapshot();
});

test('Should render sections with colSpan', () => {
    const changeSpy = jest.fn();

    const schema = {
        section1: {
            label: 'Section 1',
            type: 'section',
            colSpan: 8,
            items: {
                item11: {
                    label: 'Item 1.1',
                    type: 'text_line',
                },
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            colSpan: 4,
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                },
            },
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    expect(render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    )).toMatchSnapshot();
});

test('Should render sections without label', () => {
    const changeSpy = jest.fn();

    const schema = {
        section1: {
            type: 'section',
            colSpan: 8,
            items: {
                item11: {
                    label: 'Item 1.1',
                    type: 'text_line',
                },
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            colSpan: 4,
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                },
            },
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    expect(render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    )).toMatchSnapshot();
});
