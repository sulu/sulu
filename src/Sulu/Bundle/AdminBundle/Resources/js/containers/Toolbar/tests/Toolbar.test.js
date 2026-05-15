// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Toolbar from '../Toolbar';
import toolbarStorePool from '../stores/toolbarStorePool';

let toolbarStoreMock = {};

jest.mock('../stores/toolbarStorePool', () => ({
    createStore: jest.fn(),
    getStore: jest.fn(),
    hasStore: jest.fn(),
}));

jest.mock('../../../services/initializer', () => ({
    initializedTranslationsLocale: true,
}));

beforeEach(() => {
    toolbarStoreMock = {
        errors: [],
        warnings: [],
        showSuccess: false,
        getBackButtonConfig: jest.fn(),
        getItemsConfig: jest.fn().mockReturnValue([]),
        getIconsConfig: jest.fn().mockReturnValue([]),
        getLocaleConfig: jest.fn(),
    };
});

test('Render the items and icons from the ToolbarStore', () => {
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue(undefined);

    toolbarStoreMock.getIconsConfig.mockReturnValue(
        [
            <p key={1}>Test1</p>,
            <p key={2}>Test2</p>,
        ]
    );

    toolbarStoreMock.getItemsConfig.mockReturnValue(
        [
            {
                type: 'button',
                label: 'Delete',
                disabled: true,
                icon: 'fa-trash-o',
            },
            {
                type: 'dropdown',
                label: 'Save',
                icon: 'fa-floppy-more',
                options: [
                    {
                        label: 'Save as draft',
                        onClick: () => {},
                    },
                    {
                        label: 'Publish',
                        onClick: () => {},
                    },
                    {
                        label: 'Save and publish',
                        onClick: () => {},
                    },
                ],
            },
            {
                type: 'toggler',
                label: 'Toggler',
                onClick: () => {},
                value: true,
            },
        ]
    );

    const {asFragment} = render(<Toolbar storeKey={storeKey} />);

    expect(asFragment()).toMatchSnapshot();
    expect(toolbarStorePool.createStore).toBeCalledWith(storeKey);
});

test('Render the error from the ToolbarStore', () => {
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue(undefined);
    toolbarStoreMock.errors.push('Something went wrong');

    toolbarStoreMock.getIconsConfig.mockReturnValue(
        [
            <p key={1}>Test1</p>,
            <p key={2}>Test2</p>,
        ]
    );

    const {asFragment} = render(<Toolbar storeKey={storeKey} />);

    expect(asFragment()).toMatchSnapshot();
    expect(toolbarStorePool.createStore).toBeCalledWith(storeKey);
});

test('Render the warning from the ToolbarStore', () => {
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue(undefined);
    toolbarStoreMock.warnings.push('Something unimportant went wrong');

    toolbarStoreMock.getIconsConfig.mockReturnValue(
        [
            <p key={1}>Test1</p>,
            <p key={2}>Test2</p>,
        ]
    );

    const {asFragment} = render(<Toolbar storeKey={storeKey} />);

    expect(asFragment()).toMatchSnapshot();
    expect(toolbarStorePool.createStore).toBeCalledWith(storeKey);
});

test('Render the items as disabled if one is loading', () => {
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});

    toolbarStoreMock.getItemsConfig.mockReturnValue(
        [
            {
                type: 'button',
                label: 'Add',
                icon: 'fa-add-o',
                disabled: false,
            },
            {
                type: 'button',
                label: 'Delete',
                icon: 'fa-trash-o',
                loading: true,
            },
        ]
    );

    render(<Toolbar storeKey={storeKey} />);
    expect(toolbarStorePool.createStore).toBeCalledWith(storeKey);

    const buttons = screen.getAllByRole('button').filter((element) => element.tagName === 'BUTTON');
    expect(buttons).toHaveLength(3);
    expect(buttons[0]).toBeDisabled();
    expect(buttons[1]).toBeDisabled();
    expect(buttons[2]).toBeDisabled();
});

test('Show success message on back button for some time', () => {
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.showSuccess = true;

    const {asFragment} = render(<Toolbar storeKey={storeKey} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Show success message on navigation button for some time', () => {
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.showSuccess = true;

    const {asFragment} = render(<Toolbar onNavigationButtonClick={jest.fn()} storeKey={storeKey} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Click on the success message should open the navigation', async() => {
    const user = userEvent.setup();
    const storeKey = 'testStore';
    const navigationButtonClickSpy = jest.fn();

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.showSuccess = true;

    render(<Toolbar onNavigationButtonClick={navigationButtonClickSpy} storeKey={storeKey} />);
    await user.click(screen.getByRole('button', {name: 'su-check'}));

    expect(navigationButtonClickSpy).toBeCalledWith();
});

test('Click on the success message should navigate back', async() => {
    const user = userEvent.setup();
    const storeKey = 'testStore';
    const backSpy = jest.fn();

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({
        onClick: backSpy,
    });
    toolbarStoreMock.showSuccess = true;

    render(<Toolbar storeKey={storeKey} />);
    await user.click(screen.getByRole('button', {name: 'su-check'}));

    expect(backSpy).toBeCalledWith();
});

test('Remove last error if close button on snackbar is clicked', async() => {
    const user = userEvent.setup();
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.errors.push('Something went wrong');

    toolbarStoreMock.getItemsConfig.mockReturnValue(
        [
            {
                type: 'button',
                label: 'Add',
                icon: 'fa-add-o',
                disabled: false,
            },
        ]
    );

    render(<Toolbar storeKey={storeKey} />);

    expect(toolbarStoreMock.errors).toHaveLength(1);
    await user.click(screen.getByLabelText('su-times'));
    expect(toolbarStoreMock.errors).toHaveLength(0);
});
