// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import TwoFactorForm from '../TwoFactorForm';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(function(key) {
        return key;
    }),
}));

test('Should render the component', () => {
    expect(render(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should render the component error', () => {
    expect(render(
        <TwoFactorForm
            error={true}
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should trigger onChangeForm correctly', () => {
    const onChangeForm = jest.fn();
    const form = shallow(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
        />
    );

    form.find('Button').at(0).simulate('click');

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if autCode is missing', () => {
    const onSubmit = jest.fn();
    const form = shallow(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const event = {
        preventDefault: jest.fn(),
    };

    form.find('form').prop('onSubmit')(event);

    expect(event.preventDefault).toBeCalledWith();
    expect(onSubmit).not.toBeCalled();
});

test('Should trigger onSubmit correctly', () => {
    const onSubmit = jest.fn();
    const form = shallow(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const event = {
        preventDefault: jest.fn(),
    };

    form.find('Input[icon="su-lock"]').at(0).prop('onChange')('authcode');
    form.find('form').prop('onSubmit')(event);

    expect(event.preventDefault).toBeCalledWith();
    expect(onSubmit).toBeCalledWith({_auth_code: 'authcode', _trusted: false});
});

test('Should trigger onSubmit correctly with trusted device', () => {
    const onSubmit = jest.fn();
    const form = shallow(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const event = {
        preventDefault: jest.fn(),
    };

    form.find('Input[icon="su-lock"]').at(0).prop('onChange')('authcode');
    form.find('Checkbox').at(0).prop('onChange')(true);
    form.find('form').prop('onSubmit')(event);

    expect(event.preventDefault).toBeCalledWith();
    expect(onSubmit).toBeCalledWith({_auth_code: 'authcode', _trusted: true});
});
