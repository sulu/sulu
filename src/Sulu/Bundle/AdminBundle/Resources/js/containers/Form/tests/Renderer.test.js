// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Router from '../../../services/Router';
import FormInspector from '../FormInspector';
import Renderer from '../Renderer';
import Field from '../Field';

jest.mock('../../../services/Router/Router', () => jest.fn());
jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

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

function createFormInspector() {
    return new FormInspector(({isFieldModified: jest.fn()}: any));
}

function renderRenderer(props: Object = {}) {
    return render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{}}
            schemaPath=""
            value={{}}
            {...props}
        />
    );
}

function renderRendererAndCollectFieldProps(props: Object = {}) {
    const createElementSpy = jest.spyOn(React, 'createElement');
    const view = renderRenderer(props);

    const fieldProps = createElementSpy.mock.calls
        .filter(([component]) => component === Field)
        .map(([, componentProps]) => componentProps);

    createElementSpy.mockRestore();

    return {
        ...view,
        fieldProps,
    };
}

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
    const formInspector = createFormInspector();

    const {fieldProps} = renderRendererAndCollectFieldProps({
        formInspector,
        onFieldFinish: fieldFinishSpy,
        schema,
    });

    fieldProps[0].onFinish('/text', '/text');
    expect(fieldFinishSpy).toHaveBeenLastCalledWith('/text', '/text');

    fieldProps[1].onFinish('/datetime', '/datetime');
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

    const formInspector = createFormInspector();

    const {asFragment} = renderRenderer({
        data: {},
        formInspector,
        onChange: jest.fn(),
        onFieldFinish: jest.fn(),
        schema,
        value: {},
    });

    expect(asFragment()).toMatchSnapshot();
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

    const formInspector = createFormInspector();

    const {asFragment} = renderRenderer({
        data,
        formInspector,
        onChange: jest.fn(),
        onFieldFinish: jest.fn(),
        schema,
        value: data,
    });

    expect(asFragment()).toMatchSnapshot();
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
    const formInspector = createFormInspector();

    const {asFragment} = renderRenderer({
        data,
        formInspector,
        onChange: jest.fn(),
        onFieldFinish: jest.fn(),
        schema,
        value: data,
    });

    expect(asFragment()).toMatchSnapshot();
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

    const formInspector = createFormInspector();

    const {fieldProps} = renderRendererAndCollectFieldProps({
        data: {},
        dataPath: '/block/0',
        formInspector,
        onChange: jest.fn(),
        onFieldFinish: jest.fn(),
        schema,
        schemaPath: '/test',
        value: {},
    });

    expect(fieldProps[0].schemaPath).toEqual('/test/highlight/items/title');
    expect(fieldProps[0].dataPath).toEqual('/block/0/title');
    expect(fieldProps[1].schemaPath).toEqual('/test/highlight/items/url');
    expect(fieldProps[1].dataPath).toEqual('/block/0/url');
    expect(fieldProps[2].schemaPath).toEqual('/test/article');
    expect(fieldProps[2].dataPath).toEqual('/block/0/article');
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

    const formInspector = createFormInspector();

    const {fieldProps} = renderRendererAndCollectFieldProps({
        data,
        dataPath: '/block/0',
        formInspector,
        onChange: jest.fn(),
        onFieldFinish: jest.fn(),
        schema,
        schemaPath: '/test',
        value,
    });

    expect(fieldProps[0].data).toEqual(data);
    expect(fieldProps[0].value).toEqual('Some title');
    expect(fieldProps[1].data).toEqual(data);
    expect(fieldProps[1].value).toEqual('some-url');
    expect(fieldProps[2].data).toEqual(data);
    expect(fieldProps[2].value).toEqual('Some article');
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

    const formInspector = createFormInspector();

    const {fieldProps} = renderRendererAndCollectFieldProps({
        data: {},
        dataPath: '',
        formInspector,
        onChange: changeSpy,
        onFieldFinish: fieldFinishSpy,
        onSuccess: successSpy,
        schema,
        schemaPath: '',
        value: {},
    });

    expect(fieldProps[0].formInspector).toBe(formInspector);
    expect(fieldProps[0].name).toBe('text');
    expect(fieldProps[0].onChange).toBe(changeSpy);
    expect(fieldProps[0].onFinish).toBeInstanceOf(Function);
    expect(fieldProps[0].error).toBe(undefined);
    expect(fieldProps[0].router).toBe(undefined);
    expect(fieldProps[1].formInspector).toBe(formInspector);
    expect(fieldProps[1].name).toBe('datetime');
    expect(fieldProps[1].onChange).toBe(changeSpy);
    expect(fieldProps[1].onFinish).toBeInstanceOf(Function);
    expect(fieldProps[1].onSuccess).toBe(successSpy);
    expect(fieldProps[1].error).toBe(undefined);
    expect(fieldProps[1].router).toBe(undefined);
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
    const formInspector = createFormInspector();

    const {fieldProps} = renderRendererAndCollectFieldProps({
        data: {},
        dataPath: '',
        formInspector,
        onChange: changeSpy,
        onFieldFinish: fieldFinishSpy,
        router,
        schema,
        schemaPath: '',
        value: {},
    });

    expect(fieldProps[0].router).toBe(router);
    expect(fieldProps[1].router).toBe(router);
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

    const formInspector = createFormInspector();
    const formStore: any = (formInspector: any).formStore;
    formStore.isFieldModified.mockImplementation((dataPath) => dataPath === '/text');

    const {fieldProps} = renderRendererAndCollectFieldProps({
        data: {},
        dataPath: '',
        errors,
        formInspector,
        onChange: changeSpy,
        onFieldFinish: jest.fn(),
        schema,
        schemaPath: '',
        value: {},
    });

    expect(fieldProps[0].error).toBe(textError);
    expect(fieldProps[1].error).toBe(undefined);
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
    const formInspector = createFormInspector();

    const {fieldProps} = renderRendererAndCollectFieldProps({
        data: {},
        dataPath: '',
        errors,
        formInspector,
        onChange: changeSpy,
        onFieldFinish: jest.fn(),
        schema,
        schemaPath: '',
        showAllErrors: true,
        value: {},
    });

    fieldProps[0].onFinish('text');

    expect(fieldProps[0].error).toBe(textError);
    expect(fieldProps[1].error).toBe(datetimeError);
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

    const formInspector = createFormInspector();

    const {asFragment} = renderRenderer({
        data: {},
        dataPath: '',
        formInspector,
        onChange: changeSpy,
        onFieldFinish: jest.fn(),
        schema,
        schemaPath: '',
        value: {},
    });

    expect(asFragment()).toMatchSnapshot();
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

    const formInspector = createFormInspector();

    const {asFragment} = renderRenderer({
        data: {},
        dataPath: '',
        formInspector,
        onChange: changeSpy,
        onFieldFinish: jest.fn(),
        schema,
        schemaPath: '',
        value: {},
    });

    expect(asFragment()).toMatchSnapshot();
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

    const formInspector = createFormInspector();

    const {asFragment} = renderRenderer({
        data: {},
        dataPath: '',
        formInspector,
        onChange: changeSpy,
        onFieldFinish: jest.fn(),
        schema,
        schemaPath: '',
        value: {},
    });

    expect(asFragment()).toMatchSnapshot();
});
