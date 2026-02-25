// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import {fieldTypeDefaultProps, getLatestMockProps} from 'sulu-admin-bundle/utils/TestHelper';
import {userStore} from 'sulu-admin-bundle/stores';
import TeaserSelection from '../../fields/TeaserSelection';

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: undefined,
}));

jest.mock('../../../../containers/TeaserSelection', () => {
    const TeaserSelectionMock = jest.fn(() => <div />);

    return {
        __esModule: true,
        default: TeaserSelectionMock,
        teaserProviderRegistry: {
            get: jest.fn(),
            keys: [],
        },
    };
});

const teaserSelectionModule = ((jest.requireMock('../../../../containers/TeaserSelection'): any): {
    default: {
        mock: {calls: Array<[Object]>},
        ...
    },
    teaserProviderRegistry: {
        get: Function,
        ...
    },
    ...
});
const teaserSelectionComponent = teaserSelectionModule.default;
const teaserProviderRegistry = teaserSelectionModule.teaserProviderRegistry;
const mockedUserStore: any = userStore;

beforeEach(() => {
    jest.clearAllMocks();
    mockedUserStore.contentLocale = undefined;
});

test('Pass props correctly to component', () => {
    const changeSpy = jest.fn();
    const value = {
        presentAs: undefined,
        items: [],
    };
    const formInspector: any = {locale: observable.box('en')};

    const {asFragment} = render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            value={value}
        />
    );

    expect(asFragment()).toMatchSnapshot();
    const teaserSelectionProps = getLatestMockProps(teaserSelectionComponent);
    expect(teaserSelectionProps.disabled).toEqual(undefined);
    expect(teaserSelectionProps.onChange).toEqual(expect.any(Function));
    expect(teaserSelectionProps.onItemClick).toEqual(expect.any(Function));
    expect(teaserSelectionProps.presentations).toEqual(undefined);
    expect(teaserSelectionProps.value).toEqual(value);
    expect(teaserSelectionProps.locale.get ? teaserSelectionProps.locale.get() : teaserSelectionProps.locale)
        .toEqual('en');
});

test('Pass disabled value from props to component', () => {
    mockedUserStore.contentLocale = 'de';
    const formInspector: any = {};

    render(<TeaserSelection {...fieldTypeDefaultProps} disabled={true} formInspector={formInspector} />);

    expect(getLatestMockProps(teaserSelectionComponent).disabled).toEqual(true);
});

test('Pass locale from userStore when form has no locale', () => {
    mockedUserStore.contentLocale = 'de';
    const formInspector: any = {};

    render(<TeaserSelection {...fieldTypeDefaultProps} formInspector={formInspector} />);

    expect(getLatestMockProps(teaserSelectionComponent).locale.get()).toEqual('de');
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
    const formInspector: any = {};

    render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(getLatestMockProps(teaserSelectionComponent).presentations).toEqual([
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

    const router: any = {
        navigate: jest.fn(),
    };
    const formInspector: any = {};

    render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
            value={{
                presentAs: '',
                items: [
                    {
                        id: 5,
                        type: 'pages',
                    },
                ],
            }}
        />
    );

    getLatestMockProps(teaserSelectionComponent).onItemClick(5, {
        attributes: {webspaceKey: 'sulu_io'},
        id: 5,
        type: 'pages',
    });

    expect(router.navigate).toHaveBeenCalledWith('sulu_page.page_edit_form', {
        id: 5,
        webspace: 'sulu_io',
    });
});

test('Throw error if present_as schemaOption is from wrong type', () => {
    const schemaOptions = {
        present_as: {
            name: 'present_as',
            value: 'test',
        },
    };
    const formInspector: any = {};

    expect(
        () => render(
            <TeaserSelection {...fieldTypeDefaultProps} formInspector={formInspector} schemaOptions={schemaOptions} />
        )
    ).toThrow(/present_as/);
});

test('Should call onChange and onFinish callback when TeaserSelection container fires onChange callback', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector: any = {};

    render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    getLatestMockProps(teaserSelectionComponent).onChange({
        presentAs: undefined,
        items: [],
    });

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [],
    });
    expect(finishSpy).toBeCalledWith();
});
