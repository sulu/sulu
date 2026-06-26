// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Url from '../../fields/Url';

let mockUrlProps: Object = {};

const mockReact = require('react');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/Url', () => {
    const UrlMock = jest.fn((props) => {
        mockUrlProps = {
            ...props,
            disabled: props.disabled === undefined ? false : props.disabled,
            protocols: props.protocols || ['http://', 'https://', 'ftp://', 'ftps://', 'mailto:', 'tel:'],
            valid: props.valid === undefined ? true : props.valid,
        };

        return mockReact.createElement('input', {type: 'url'});
    });

    return UrlMock;
});

beforeEach(() => {
    mockUrlProps = {};
});

test('Pass error prop correctly to Url component', () => {
    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
    };

    const error = {keyword: 'minLength', parameters: {}};

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <Url
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockUrlProps.valid).toEqual(false);
});

test('Pass props correctly to Url component', () => {
    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <Url
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="http://www.sulu.io"
        />
    );

    expect(mockUrlProps.protocols).toEqual(['http://', 'https://']);
    expect(mockUrlProps.value).toEqual('http://www.sulu.io');
    expect(mockUrlProps.disabled).toEqual(true);
});

test('Pass no schemaOptions to Url component and render correct defaults', () => {
    const schemaOptions = {};

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <Url
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="http://www.sulu.io"
        />
    );

    expect(mockUrlProps.protocols).toEqual(
        ['http://', 'https://', 'ftp://', 'ftps://', 'mailto:', 'tel:']
    );
    expect(mockUrlProps.value).toEqual('http://www.sulu.io');
    expect(mockUrlProps.disabled).toEqual(true);
});

test('Not call changed when only protocol is given', () => {
    const schemaOptions = {
        defaults: {
            name: 'defaults',
            value: [
                {name: 'scheme', value: 'http://'},
            ],
        },
    };

    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockUrlProps.protocols).toEqual(
        ['http://', 'https://', 'ftp://', 'ftps://', 'mailto:', 'tel:']
    );
    expect(mockUrlProps.defaultProtocol).toEqual('http://');
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Pass correct default props to Url component', () => {
    const schemaOptions = {
        defaults: {
            name: 'defaults',
            value: [
                {name: 'scheme', value: 'http://'},
                {name: 'specific_part', value: 'github.com'},
            ],
        },
    };
    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockUrlProps.protocols).toEqual(
        ['http://', 'https://', 'ftp://', 'ftps://', 'mailto:', 'tel:']
    );
    expect(changeSpy).toHaveBeenCalledWith('http://github.com', {'isDefaultValue': true});
});

test('Throw error if only specific_part default is set', () => {
    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
        defaults: {
            name: 'defaults',
            value: [
                {name: 'specific_part', value: 'sulu.io'},
            ],
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    expect(() => new Url({
        ...fieldTypeDefaultProps,
        formInspector,
        schemaOptions,
    })).toThrow(/without a scheme/);
});

test('Do not build URL from defaults if value is already given', () => {
    const changeSpy = jest.fn();

    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
        defaults: {
            name: 'defaults',
            value: [
                {name: 'scheme', value: 'https://'},
                {name: 'specific_part', value: 'sulu.io'},
            ],
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
            value="http://www.sulu.io"
        />
    );

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Build URL from defaults to pass as value to URL component', () => {
    const changeSpy = jest.fn();

    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
        defaults: {
            name: 'defaults',
            value: [
                {name: 'scheme', value: 'https://'},
                {name: 'specific_part', value: 'sulu.io'},
            ],
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toHaveBeenCalledWith('https://sulu.io', {'isDefaultValue': true});
});

test('Should not pass any arguments to onFinish callback', () => {
    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const finishSpy = jest.fn();

    render(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    mockUrlProps.onBlur('Test');

    expect(finishSpy).toHaveBeenCalledWith();
});
