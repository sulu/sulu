// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Url from '../../fields/Url';
import UrlComponent from '../../../../components/Url';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../../components/Url', () => {
    const UrlComponentMock: any = jest.fn(() => null);
    UrlComponentMock.defaultProps = {
        protocols: ['http://', 'https://', 'ftp://', 'ftps://', 'mailto:', 'tel:'],
    };

    return UrlComponentMock;
});

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
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

    render(
        <Url
            {...createProps()}
            error={error}
            schemaOptions={schemaOptions}
        />
    );

    const urlProps: any = getLatestMockProps((UrlComponent: any));
    expect(urlProps.valid).toEqual(false);
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

    render(
        <Url
            {...createProps()}
            disabled={true}
            schemaOptions={schemaOptions}
            value="http://www.sulu.io"
        />
    );

    const urlProps: any = getLatestMockProps((UrlComponent: any));
    expect(urlProps.protocols).toEqual(['http://', 'https://']);
    expect(urlProps.value).toEqual('http://www.sulu.io');
    expect(urlProps.disabled).toEqual(true);
});

test('Pass no schemaOptions to Url component and render correct defaults', () => {
    const schemaOptions = {};

    render(
        <Url
            {...createProps()}
            disabled={true}
            schemaOptions={schemaOptions}
            value="http://www.sulu.io"
        />
    );

    const urlProps: any = getLatestMockProps((UrlComponent: any));
    expect(urlProps.protocols).toEqual(
        ['http://', 'https://', 'ftp://', 'ftps://', 'mailto:', 'tel:']
    );
    expect(urlProps.value).toEqual('http://www.sulu.io');
    expect(urlProps.disabled).toEqual(true);
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

    render(
        <Url
            {...createProps()}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    const urlProps: any = getLatestMockProps((UrlComponent: any));
    expect(urlProps.protocols).toEqual(
        ['http://', 'https://', 'ftp://', 'ftps://', 'mailto:', 'tel:']
    );
    expect(urlProps.defaultProtocol).toEqual('http://');
    expect(changeSpy).not.toBeCalled();
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

    render(
        <Url
            {...createProps()}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    const urlProps: any = getLatestMockProps((UrlComponent: any));
    expect(urlProps.protocols).toEqual(
        ['http://', 'https://', 'ftp://', 'ftps://', 'mailto:', 'tel:']
    );
    expect(changeSpy).toBeCalledWith('http://github.com', {'isDefaultValue': true});
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

    expect(() => render(
        <Url
            {...createProps()}
            schemaOptions={schemaOptions}
        />
    )).toThrow(/without a scheme/);
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

    render(
        <Url
            {...createProps()}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
            value="http://www.sulu.io"
        />
    );

    expect(changeSpy).not.toBeCalled();
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

    render(
        <Url
            {...createProps()}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith('https://sulu.io', {'isDefaultValue': true});
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

    const finishSpy = jest.fn();

    render(
        <Url
            {...createProps()}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    const urlProps: any = getLatestMockProps((UrlComponent: any));
    urlProps.onBlur('Test');

    expect(finishSpy).toBeCalledWith();
});
