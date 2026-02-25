// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import CustomUrlsLocaleSelect from '../../fields/CustomUrlsLocaleSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-page-bundle/stores', () => ({
    webspaceStore: {
        getWebspace: jest.fn(),
    },
}));

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: {
        options: {
            webspace: 'sulu_io',
        },
    },
    ...props,
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct props to MultiSelect', async() => {
    const user = userEvent.setup();
    const webspace = {
        allLocalizations: [
            {localization: 'de'},
            {localization: 'en'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <CustomUrlsLocaleSelect
            {...createProps({
                disabled: true,
                value: 'en',
            })}
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');

    const triggerButton = screen.getByRole('button', {name: /en/});
    expect(triggerButton).toBeDisabled();

    await user.click(triggerButton);
    expect(screen.queryByRole('button', {name: 'de'})).not.toBeInTheDocument();
});

test('Render all locale options', async() => {
    const user = userEvent.setup();
    const webspace = {
        allLocalizations: [
            {localization: 'de'},
            {localization: 'en'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <CustomUrlsLocaleSelect
            {...createProps({
                value: 'en',
            })}
        />
    );

    await user.click(screen.getByRole('button', {name: /en/}));

    const optionsList = screen.getByRole('list');
    expect(within(optionsList).getByRole('button', {name: 'de'})).toBeInTheDocument();
    expect(within(optionsList).getByRole('button', {name: /en/})).toBeInTheDocument();
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const webspace = {
        allLocalizations: [
            {localization: 'de'},
            {localization: 'en'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <CustomUrlsLocaleSelect
            {...createProps({
                onChange: changeSpy,
                onFinish: finishSpy,
                value: 'de',
            })}
        />
    );

    await user.click(screen.getByRole('button', {name: /de/}));
    await user.click(screen.getByRole('button', {name: 'en'}));

    expect(changeSpy).toBeCalledWith('en');
    expect(finishSpy).toBeCalledWith();
});
