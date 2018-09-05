// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import PasswordConfirmation from '../../fields/PasswordConfirmation';
import PasswordConfirmationComponent from '../../../../components/PasswordConfirmation';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to PasswordConfirmation component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const error = {keyword: 'required', parameters: {}};

    const passwordConfirmation = shallow(
        <PasswordConfirmation
            dataPath=""
            fieldTypeOptions={{}}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            error={error}
            formInspector={formInspector}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    expect(passwordConfirmation.find(PasswordConfirmationComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to PasswordConfirmation component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const passwordConfirmation = shallow(
        <PasswordConfirmation
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    expect(passwordConfirmation.find(PasswordConfirmationComponent).prop('valid')).toBe(true);

    passwordConfirmation.find(PasswordConfirmationComponent).simulate('change', 'value');

    expect(changeSpy).toBeCalledWith('value');
    expect(finishSpy).toBeCalledWith();
});
