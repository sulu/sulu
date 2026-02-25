// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import {fieldTypeDefaultProps, getLatestMockProps} from 'sulu-admin-bundle/utils/TestHelper';
import {userStore} from 'sulu-admin-bundle/stores';
import PageTreeRoute from '../../fields/PageTreeRoute';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'de',
}));

jest.mock('sulu-admin-bundle/containers', () => ({
    SingleSelection: jest.fn(() => null),
}));

jest.mock('sulu-route-bundle/containers', () => ({
    ResourceLocator: jest.fn(() => null),
}));

const singleSelectionComponent = ((jest.requireMock('sulu-admin-bundle/containers'): any).SingleSelection: {
    mock: {calls: Array<[Object]>},
    ...
});
const resourceLocatorComponent = ((jest.requireMock('sulu-route-bundle/containers'): any).ResourceLocator: {
    mock: {calls: Array<[Object]>},
    ...
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render a PageTreeRoute', () => {
    const fieldTypeOptions = {
        defaultMode: 'tree_leaf_edit',
    };
    const value = {
        page: {
            uuid: 'uuid-uuid-uuid-uuid',
        },
        suffix: '/hello',
    };
    const locale = observable.box('de');
    const formInspector: any = {locale};
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const {asFragment} = render(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    expect(asFragment()).toMatchSnapshot();

    expect(singleSelectionComponent).toHaveBeenCalledWith(expect.objectContaining({
        adapter: 'column_list',
        disabled: false,
        listKey: 'pages',
        locale,
        value: value.page.uuid,
    }), {});
    expect(resourceLocatorComponent).toHaveBeenCalledWith(expect.objectContaining({
        disabled: false,
        value: value.suffix,
        fieldTypeOptions: expect.objectContaining({
            defaultMode: 'tree_leaf_edit',
            historyResourceKey: 'route_histories',
            options: {
                history: true,
            },
            requestParameters: {
                parentId: value.page.uuid,
                parentKey: 'pages',
                relative: true,
            },
        }),
    }), {});

    getLatestMockProps(singleSelectionComponent).onChange('new-uuid', {url: '/test/new'});
    expect(changeSpy).toHaveBeenCalledWith({
        page: {
            path: '/test/new',
            uuid: 'new-uuid',
        },
        suffix: '/hello',
    });
    expect(finishSpy).toHaveBeenCalledTimes(1);

    getLatestMockProps(resourceLocatorComponent).onChange('/changed');
    expect(changeSpy).toHaveBeenCalledWith({
        page: {
            uuid: 'uuid-uuid-uuid-uuid',
        },
        suffix: '/changed',
    });
    expect(finishSpy).toHaveBeenCalledTimes(2);
});

test('Render a PageTreeRoute without value', () => {
    const fieldTypeOptions = {
        defaultMode: 'tree_leaf_edit',
    };

    const formInspector: any = {};

    const {asFragment} = render(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={null}
        />
    );

    expect(asFragment()).toMatchSnapshot();

    const singleSelectionProps = getLatestMockProps(singleSelectionComponent);
    expect(singleSelectionProps.value).toEqual(null);
    expect(singleSelectionProps.locale.get()).toEqual(userStore.contentLocale);

    expect(resourceLocatorComponent).toHaveBeenCalledWith(expect.objectContaining({
        disabled: true,
        value: null,
    }), {});
});
