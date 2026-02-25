// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {MultiSelect} from 'sulu-admin-bundle/components';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import webspaceStore from '../../../../stores/webspaceStore';
import PageSettingsNavigationSelect from '../../fields/PageSettingsNavigationSelect';

jest.mock('mobx-react', () => ({
    observer: (Component) => Component,
}));

jest.mock('sulu-admin-bundle/components', () => {
    const MultiSelect: any = jest.fn(() => null);
    MultiSelect.Option = jest.fn(() => null);

    return {MultiSelect};
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
}));

test('Pass correct props to MultiSelect', () => {
    const webspace = {
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const formInspector: any = {
        options: {
            webspace: 'sulu_io',
        },
    };

    render(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={['footer']}
        />
    );

    const multiSelectProps: any = getLatestMockProps((MultiSelect: any));
    const optionNodes = React.Children.toArray(multiSelectProps.children);

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');
    expect(multiSelectProps.disabled).toEqual(true);
    expect(multiSelectProps.values).toEqual(['footer']);
    expect(optionNodes[0].props.children).toEqual('Main Navigation');
    expect(optionNodes[0].props.value).toEqual('main');
    expect(optionNodes[1].props.children).toEqual('Footer Navigation');
    expect(optionNodes[1].props.value).toEqual('footer');
});

test('Call onChange and onBlur if the value is changed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const webspace = {
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const formInspector: any = {
        options: {
            webspace: 'sulu_io',
        },
    };

    render(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['footer']}
        />
    );

    const multiSelectProps: any = getLatestMockProps((MultiSelect: any));
    multiSelectProps.onChange(['footer', 'main']);
    expect(changeSpy).toBeCalledWith(['footer', 'main']);
    expect(finishSpy).toBeCalledWith();
});
