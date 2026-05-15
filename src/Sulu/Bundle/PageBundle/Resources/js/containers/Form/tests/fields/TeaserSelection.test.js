// @flow
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import TeaserSelection from '../../fields/TeaserSelection';
import TeaserSelectionComponent, {teaserProviderRegistry} from '../../../../containers/TeaserSelection';

jest.mock('../../../../containers/TeaserSelection', () => {
    const TeaserSelectionMock = jest.fn(() => null);

    return {
        __esModule: true,
        default: TeaserSelectionMock,
        teaserProviderRegistry: {
            get: jest.fn(),
            keys: [],
        },
    };
});

jest.mock('sulu-admin-bundle/stores', () => ({
    userStore: {
        contentLocale: 'de',
    },
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector(locale: any = undefined) {
    return ({
        locale,
    }: any);
}

test('Pass props correctly to component', () => {
    const changeSpy = jest.fn();
    const value = {
        presentAs: undefined,
        items: [],
    };
    const formInspector = createFormInspector(observable.box('en'));

    render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            value={value}
        />
    );

    const [teaserSelectionProps] = (TeaserSelectionComponent: any).mock.calls[0];
    expect(teaserSelectionProps.disabled).toEqual(undefined);
    expect(teaserSelectionProps.locale.get()).toEqual('en');
    expect(teaserSelectionProps.presentations).toBe(undefined);
    expect(teaserSelectionProps.value).toBe(value);
});

test('Pass disabled value from props to component', () => {
    render(<TeaserSelection {...fieldTypeDefaultProps} disabled={true} formInspector={createFormInspector()} />);

    const [teaserSelectionProps] = (TeaserSelectionComponent: any).mock.calls[0];
    expect(teaserSelectionProps.disabled).toEqual(true);
});

test('Pass locale from userStore when form has no locale', () => {
    render(<TeaserSelection {...fieldTypeDefaultProps} formInspector={createFormInspector()} />);

    const [teaserSelectionProps] = (TeaserSelectionComponent: any).mock.calls[0];
    expect(teaserSelectionProps.locale.get()).toEqual('de');
});

test('Pass presentations prop correctly to component', () => {
    const schemaOptions = {
        present_as: {
            name: 'present_as',
            value: [
                {name: 'test-1', title: 'Test 1'},
                {name: 'test-2', title: 'Test 2'},
            ],
        },
    };

    render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            schemaOptions={schemaOptions}
        />
    );

    const [teaserSelectionProps] = (TeaserSelectionComponent: any).mock.calls[0];
    expect(teaserSelectionProps.presentations).toEqual([
        {label: 'Test 1', value: 'test-1'},
        {label: 'Test 2', value: 'test-2'},
    ]);
});

test('Navigate to item when item is clicked', () => {
    teaserProviderRegistry.get.mockReturnValue({
        resultToView: {'attributes/webspaceKey': 'webspace', id: 'id'},
        title: 'Pages',
        view: 'sulu_page.page_edit_form',
    });

    const value = {
        presentAs: '',
        items: [
            {
                id: 5,
                type: 'pages',
            },
            {
                id: 2,
                type: 'pages',
            },
        ],
    };
    const router = ({navigate: jest.fn()}: any);

    render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            router={router}
            value={value}
        />
    );

    const [teaserSelectionProps] = (TeaserSelectionComponent: any).mock.calls[0];
    teaserSelectionProps.onItemClick(5, {attributes: {webspaceKey: 'sulu_io'}, id: 5, type: 'pages'});
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_page.page_edit_form', {id: 5, webspace: 'sulu_io'});

    teaserSelectionProps.onItemClick(2, {attributes: {webspaceKey: 'sulu_blog'}, id: 2, type: 'pages'});
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_page.page_edit_form', {id: 2, webspace: 'sulu_blog'});
});

test('Throw error if present_as schemaOption is from wrong type', () => {
    const schemaOptions = {
        present_as: {
            name: 'present_as',
            value: 'test',
        },
    };
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(
            () => render(
                <TeaserSelection
                    {...fieldTypeDefaultProps}
                    formInspector={createFormInspector()}
                    schemaOptions={schemaOptions}
                />
            )
        ).toThrow(/present_as/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
});

test('Should call onChange and onFinish callback when TeaserSelection container fires onChange callback', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const [teaserSelectionProps] = (TeaserSelectionComponent: any).mock.calls[0];
    teaserSelectionProps.onChange({
        presentAs: undefined,
        items: [],
    });

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [],
    });
    expect(finishSpy).toBeCalledWith();
});
