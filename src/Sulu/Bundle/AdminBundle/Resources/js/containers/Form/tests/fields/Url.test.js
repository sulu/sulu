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

test('Pass error prop correctly to Input component', () => {
    const schemaOptions = {
        schemes: {
            value: [
                {
                    name: 'http://',
                },
                {
                    name: 'https://',
                },
            ],
        } ,
    };

    const error = {keyword: 'minLength', parameters: {}};

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const inputValid = shallow(
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

    expect(inputValid.find(UrlComponent).prop('valid')).toEqual(false);
});

test('Pass props correctly to Input component', () => {
    const schemaOptions = {
        schemes: {
            value: [
                {
                    name: 'http://',
                },
                {
                    name: 'https://',
                },
            ],
        } ,
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const inputValid = shallow(
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

    expect(inputValid.find(UrlComponent).prop('protocols')).toEqual(['http://', 'https://']);
    expect(inputValid.find(UrlComponent).prop('value')).toEqual('http://www.sulu.io');
});

test('Should not pass any arguments to onFinish callback', () => {
    const schemaOptions = {
        schemes: {
            value: [
                {
                    name: 'http://',
                },
                {
                    name: 'https://',
                },
            ],
        } ,
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const finishSpy = jest.fn();

    const input = shallow(
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

    input.find('Url').prop('onBlur')('Test');

    expect(finishSpy).toBeCalledWith();
});
