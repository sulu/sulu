// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import FieldRenderer from '../FieldRenderer';

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
    const locale = observable.box('de');
    const schema = {
        text: {label: 'Label', type: 'text_line'},
    };

    const formRenderer = shallow(
        <FieldRenderer
            data={data}
            errors={errors}
            index={1}
            locale={locale}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            schema={schema}
        />
    );

    expect(formRenderer.find('Renderer').props()).toEqual(expect.objectContaining({
        data,
        errors,
        locale,
        onFieldFinish: fieldFinishSpy,
        schema,
        showAllErrors: false,
    }));
});

test('Should pass showAllErrors prop to Renderer', () => {
    const formRenderer = shallow(
        <FieldRenderer
            data={{}}
            index={2}
            locale={observable.box('de')}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            showAllErrors={true}
            schema={{}}
        />
    );

    expect(formRenderer.find('Renderer').prop('showAllErrors')).toEqual(true);
});

test('Should call onChange callback with correct index', () => {
    const changeSpy = jest.fn();

    const formRenderer = shallow(
        <FieldRenderer
            data={{}}
            index={2}
            locale={undefined}
            onChange={changeSpy}
            onFieldFinish={jest.fn()}
            schema={{}}
        />
    );

    formRenderer.find('Renderer').prop('onChange')('test', 'value');

    expect(changeSpy).toBeCalledWith(2, 'test', 'value');
});

test('Should call onFieldFinish when some subfield finishes editing', () => {
    const fieldFinishSpy = jest.fn();

    const formRenderer = shallow(
        <FieldRenderer
            data={{}}
            index={2}
            locale={undefined}
            onChange={jest.fn()}
            onFieldFinish={fieldFinishSpy}
            schema={{}}
        />
    );

    formRenderer.find('Renderer').prop('onFieldFinish')();

    expect(fieldFinishSpy).toBeCalledWith();
});
