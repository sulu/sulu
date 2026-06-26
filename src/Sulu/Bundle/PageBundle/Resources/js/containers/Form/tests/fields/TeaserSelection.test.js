// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore, userStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {Router} from 'sulu-admin-bundle/services';
import TeaserSelection from '../../fields/TeaserSelection';
import TeaserSelectionComponent, {teaserProviderRegistry} from '../../../../containers/TeaserSelection';

let mockTeaserSelectionProps: Object = {};

const mockReact = require('react');

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({}));

jest.mock('../../../../containers/TeaserSelection', () => {
    const teaserProviderRegistry = {
        get: jest.fn(),
        keys: [],
    };

    const TeaserSelection = jest.fn((props) => {
        mockTeaserSelectionProps = props;

        return mockReact.createElement(
            'div',
            {'data-testid': 'teaser-selection'},
            mockReact.createElement(
                'button',
                {onClick: () => props.onChange({items: [], presentAs: undefined}), type: 'button'},
                'change-teasers'
            ),
            mockReact.createElement(
                'button',
                {
                    onClick: () => props.onItemClick('pages;5', {
                        attributes: {
                            webspaceKey: 'sulu_io',
                        },
                        id: 5,
                        title: 'Test 1',
                        type: 'pages',
                    }),
                    type: 'button',
                },
                'open-teaser'
            )
        );
    });

    return {
        __esModule: true,
        default: TeaserSelection,
        teaserProviderRegistry,
    };
});

beforeEach(() => {
    mockTeaserSelectionProps = {};
    (TeaserSelectionComponent: any).mockClear();
    teaserProviderRegistry.get.mockReset();
});

test('Pass props correctly to component', () => {
    const changeSpy = jest.fn();
    const value = {
        presentAs: undefined,
        items: [],
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore(
                'test',
                undefined,
                {locale: observable.box('en')}
            ),
            'snippets'
        )
    );

    render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            value={value}
        />
    );

    expect(TeaserSelectionComponent).toHaveBeenCalled();
    expect(mockTeaserSelectionProps.disabled).toEqual(undefined);
    expect(mockTeaserSelectionProps.locale.get()).toEqual('en');
    expect(mockTeaserSelectionProps.presentations).toBe(undefined);
    expect(mockTeaserSelectionProps.value).toBe(value);
});

test('Pass disabled value from props to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

    render(<TeaserSelection {...fieldTypeDefaultProps} disabled={true} formInspector={formInspector} />);

    expect(mockTeaserSelectionProps.disabled).toEqual(true);
});

test('Pass locale from userStore when form has no locale', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

    render(<TeaserSelection {...fieldTypeDefaultProps} formInspector={formInspector} />);

    expect(mockTeaserSelectionProps.locale.get()).toEqual('de');
});

test('Pass presentations prop correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

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
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockTeaserSelectionProps.presentations).toEqual([
        {label: 'Test 1', value: 'test-1'},
        {label: 'Test 2', value: 'test-2'},
    ]);
});

test('Navigate to item when item is clicked', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

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

    const router = new Router();

    render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
            value={value}
        />
    );

    await user.click(screen.getByRole('button', {name: 'open-teaser'}));

    expect(router.navigate).toHaveBeenLastCalledWith('sulu_page.page_edit_form', {id: 5, webspace: 'sulu_io'});
});

test('Throw error if present_as schemaOption is from wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

    const schemaOptions = {
        present_as: {
            name: 'present_as',
            value: 'test',
        },
    };

    const teaserSelection = new TeaserSelection({...fieldTypeDefaultProps, formInspector, schemaOptions});

    expect(() => teaserSelection.render()).toThrow(/present_as/);
});

test('Should call onChange and onFinish callback when TeaserSelection container fires onChange callback', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    await user.click(screen.getByRole('button', {name: 'change-teasers'}));

    expect(changeSpy).toHaveBeenCalledWith({
        presentAs: undefined,
        items: [],
    });
    expect(finishSpy).toHaveBeenCalledWith();
});
