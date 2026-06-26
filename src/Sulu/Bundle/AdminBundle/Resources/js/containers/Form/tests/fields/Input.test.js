// @flow
import React from 'react';
import log from 'loglevel';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Input from '../../fields/Input';

let mockInputProps: Object = {};

const mockReact = require('react');

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/Input', () => jest.fn((props) => {
    mockInputProps = props;

    return mockReact.createElement('input', {type: 'text'});
}));

beforeEach(() => {
    mockInputProps = {};
});

test('Pass error correctly to Input component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <Input
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(mockInputProps.valid).toBe(false);
});

test('Pass props correctly to Input component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Input
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(mockInputProps.maxCharacters).toBe(undefined);
    expect(mockInputProps.valid).toBe(true);
    expect(mockInputProps.disabled).toBe(true);
    expect(mockInputProps.headline).toBe(undefined);
});

test('Pass headline prop correctly', () => {
    const schemaOptions = {
        headline: {
            name: 'headline',
            value: true,
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Input
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockInputProps.headline).toBe(true);
});

test('Pass autoFocus prop correctly', () => {
    const schemaOptions = {
        autofocus: {
            name: 'autofocus',
            value: true,
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Input
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockInputProps.autoFocus).toBe(true);
});

test('Component correctly logs deprecated warning for max_characters', () => {
    const schemaOptions = {
        max_characters: {
            name: 'max_characters',
            value: '70',
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Input
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));

    expect(mockInputProps.maxCharacters).toBe(70);
    expect(mockInputProps.valid).toBe(true);
});

test('Component correctly chooses soft_max_length over max_characters', () => {
    const schemaOptions = {
        max_characters: {
            name: 'max_characters',
            value: '55',
        },
        soft_max_length: {
            name: 'soft_max_length',
            value: '70',
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Input
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));

    expect(mockInputProps.maxCharacters).toBe(70);
    expect(mockInputProps.valid).toBe(true);
});

test('Pass props correctly including soft_max_length to Input component', () => {
    const schemaOptions = {
        soft_max_length: {
            name: 'soft_max_length',
            value: '70',
        },
        max_segments: {
            name: 'max_segments',
            value: '6',
        },
        segment_delimiter: {
            name: 'segment_delimiter',
            value: ',',
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Input
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockInputProps.maxCharacters).toBe(70);
    expect(mockInputProps.maxSegments).toBe(6);
    expect(mockInputProps.segmentDelimiter).toBe(',');
    expect(mockInputProps.valid).toBe(true);
});

test('Should not pass any arguments to onFinish callback', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const finishSpy = jest.fn();

    render(
        <Input
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
        />
    );

    mockInputProps.onBlur('Test');

    expect(finishSpy).toHaveBeenCalledWith();
});

test('TextArea should call onFocus when the TextArea gets focus', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const focusSpy = jest.fn();
    render(
        <Input
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFocus={focusSpy}
        />
    );

    const target = new EventTarget();
    mockInputProps.onFocus({
        target,
    });

    expect(focusSpy).toHaveBeenCalledWith(target);
});
