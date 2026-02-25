// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import getMockCallArg from '../../../utils/TestHelper/getMockCallArg';
import Toolbar from '../Toolbar';
import toolbarStorePool from '../stores/toolbarStorePool';

let toolbarStoreMock = {};

jest.mock('../stores/toolbarStorePool', () => ({
    createStore: jest.fn(),
    getStore: jest.fn(),
    hasStore: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../services/initializer', () => ({
    initializedTranslationsLocale: true,
}));

jest.mock('debounce', () => jest.fn((callback) => {
    const debounced = (...args) => callback(...args);
    debounced.clear = jest.fn();

    return debounced;
}));

jest.mock('../../../components/Snackbar', () => {
    const React = require('react');

    return jest.fn(function SnackbarMock({onCloseClick, type, visible}) {
        if (!visible) {
            return null;
        }

        return React.createElement(
            'button',
            {'data-testid': `snackbar-${type}`, onClick: onCloseClick, type: 'button'},
            type
        );
    });
});

jest.mock('../../../components/Toolbar', () => {
    const React = require('react');

    const ToolbarComponent: any = jest.fn(function ToolbarComponentMock({children}) {
        return React.createElement('div', {'data-testid': 'toolbar-root'}, children);
    });

    ToolbarComponent.Controls = jest.fn(function ControlsMock({children}) {
        return React.createElement('div', {'data-testid': 'toolbar-controls'}, children);
    });

    ToolbarComponent.Button = jest.fn(function ButtonMock({onClick, success}) {
        return React.createElement(
            'button',
            {
                'data-testid': success ? 'toolbar-success-button' : 'toolbar-button',
                onClick,
                type: 'button',
            }
        );
    });

    ToolbarComponent.Dropdown = jest.fn(function DropdownMock() {
        return React.createElement('div', {'data-testid': 'toolbar-dropdown'});
    });

    ToolbarComponent.Icons = jest.fn(function IconsMock({children}) {
        return React.createElement('div', {'data-testid': 'toolbar-icons'}, children);
    });

    ToolbarComponent.Items = jest.fn(function ItemsMock({children}) {
        return React.createElement('div', {'data-testid': 'toolbar-items'}, children);
    });

    ToolbarComponent.Select = jest.fn(function SelectMock() {
        return React.createElement('div', {'data-testid': 'toolbar-select'});
    });

    ToolbarComponent.Toggler = jest.fn(function TogglerMock() {
        return React.createElement('div', {'data-testid': 'toolbar-toggler'});
    });

    return ToolbarComponent;
});

const toolbarComponent = (jest.requireMock('../../../components/Toolbar'): any);

function getButtonProps(index: number): any {
    return getMockCallArg(toolbarComponent.Button, index, 0);
}

beforeEach(() => {
    jest.clearAllMocks();

    window.ResizeObserver = jest.fn(function() {
        this.observe = jest.fn();
        this.disconnect = jest.fn();
    });

    toolbarStoreMock = {
        disableAll: false,
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
            {type: 'button', label: 'Delete', disabled: true, icon: 'fa-trash-o'},
            {
                type: 'dropdown',
                label: 'Save',
                icon: 'fa-floppy-more',
                options: [
                    {label: 'Save as draft', onClick: jest.fn()},
                    {label: 'Publish', onClick: jest.fn()},
                    {label: 'Save and publish', onClick: jest.fn()},
                ],
            },
            {type: 'toggler', label: 'Toggler', onClick: jest.fn(), value: true},
        ]
    );

    const {asFragment} = render(<Toolbar storeKey={storeKey} />);
    expect(asFragment()).toMatchSnapshot();
    expect(toolbarStorePool.createStore).toHaveBeenCalledWith(storeKey);
});

test('Render the error from the ToolbarStore', () => {
    const storeKey = 'testStore';
    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue(undefined);
    toolbarStoreMock.errors.push('Something went wrong');
    toolbarStoreMock.getIconsConfig.mockReturnValue([<p key={1}>Test1</p>, <p key={2}>Test2</p>]);

    const {asFragment} = render(<Toolbar storeKey={storeKey} />);
    expect(asFragment()).toMatchSnapshot();
    expect(toolbarStorePool.createStore).toHaveBeenCalledWith(storeKey);
});

test('Render the warning from the ToolbarStore', () => {
    const storeKey = 'testStore';
    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue(undefined);
    toolbarStoreMock.warnings.push('Something unimportant went wrong');
    toolbarStoreMock.getIconsConfig.mockReturnValue([<p key={1}>Test1</p>, <p key={2}>Test2</p>]);

    const {asFragment} = render(<Toolbar storeKey={storeKey} />);
    expect(asFragment()).toMatchSnapshot();
    expect(toolbarStorePool.createStore).toHaveBeenCalledWith(storeKey);
});

test('Render the items as disabled if one is loading', () => {
    const storeKey = 'testStore';
    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.getItemsConfig.mockReturnValue(
        [
            {type: 'button', label: 'Add', icon: 'fa-add-o', disabled: false},
            {type: 'button', label: 'Delete', icon: 'fa-trash-o', loading: true},
        ]
    );

    render(<Toolbar storeKey={storeKey} />);
    expect(toolbarStorePool.createStore).toHaveBeenCalledWith(storeKey);

    expect(getButtonProps(0).disabled).toBe(true);
    expect(getButtonProps(1).disabled).toBe(true);
    expect(getButtonProps(2).disabled).toBe(true);
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
    const storeKey = 'testStore';
    const navigationButtonClickSpy = jest.fn();
    const user = userEvent.setup();
    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.showSuccess = true;

    render(<Toolbar onNavigationButtonClick={navigationButtonClickSpy} storeKey={storeKey} />);
    await user.click(screen.getByTestId('toolbar-success-button'));

    expect(navigationButtonClickSpy).toHaveBeenCalled();
});

test('Click on the success message should navigate back', async() => {
    const storeKey = 'testStore';
    const backSpy = jest.fn();
    const user = userEvent.setup();
    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({onClick: backSpy});
    toolbarStoreMock.showSuccess = true;

    render(<Toolbar storeKey={storeKey} />);
    await user.click(screen.getByTestId('toolbar-success-button'));

    expect(backSpy).toHaveBeenCalled();
});

test('Remove last error if close button on snackbar is clicked', async() => {
    const storeKey = 'testStore';
    const user = userEvent.setup();
    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.errors.push({code: 100, message: 'Something went wrong'});
    toolbarStoreMock.getItemsConfig.mockReturnValue(
        [
            {type: 'button', label: 'Add', icon: 'fa-add-o', disabled: false},
        ]
    );

    render(<Toolbar storeKey={storeKey} />);
    expect(screen.getByTestId('snackbar-error')).toBeInTheDocument();

    expect(toolbarStoreMock.errors).toHaveLength(1);
    await user.click(screen.getByTestId('snackbar-error'));
    expect(toolbarStoreMock.errors).toHaveLength(0);
});
