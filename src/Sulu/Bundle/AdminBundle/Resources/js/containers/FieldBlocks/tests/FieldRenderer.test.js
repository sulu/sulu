// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Router from '../../../services/Router';
import FieldRenderer from '../FieldRenderer';
import {FormInspector, ResourceFormStore, Renderer} from '../../Form';
import ResourceStore from '../../../stores/ResourceStore';

jest.mock('../../../services/Router/Router', () => jest.fn());

jest.mock('../../Form', () => ({
    FormInspector: jest.fn(),
    ResourceFormStore: jest.fn(),
    Renderer: jest.fn(() => null),
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn());

function getLatestRendererProps() {
    const calls = (Renderer: any).mock.calls;
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should pass props correctly to Renderer', () => {
    const fieldFinishSpy = jest.fn();
    const successSpy = jest.fn();

    const value = {
        title: 'Test',
    };

    const data = {
        content: 'test',
        block: value,
    };

    const errors = {
        content: {
            keyword: 'minLength',
            parameters: {},
        },
    };
    const schema = {
        text: {label: 'Label', type: 'text_line', visible: true},
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
    const router = new Router();

    render(
        <FieldRenderer
            data={data}
            dataPath="/block/0/test"
            errors={errors}
            formInspector={formInspector}
            index={1}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            onSuccess={successSpy}
            router={router}
            schema={schema}
            schemaPath="/test"
            value={value}
        />
    );

    expect(getLatestRendererProps()).toEqual(expect.objectContaining({
        data,
        dataPath: '/block/0/test',
        errors,
        formInspector,
        onFieldFinish: fieldFinishSpy,
        onSuccess: successSpy,
        router,
        schema,
        schemaPath: '/test',
        showAllErrors: false,
        value,
    }));
});

test('Should pass showAllErrors prop to Renderer', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    render(
        <FieldRenderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            index={2}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{}}
            schemaPath=""
            showAllErrors={true}
            value={{}}
        />
    );

    expect(getLatestRendererProps().showAllErrors).toEqual(true);
});

test('Should call onChange callback with correct index', () => {
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    render(
        <FieldRenderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            index={2}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSuccess={jest.fn()}
            router={undefined}
            schema={{}}
            schemaPath=""
            value={{}}
        />
    );

    getLatestRendererProps().onChange('test', 'value');

    expect(changeSpy).toBeCalledWith(2, 'test', 'value', undefined);
});

test('Should pass context through onChange callback', () => {
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    render(
        <FieldRenderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            index={0}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            onSuccess={jest.fn()}
            router={undefined}
            schema={{}}
            schemaPath=""
            value={{}}
        />
    );

    getLatestRendererProps().onChange('alignment', 'left', {isDefaultValue: true});

    expect(changeSpy).toBeCalledWith(0, 'alignment', 'left', {isDefaultValue: true});
});

test('Should call onFieldFinish when some subfield finishes editing', () => {
    const fieldFinishSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    render(
        <FieldRenderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            index={2}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            onSuccess={jest.fn()}
            router={undefined}
            schema={{}}
            schemaPath=""
            value={{}}
        />
    );

    getLatestRendererProps().onFieldFinish();

    expect(fieldFinishSpy).toBeCalledWith();
});
