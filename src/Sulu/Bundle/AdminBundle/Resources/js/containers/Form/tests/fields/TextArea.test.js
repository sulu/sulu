// @flow
import React from 'react';
import log from 'loglevel';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import TextArea from '../../fields/TextArea';

let mockTextAreaProps: Object = {};

const mockReact = require('react');

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/TextArea', () => jest.fn((props) => {
    mockTextAreaProps = props;

    return mockReact.createElement('textarea');
}));

beforeEach(() => {
    mockTextAreaProps = {};
});

test('Pass error correctly to TextArea component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <TextArea
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
            value="xyz"
        />
    );

    expect(mockTextAreaProps.valid).toBe(false);
});

test('Pass props correctly to TextArea component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <TextArea
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(mockTextAreaProps.maxCharacters).toBe(undefined);
    expect(mockTextAreaProps.valid).toBe(true);
    expect(mockTextAreaProps.disabled).toBe(true);
});

test('Component correctly logs deprecated warning for max_characters', () => {
    const schemaOptions = {
        max_characters: {
            name: 'max_characters',
            value: '70',
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));

    expect(mockTextAreaProps.maxCharacters).toBe(70);
    expect(mockTextAreaProps.valid).toBe(true);
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

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));

    expect(mockTextAreaProps.maxCharacters).toBe(70);
    expect(mockTextAreaProps.valid).toBe(true);
});

test('Pass props correctly including soft_max_length to TextArea component', () => {
    const schemaOptions = {
        soft_max_length: {
            name: 'soft_max_length',
            value: '70',
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockTextAreaProps.maxCharacters).toBe(70);
    expect(mockTextAreaProps.valid).toBe(true);
});

test('TextArea should call onFocus when the TextArea gets focus', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const focusSpy = jest.fn();
    render(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFocus={focusSpy}
        />
    );

    const target = new EventTarget();
    mockTextAreaProps.onFocus({
        target,
    });

    expect(focusSpy).toHaveBeenCalledWith(target);
});
