// @flow
import {render, screen} from '@testing-library/react';
import {observable} from 'mobx';
import React from 'react';
import {ResourceLocator, SingleSelection} from 'sulu-admin-bundle/containers';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import PageTreeRoute from '../../fields/PageTreeRoute';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'de',
}));

jest.mock('sulu-admin-bundle/containers', () => ({
    SingleSelection: jest.fn(({value}) => {
        return (
            <div data-testid="single-selection">
                {value || 'no-page-selected'}
            </div>
        );
    }),
    ResourceLocator: jest.fn(({value}) => {
        return (
            <div data-testid="resource-locator">
                {value || 'no-suffix-selected'}
            </div>
        );
    }),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector(locale: any = undefined) {
    return ({locale}: any);
}

test('Render a PageTreeRoute', async() => {
    const modeResolver = jest.fn(() => Promise.resolve('leaf'));
    const onChange = jest.fn();
    const onFinish = jest.fn();
    const value = {
        page: {
            uuid: 'uuid-uuid-uuid-uuid',
        },
        suffix: '/hello',
    };
    const locale = observable.box('de');

    render(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{modeResolver}}
            formInspector={createFormInspector(locale)}
            onChange={onChange}
            onFinish={onFinish}
            value={value}
        />
    );

    expect(modeResolver).toHaveBeenCalled();
    expect(await screen.findByTestId('single-selection')).toBeInTheDocument();

    expect(SingleSelection).toHaveBeenCalledTimes(1);
    expect(ResourceLocator).toHaveBeenCalledTimes(1);

    const [singleSelectionProps] = (SingleSelection: any).mock.calls[0];
    const [resourceLocatorProps] = (ResourceLocator: any).mock.calls[0];

    expect(singleSelectionProps.value).toBe('uuid-uuid-uuid-uuid');
    expect(singleSelectionProps.locale).toBe(locale);
    expect(resourceLocatorProps.value).toBe('/hello');

    singleSelectionProps.onChange('uuid-next', {url: '/next-path'});
    expect(onChange).toHaveBeenCalledWith({
        page: {
            path: '/next-path',
            uuid: 'uuid-next',
        },
        suffix: '/hello',
    });
    expect(onFinish).toHaveBeenCalled();

    resourceLocatorProps.onChange('/changed-suffix');
    expect(onChange).toHaveBeenCalledWith({
        page: {
            uuid: 'uuid-uuid-uuid-uuid',
        },
        suffix: '/changed-suffix',
    });
});

test('Render a PageTreeRoute without value', async() => {
    const modeResolver = jest.fn(() => Promise.resolve('leaf'));
    const onChange = jest.fn();
    const onFinish = jest.fn();

    render(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{modeResolver}}
            formInspector={createFormInspector()}
            onChange={onChange}
            onFinish={onFinish}
            value={null}
        />
    );

    expect(modeResolver).toHaveBeenCalled();
    expect(await screen.findByTestId('single-selection')).toBeInTheDocument();

    const [singleSelectionProps] = (SingleSelection: any).mock.calls[0];
    const [resourceLocatorProps] = (ResourceLocator: any).mock.calls[0];

    expect(singleSelectionProps.value).toBe(null);
    expect(singleSelectionProps.locale.get()).toBe('de');
    expect(resourceLocatorProps.value).toBe(null);

    singleSelectionProps.onChange('uuid-next', {url: '/next-path'});
    expect(onChange).toHaveBeenCalledWith({
        page: {
            path: '/next-path',
            uuid: 'uuid-next',
        },
    });
    expect(onFinish).toHaveBeenCalled();
});
