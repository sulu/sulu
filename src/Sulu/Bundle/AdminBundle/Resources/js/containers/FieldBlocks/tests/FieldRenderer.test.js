// @flow
import React from 'react';
import {shallow} from 'enzyme';
import FieldRenderer from '../FieldRenderer';
import {FormInspector, FormStore, Renderer} from '../../Form';
import ResourceStore from '../../../stores/ResourceStore';

jest.mock('../../Form', () => ({
    FormInspector: jest.fn(),
    FormStore: jest.fn(),
    Renderer: jest.fn(),
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn());

test('Should pass props correctly to Renderer', () => {
    const fieldFinishSpy = jest.fn();
    const data = {
        content: 'test',
    };
    const errors = {
        content: {
            keyword: 'minLength',
            parameters: {},
        },
    };
    const schema = {
        text: {label: 'Label', type: 'text_line'},
    };
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const formRenderer = shallow(
        <FieldRenderer
            data={data}
            dataPath="/block/0/test"
            errors={errors}
            formInspector={formInspector}
            index={1}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            schema={schema}
            schemaPath="/test"
        />
    );

    expect(formRenderer.find(Renderer).props()).toEqual(expect.objectContaining({
        data,
        dataPath: '/block/0/test',
        errors,
        formInspector,
        onFieldFinish: fieldFinishSpy,
        schema,
        schemaPath: '/test',
        showAllErrors: false,
    }));
});

test('Should pass showAllErrors prop to Renderer', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const formRenderer = shallow(
        <FieldRenderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            index={2}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            schema={{}}
            schemaPath=""
            showAllErrors={true}
        />
    );

    expect(formRenderer.find(Renderer).prop('showAllErrors')).toEqual(true);
});

test('Should call onChange callback with correct index', () => {
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const formRenderer = shallow(
        <FieldRenderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            index={2}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={{}}
            schemaPath=""
        />
    );

    formRenderer.find(Renderer).prop('onChange')('test', 'value');

    expect(changeSpy).toBeCalledWith(2, 'test', 'value');
});

test('Should call onFieldFinish when some subfield finishes editing', () => {
    const fieldFinishSpy = jest.fn();
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const formRenderer = shallow(
        <FieldRenderer
            data={{}}
            dataPath=""
            formInspector={formInspector}
            index={2}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            schema={{}}
            schemaPath=""
        />
    );

    formRenderer.find(Renderer).prop('onFieldFinish')();

    expect(fieldFinishSpy).toBeCalledWith();
});
