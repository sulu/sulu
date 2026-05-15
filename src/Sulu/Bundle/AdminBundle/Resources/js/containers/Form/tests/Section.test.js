// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {observable} from 'mobx';
import Field from '../Field';
import Section from '../Section';
import conditionDataProviderRegistry from '../registries/conditionDataProviderRegistry';

jest.mock('../Field', () => jest.fn(() => null));

beforeEach(() => {
    conditionDataProviderRegistry.clear();
    jest.clearAllMocks();
});

function renderField(formInspector: any, data: Object) {
    return (
        <Field
            data={data}
            dataPath=""
            formInspector={formInspector}
            name="test"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSuccess={jest.fn()}
            router={undefined}
            schema={{label: 'label1', type: 'text'}}
            schemaPath=""
        />
    );
}

test('Render section with children', () => {
    const formInspector = ({}: any);

    render(
        <Section data={{}} formInspector={formInspector} name="section" schema={{label: 'Section', type: 'section'}}>
            {renderField(formInspector, {})}
        </Section>
    );

    expect(screen.getByText('Section')).toBeInTheDocument();
    expect((Field: any).mock.calls).toHaveLength(1);
});

test('Do not render anything if visibleCondition evaluates to false', () => {
    const formInspector = ({}: any);

    const schema = {
        label: 'Text',
        type: 'text_line',
        visibleCondition: 'title != "Test"',
    };

    const data = observable({title: 'Test'});

    render(
        <Section data={data} formInspector={formInspector} name="section" schema={schema}>
            {renderField(formInspector, data)}
        </Section>
    );

    expect((Field: any).mock.calls).toHaveLength(0);

    act(() => {
        data.title = 'Changed title!';
    });
    expect((Field: any).mock.calls).toHaveLength(1);
});

test('Render the section if visibleCondition with conditionDataProvider evaluates to true', () => {
    const formInspector = ({}: any);

    conditionDataProviderRegistry.add((data) => ({__test: data.test}));

    const schema = {
        label: 'Text',
        type: 'text_line',
        visibleCondition: '__test == "Test"',
    };

    const data = {test: 'Test'};

    render(
        <Section data={data} formInspector={formInspector} name="section" schema={schema}>
            {renderField(formInspector, data)}
        </Section>
    );

    expect((Field: any).mock.calls).toHaveLength(1);
});
