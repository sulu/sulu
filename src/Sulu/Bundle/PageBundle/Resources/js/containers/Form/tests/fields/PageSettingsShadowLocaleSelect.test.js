// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import PageSettingsShadowLocaleSelect from '../../fields/PageSettingsShadowLocaleSelect';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.options = formStore.options;
        this.getValueByPath = jest.fn();
        this.locale = formStore.locale;
    }),
    ResourceFormStore: jest.fn(function(resourceStore, options) {
        this.options = options;
        this.locale = resourceStore.locale;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, options) {
        this.locale = options.locale;
    }),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

test('Pass correct props to SingleSelect', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    formInspector.getValueByPath.mockImplementation((path) => {
        if (path === '/contentLocales') {
            return ['en', 'de', 'nl'];
        }
    });

    const {rerender} = render(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value="de"
        />
    );

    expect(screen.getByRole('button', {name: /de/})).toBeDisabled();

    rerender(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={false}
            formInspector={formInspector}
            value="de"
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(screen.getAllByRole('button', {name: /de/})).toHaveLength(2);
    expect(screen.getByRole('button', {name: 'nl'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'en'})).not.toBeInTheDocument();
});

test('Pass correct props to SingleSelect when no shadow-locale exists', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('de')}),
            'test'
        )
    );
    formInspector.getValueByPath.mockImplementation((path) => {
        if (path === '/contentLocales') {
            return ['de'];
        }
    });

    const {rerender} = render(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(screen.getByRole('button', {name: /sulu_admin.please_choose/})).toBeDisabled();

    rerender(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={false}
            formInspector={formInspector}
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(screen.queryByRole('button', {name: 'de'})).not.toBeInTheDocument();
});

test('Call onChange and onFinish if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('nl')}),
            'test'
        )
    );
    formInspector.getValueByPath.mockImplementation((path) => {
        if (path === '/contentLocales') {
            return ['en', 'de', 'nl'];
        }
    });

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
    await user.click(screen.getByRole('button', {name: 'en'}));

    expect(changeSpy).toHaveBeenCalledWith('en');
    expect(finishSpy).toHaveBeenCalledWith();
});
