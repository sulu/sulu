// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import {SingleSelect} from 'sulu-admin-bundle/components';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import PageSettingsShadowLocaleSelect from '../../fields/PageSettingsShadowLocaleSelect';

jest.mock('sulu-admin-bundle/components', () => {
    const SingleSelect: any = jest.fn(() => null);
    SingleSelect.Option = jest.fn(() => null);

    return {SingleSelect};
});

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

    const singleSelectProps: any = getLatestMockProps((SingleSelect: any));
    const optionNodes = React.Children.toArray(singleSelectProps.children).filter(Boolean);

    expect(singleSelectProps.disabled).toEqual(true);
    expect(singleSelectProps.value).toEqual('de');
    expect(optionNodes[0].props.children).toEqual('de');
    expect(optionNodes[0].props.value).toEqual('de');
    expect(optionNodes[1].props.children).toEqual('nl');
    expect(optionNodes[1].props.value).toEqual('nl');
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

    const singleSelectProps: any = getLatestMockProps((SingleSelect: any));
    const optionNodes = React.Children.toArray(singleSelectProps.children).filter(Boolean);

    expect(singleSelectProps.disabled).toEqual(true);
    expect(singleSelectProps.value).toEqual(undefined);
    expect(optionNodes).toHaveLength(0);
});

test('Call onChange and onFinish if the value is changed', () => {
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

    const singleSelectProps: any = getLatestMockProps((SingleSelect: any));
    singleSelectProps.onChange('en');
    expect(changeSpy).toBeCalledWith('en');
    expect(finishSpy).toBeCalledWith();
});
