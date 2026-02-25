// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import Renderer from '../Renderer';
import FormInspector from '../FormInspector';
import ResourceFormStore from '../stores/ResourceFormStore';
import getMockCallArg from '../../../utils/TestHelper/getMockCallArg';

jest.mock('../../../services/Router/Router', () => jest.fn());
jest.mock('../FormInspector', () => jest.fn(function() {
    this.isFieldModified = jest.fn();
}));
jest.mock('../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../../stores/ResourceStore', () => jest.fn());
jest.mock('../Field', () => jest.fn(() => null));

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
            default:
                return undefined;
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

const FieldMock: any = jest.requireMock('../Field');

const createFormInspector = () => new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
const getFieldProps = (index) => getMockCallArg(FieldMock, index, 0);

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should call onFieldFinish callback when editing a field has finished', () => {
    const schema = {
        text: {label: 'Text', type: 'text_line'},
        datetime: {label: 'Datetime', type: 'datetime'},
    };
    const fieldFinishSpy = jest.fn();

    render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    );

    getFieldProps(0).onFinish('/text', '/text');
    expect(fieldFinishSpy).toHaveBeenLastCalledWith('/text', '/text');

    getFieldProps(1).onFinish('/datetime', '/datetime');
    expect(fieldFinishSpy).toHaveBeenLastCalledWith('/datetime', '/datetime');
});

test('Should render field types based on schema', () => {
    const schema = {
        text: {label: 'Text', type: 'text_line'},
        datetime: {label: 'Datetime', type: 'datetime'},
    };

    const {asFragment} = render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render nested field types based on schema', () => {
    const schema = {
        'test/text': {label: 'Text', type: 'text_line'},
        'test/datetime': {label: 'Datetime', type: 'datetime'},
    };
    const data = {
        test: {
            text: 'Text',
            datetime: 'DateTime',
        },
    };

    const {asFragment} = render(
        <Renderer
            data={data}
            dataPath=""
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={data}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should pass correct dataPath and schemaPath to fields', () => {
    const schema = {
        highlight: {
            items: {
                title: {type: 'text_line'},
                url: {type: 'text_line'},
            },
            type: 'section',
        },
        article: {
            type: 'text_line',
        },
    };

    render(
        <Renderer
            data={{}}
            dataPath="/block/0"
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath="/test"
            value={{}}
        />
    );

    expect(getFieldProps(0).schemaPath).toEqual('/test/highlight/items/title');
    expect(getFieldProps(0).dataPath).toEqual('/block/0/title');
    expect(getFieldProps(1).schemaPath).toEqual('/test/highlight/items/url');
    expect(getFieldProps(1).dataPath).toEqual('/block/0/url');
    expect(getFieldProps(2).schemaPath).toEqual('/test/article');
    expect(getFieldProps(2).dataPath).toEqual('/block/0/article');
});

test('Should pass correct data and value to fields', () => {
    const schema = {
        highlight: {
            items: {
                title: {type: 'text_line'},
                url: {type: 'text_line'},
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

    render(
        <Renderer
            data={data}
            dataPath="/block/0"
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath="/test"
            value={value}
        />
    );

    expect(getFieldProps(0).data).toEqual(data);
    expect(getFieldProps(0).value).toEqual('Some title');
    expect(getFieldProps(1).data).toEqual(data);
    expect(getFieldProps(1).value).toEqual('some-url');
    expect(getFieldProps(2).data).toEqual(data);
    expect(getFieldProps(2).value).toEqual('Some article');
});

test('Should pass name/schema/formInspector/router to fields', () => {
    const schema = {
        text: {label: 'Text', type: 'text_line'},
        datetime: {label: 'Datetime', type: 'datetime'},
    };

    const changeSpy = jest.fn();
    const fieldFinishSpy = jest.fn();
    const successSpy = jest.fn();
    const router = new Router();
    const formInspector = createFormInspector();

    render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            onChange={changeSpy}
            onFieldFinish={fieldFinishSpy}
            onSuccess={successSpy}
            router={router}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    );

    expect(getFieldProps(0).formInspector).toBe(formInspector);
    expect(getFieldProps(0).name).toBe('text');
    expect(getFieldProps(0).onChange).toBe(changeSpy);
    expect(getFieldProps(0).onFinish).toBeInstanceOf(Function);
    expect(getFieldProps(0).error).toBe(undefined);
    expect(getFieldProps(0).router).toBe(router);
    expect(getFieldProps(1).formInspector).toBe(formInspector);
    expect(getFieldProps(1).name).toBe('datetime');
    expect(getFieldProps(1).onSuccess).toBe(successSpy);
    expect(getFieldProps(1).router).toBe(router);
});

test('Should pass errors only to modified fields', () => {
    const schema = {
        text: {label: 'Text', type: 'text_line'},
        datetime: {label: 'Datetime', type: 'datetime'},
    };
    const textError = {keyword: 'required', parameters: {}};
    const datetimeError = {keyword: 'minLength', parameters: {}};
    const errors = {text: textError, datetime: datetimeError};
    const formInspector = createFormInspector();
    formInspector.isFieldModified.mockImplementation((dataPath) => dataPath === '/text');

    render(
        <Renderer
            data={{}}
            dataPath=""
            errors={errors}
            formInspector={formInspector}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    );

    expect(getFieldProps(0).error).toBe(textError);
    expect(getFieldProps(1).error).toBe(undefined);
});

test('Should pass all errors to fields if showAllErrors is true', () => {
    const schema = {
        text: {label: 'Text', type: 'text_line'},
        datetime: {label: 'Datetime', type: 'datetime'},
    };
    const textError = {keyword: 'required', parameters: {}};
    const datetimeError = {keyword: 'minLength', parameters: {}};
    const errors = {text: textError, datetime: datetimeError};

    render(
        <Renderer
            data={{}}
            dataPath=""
            errors={errors}
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            showAllErrors={true}
            value={{}}
        />
    );

    expect(getFieldProps(0).error).toBe(textError);
    expect(getFieldProps(1).error).toBe(datetimeError);
});

test('Should render nested sections', () => {
    const schema = {
        section1: {
            label: 'Section 1',
            type: 'section',
            items: {
                item11: {label: 'Item 1.1', type: 'text_line'},
                section11: {label: 'Section 1.1', type: 'section'},
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            items: {
                item21: {label: 'Item 2.1', type: 'text_line'},
            },
        },
    };

    const {asFragment} = render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render sections with colSpan', () => {
    const schema = {
        section1: {
            label: 'Section 1',
            type: 'section',
            colSpan: 8,
            items: {
                item11: {label: 'Item 1.1', type: 'text_line'},
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            colSpan: 4,
            items: {
                item21: {label: 'Item 2.1', type: 'text_line'},
            },
        },
    };

    const {asFragment} = render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render sections without label', () => {
    const schema = {
        section1: {
            type: 'section',
            colSpan: 8,
            items: {
                item11: {label: 'Item 1.1', type: 'text_line'},
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            colSpan: 4,
            items: {
                item21: {label: 'Item 2.1', type: 'text_line'},
            },
        },
    };

    const {asFragment} = render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={schema}
            schemaPath=""
            value={{}}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});
