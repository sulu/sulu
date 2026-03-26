// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import PageSettingsShadowLocaleSelect from '../../fields/PageSettingsShadowLocaleSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector(locale: string, contentLocales: Array<string>) {
    return ({
        getValueByPath: jest.fn((path) => (path === '/contentLocales' ? contentLocales : undefined)),
        locale: observable.box(locale),
    }: any);
}

test('Pass correct props to SingleSelect', () => {
    const formInspector = createFormInspector('en', ['en', 'de', 'nl']);

    render(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value="de"
        />
    );

    expect(formInspector.getValueByPath).toBeCalledWith('/contentLocales');
    expect(screen.getByRole('button', {name: /^de/})).toBeDisabled();
});

test('Pass correct props to SingleSelect when no shadow-locale exists', () => {
    const formInspector = createFormInspector('de', ['de']);

    render(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(formInspector.getValueByPath).toBeCalledWith('/contentLocales');
    expect(screen.getByRole('button', {name: /sulu_admin\.please_choose/})).toBeDisabled();
});

test('Call onChange and onFinish if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = createFormInspector('nl', ['en', 'de', 'nl']);

    render(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="de"
        />
    );

    await user.click(screen.getByRole('button', {name: /^de/}));
    await user.click(screen.getByRole('button', {name: /^en/}));

    expect(changeSpy).toBeCalledWith('en');
    expect(finishSpy).toBeCalledWith();
});
