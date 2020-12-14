// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow, render} from 'enzyme';
import ResourceStore from '../../../stores/ResourceStore';
import Field from '../Field';
import Section from '../Section';
import FormInspector from '../FormInspector';
import conditionDataProviderRegistry from '../registries/conditionDataProviderRegistry';
import fieldRegistry from '../registries/fieldRegistry';
import ResourceFormStore from '../stores/ResourceFormStore';

jest.mock('../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions?.locale;
}));

jest.mock('../FormInspector', () => jest.fn(function(resourceFormStore) {
    this.locale = resourceFormStore.locale;
}));

jest.mock('../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('../registries/fieldRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn(),
}));

test('Render section with children', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="text" />;
    });

    expect(render(
        <Section data={{}} formInspector={formInspector} name="section" schema={{label: 'Section', type: 'section'}}>
            <Field
                data={{}}
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
        </Section>
    )).toMatchSnapshot();
});

test('Do not render anything if visibleCondition evaluates to false', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="date" />;
    });

    const schema = {
        label: 'Text',
        type: 'text_line',
        visibleCondition: 'title != "Test"',
    };

    const data = observable({title: 'Test'});

    const section = shallow(
        <Section data={data} formInspector={formInspector} name="section" schema={schema}>
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
        </Section>
    );

    expect(section.find('Section')).toHaveLength(0);

    data.title = 'Changed title!';
    expect(section.find('Section')).toHaveLength(1);
});

test('Render the section if visibleCondition with locale evaluates to true', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('snippets', undefined, {locale: 'en'}),
            'snippets'
        )
    );

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="date" />;
    });

    const schema = {
        label: 'Text',
        type: 'text_line',
        visibleCondition: '__locale == "en"',
    };

    const section = shallow(
        <Section data={{}} formInspector={formInspector} name="section" schema={schema}>
            <Field
                data={{}}
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
        </Section>
    );

    expect(section.find('Section')).toHaveLength(1);
});

test('Render the section if visibleCondition with conditionDataProvider evaluates to true', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));

    conditionDataProviderRegistry.add((data) => ({__test: data.test}));

    fieldRegistry.get.mockReturnValue(function Text() {
        return <input type="date" />;
    });

    const schema = {
        label: 'Text',
        type: 'text_line',
        visibleCondition: '__test == "Test"',
    };

    const data = {test: 'Test'};

    const section = shallow(
        <Section data={data} formInspector={formInspector} name="section" schema={schema}>
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
        </Section>
    );

    expect(section.find('Section')).toHaveLength(1);
});
