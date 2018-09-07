// @flow
import React from 'react';
import {shallow} from 'enzyme';
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
        } ,
    };

    const error = {keyword: 'minLength', parameters: {}};

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const url = shallow(
        <Url
            dataPath=""
            error={error}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value="http://www.sulu.io"
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
        } ,
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const url = shallow(
        <Url
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value="http://www.sulu.io"
        />
    );

    expect(url.find(UrlComponent).prop('protocols')).toEqual(['http://', 'https://']);
    expect(url.find(UrlComponent).prop('value')).toEqual('http://www.sulu.io');
});

test('Pass correct default props to Url component', () => {
    const schemaOptions = {};
    const changeSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const url = shallow(
        <Url
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    expect(url.find(UrlComponent).prop('protocols')).toEqual(['http://', 'https://', 'ftp://', 'ftps://']);
    expect(changeSpy).toBeCalledWith('https://');
});

test('Throw error if only specific_part default is set', () => {
    const schemaOptions = {
        schemes: {
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        } ,
        defaults: {
            value: [
                {name: 'specific_part', value: 'sulu.io'},
            ],
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    expect(() => shallow(
        <Url
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
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
        } ,
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
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={'http://www.sulu.io'}
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
        } ,
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
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
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
        } ,
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const finishSpy = jest.fn();

    const url = shallow(
        <Url
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value="xyz"
        />
    );

    url.find('Url').prop('onBlur')('Test');

    expect(finishSpy).toBeCalledWith();
});
