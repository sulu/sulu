// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Toolbar from '../Toolbar';
import toolbarStorePool from '../stores/toolbarStorePool';

let toolbarStoreMock: any = {};
let mockToolbarButtonProps: Array<Object> = [];

const mockReact = require('react');

jest.mock('../stores/toolbarStorePool', () => ({
    createStore: jest.fn(),
    getStore: jest.fn(),
    hasStore: jest.fn(),
}));

jest.mock('../../../utils/Translator');

jest.mock('../../../services/initializer', () => ({
    initializedTranslationsLocale: true,
}));

jest.mock('debounce', () => jest.fn((callback) => callback));

jest.mock('../../../components/Snackbar', () => jest.fn((props) => (
    props.visible
        ? mockReact.createElement(
            'div',
            {'data-testid': props.type + '-snackbar'},
            typeof props.message === 'string' ? props.message : props.message.message,
            props.onCloseClick && mockReact.createElement(
                'button',
                {
                    'aria-label': props.type + '-snackbar-close',
                    onClick: props.onCloseClick,
                    type: 'button',
                },
                'Close'
            )
        )
        : null
)));

jest.mock('../../../components/Toolbar', () => {
    const ToolbarMock: any = jest.fn((props) => mockReact.createElement('nav', {}, props.children));

    ToolbarMock.Controls = (props) => (
        mockReact.createElement('div', {'data-grow': props.grow ? 'true' : 'false'}, props.children)
    );

    ToolbarMock.Button = (props) => {
        mockToolbarButtonProps.push(props);

        return mockReact.createElement(
            'button',
            {
                'aria-label': props.icon || props.label || 'button',
                'data-disabled': props.disabled ? 'true' : 'false',
                'data-success': props.success ? 'true' : 'false',
                disabled: props.disabled,
                onClick: props.onClick ? () => props.onClick() : undefined,
                type: 'button',
            },
            props.label || props.icon
        );
    };

    ToolbarMock.Dropdown = (props) => mockReact.createElement('button', {type: 'button'}, props.label);
    ToolbarMock.Toggler = (props) => mockReact.createElement('button', {type: 'button'}, props.label);
    ToolbarMock.Select = (props) => mockReact.createElement('button', {type: 'button'}, props.label || 'Select');
    ToolbarMock.Items = (props) => mockReact.createElement('div', {'data-testid': 'items'}, props.children);
    ToolbarMock.Icons = (props) => mockReact.createElement('div', {'data-testid': 'icons'}, props.children);

    return ToolbarMock;
});

beforeEach(() => {
    mockToolbarButtonProps = [];
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

    jest.clearAllMocks();
});

function renderToolbar(props: Object = {}) {
    (toolbarStorePool.createStore: any).mockReturnValue(toolbarStoreMock);

    return render(<Toolbar storeKey="testStore" {...props} />);
}

test('Render the items and icons from the ToolbarStore', () => {
    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue(undefined);
    toolbarStoreMock.getIconsConfig.mockReturnValue([
        <p key={1}>Test1</p>,
        <p key={2}>Test2</p>,
    ]);
    toolbarStoreMock.getItemsConfig.mockReturnValue([
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
    ]);

    renderToolbar();

    expect(screen.getByText('Delete')).toBeInTheDocument();
    expect(screen.getByText('Save')).toBeInTheDocument();
    expect(screen.getByText('Toggler')).toBeInTheDocument();
    expect(screen.getByText('Test1')).toBeInTheDocument();
    expect(screen.getByText('Test2')).toBeInTheDocument();
    expect(toolbarStorePool.createStore).toHaveBeenCalledWith('testStore');
});

test('Render the error from the ToolbarStore', () => {
    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue(undefined);
    toolbarStoreMock.errors.push('Something went wrong');
    toolbarStoreMock.getIconsConfig.mockReturnValue([
        <p key={1}>Test1</p>,
        <p key={2}>Test2</p>,
    ]);

    renderToolbar();

    expect(screen.getByTestId('error-snackbar')).toHaveTextContent('Something went wrong');
    expect(toolbarStorePool.createStore).toHaveBeenCalledWith('testStore');
});

test('Render the warning from the ToolbarStore', () => {
    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue(undefined);
    toolbarStoreMock.warnings.push('Something unimportant went wrong');
    toolbarStoreMock.getIconsConfig.mockReturnValue([
        <p key={1}>Test1</p>,
        <p key={2}>Test2</p>,
    ]);

    renderToolbar();

    expect(screen.getByTestId('warning-snackbar')).toHaveTextContent('Something unimportant went wrong');
    expect(toolbarStorePool.createStore).toHaveBeenCalledWith('testStore');
});

test('Render the items as disabled if one is loading', () => {
    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.getItemsConfig.mockReturnValue([
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
    ]);

    renderToolbar();

    expect(toolbarStorePool.createStore).toHaveBeenCalledWith('testStore');
    expect(mockToolbarButtonProps[0].disabled).toBe(true);
    expect(mockToolbarButtonProps[1].disabled).toBe(true);
    expect(mockToolbarButtonProps[2].disabled).toBe(true);
});

test('Show success message on back button for some time', () => {
    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.showSuccess = true;

    renderToolbar();

    expect(mockToolbarButtonProps).toEqual(expect.arrayContaining([
        expect.objectContaining({
            icon: 'su-check',
            success: true,
        }),
    ]));
});

test('Show success message on navigation button for some time', () => {
    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.showSuccess = true;

    renderToolbar({onNavigationButtonClick: jest.fn()});

    expect(mockToolbarButtonProps[0]).toEqual(expect.objectContaining({
        icon: 'su-check',
        success: true,
    }));
});

test('Click on the success message should open the navigation', async() => {
    const navigationButtonClickSpy = jest.fn();

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.showSuccess = true;

    renderToolbar({onNavigationButtonClick: navigationButtonClickSpy});

    await userEvent.click(screen.getByLabelText('su-check'));

    expect(navigationButtonClickSpy).toHaveBeenCalledWith();
});

test('Click on the success message should navigate back', async() => {
    const backSpy = jest.fn();

    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({
        onClick: backSpy,
    });
    toolbarStoreMock.showSuccess = true;

    renderToolbar();

    await userEvent.click(screen.getByLabelText('su-check'));

    expect(backSpy).toHaveBeenCalledWith();
});

test('Remove last error if close button on snackbar is clicked', async() => {
    toolbarStoreMock.getLocaleConfig.mockReturnValue(undefined);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.errors.push({code: 100, message: 'Something went wrong'});
    toolbarStoreMock.getItemsConfig.mockReturnValue([
        {
            type: 'button',
            label: 'Add',
            icon: 'fa-add-o',
            disabled: false,
        },
    ]);

    renderToolbar();

    expect(screen.getByTestId('error-snackbar')).toBeInTheDocument();
    expect(toolbarStoreMock.errors).toHaveLength(1);

    await userEvent.click(screen.getByLabelText('error-snackbar-close'));

    expect(toolbarStoreMock.errors).toHaveLength(0);
});
