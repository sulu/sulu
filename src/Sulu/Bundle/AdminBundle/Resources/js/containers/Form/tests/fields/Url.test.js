// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import Url from '../../fields/Url';
import UrlComponent from '../../../../components/Url';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error prop correctly to Url component', () => {
    const schemaOptions = {
        schemes: {
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
    };

    const error = {keyword: 'minLength', parameters: {}};

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const url = shallow(
        <Url
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(url.find(UrlComponent).prop('valid')).toEqual(false);
});

test('Pass props correctly to Url component', () => {
    const schemaOptions = {
        schemes: {
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const url = shallow(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="http://www.sulu.io"
        />
    );

    expect(url.find(UrlComponent).prop('protocols')).toEqual(['http://', 'https://']);
    expect(url.find(UrlComponent).prop('value')).toEqual('http://www.sulu.io');
});

test('Not call changed when only protocol is given', () => {
    const schemaOptions = {
        defaults: {
            value: [
                {name: 'scheme', value: 'http://'},
            ],
        },
    };

    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const url = shallow(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(url.find(UrlComponent).prop('protocols')).toEqual(['http://', 'https://', 'ftp://', 'ftps://']);
    expect(url.find(UrlComponent).prop('defaultProtocol')).toEqual('http://');
    expect(changeSpy).not.toBeCalled();
});

test('Pass correct default props to Url component', () => {
    const schemaOptions = {
        defaults: {
            value: [
                {name: 'scheme', value: 'http://'},
                {name: 'specific_part', value: 'github.com'},
            ],
        },
    };
    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const url = shallow(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(url.find(UrlComponent).prop('protocols')).toEqual(['http://', 'https://', 'ftp://', 'ftps://']);
    expect(changeSpy).toBeCalledWith('http://github.com');
});

test('Throw error if only specific_part default is set', () => {
    const schemaOptions = {
        schemes: {
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
        defaults: {
            value: [
                {name: 'specific_part', value: 'sulu.io'},
            ],
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    expect(() => shallow(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrow(/without a scheme/);
});

test('Do not build URL from defaults if value is already given', () => {
    const changeSpy = jest.fn();

    const schemaOptions = {
        schemes: {
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
        defaults: {
            value: [
                {name: 'scheme', value: 'https://'},
                {name: 'specific_part', value: 'sulu.io'},
            ],
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    shallow(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
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
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
        defaults: {
            value: [
                {name: 'scheme', value: 'https://'},
                {name: 'specific_part', value: 'sulu.io'},
            ],
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    shallow(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith('https://sulu.io');
});

test('Should not pass any arguments to onFinish callback', () => {
    const schemaOptions = {
        schemes: {
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const finishSpy = jest.fn();

    const url = shallow(
        <Url
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    url.find('Url').prop('onBlur')('Test');

    expect(finishSpy).toBeCalledWith();
});
