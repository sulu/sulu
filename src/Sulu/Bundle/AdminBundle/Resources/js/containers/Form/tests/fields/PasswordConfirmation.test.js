// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import PasswordConfirmation from '../../fields/PasswordConfirmation';
import PasswordConfirmationComponent from '../../../../components/PasswordConfirmation';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to PasswordConfirmation component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {keyword: 'required', parameters: {}};

    const passwordConfirmation = shallow(
        <PasswordConfirmation
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(passwordConfirmation.find(PasswordConfirmationComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to PasswordConfirmation component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const passwordConfirmation = shallow(
        <PasswordConfirmation
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    expect(passwordConfirmation.find(PasswordConfirmationComponent).prop('valid')).toBe(true);
    expect(passwordConfirmation.find(PasswordConfirmationComponent).prop('disabled')).toBe(true);

    passwordConfirmation.find(PasswordConfirmationComponent).simulate('change', 'value');

    expect(changeSpy).toBeCalledWith('value');
    expect(finishSpy).toBeCalledWith();
});
