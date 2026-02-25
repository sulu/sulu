// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import PageSettingsShadowLocaleSelect from '../../fields/PageSettingsShadowLocaleSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Pass correct props to SingleSelect', () => {
    const formInspector: any = {
        getValueByPath: jest.fn((path) => path === '/contentLocales' && ['en', 'de', 'nl']),
        locale: observable.box('en'),
    };
    render(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value="de"
        />
    );

    expect(screen.getByRole('button')).toBeDisabled();
    expect(screen.getByText('de')).toBeInTheDocument();
});

test('Render available shadow-locales as options', async() => {
    const user = userEvent.setup();
    const formInspector: any = {
        getValueByPath: jest.fn((path) => path === '/contentLocales' && ['en', 'de', 'nl']),
        locale: observable.box('en'),
    };

    render(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value="de"
        />
    );

    await user.click(screen.getByRole('button'));

    expect(await screen.findByRole('button', {name: 'nl'})).toBeInTheDocument();
});

test('Pass correct props to SingleSelect when no shadow-locale exists', () => {
    const formInspector: any = {
        getValueByPath: jest.fn((path) => path === '/contentLocales' && ['de']),
        locale: observable.box('de'),
    };
    render(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(screen.getByRole('button')).toBeDisabled();
    expect(screen.getByText('sulu_admin.please_choose')).toBeInTheDocument();
});

test('Call onChange and onFinish if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector: any = {
        getValueByPath: jest.fn((path) => path === '/contentLocales' && ['en', 'de', 'nl']),
        locale: observable.box('nl'),
    };
    render(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="de"
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('en'));

    expect(changeSpy).toBeCalledWith('en');
    expect(finishSpy).toBeCalledWith();
});
