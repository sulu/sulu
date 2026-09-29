// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import CustomUrlsLocaleSelect from '../../fields/CustomUrlsLocaleSelect';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-page-bundle/stores', () => ({
    webspaceStore: {
        getWebspace: jest.fn(),
    },
}));

function createFormInspector(): any {
    return {
        options: {webspace: 'sulu_io'},
    };
}

beforeEach(() => {
    const webspace = {
        allLocalizations: [
            {localization: 'de'},
            {localization: 'en'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);
});

test('Pass correct props to MultiSelect', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();

    const {unmount} = render(
        <CustomUrlsLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value="en"
        />
    );

    expect(webspaceStore.getWebspace).toHaveBeenCalledWith('sulu_io');
    expect(screen.getByRole('button', {name: /en/})).toBeDisabled();

    unmount();

    render(
        <CustomUrlsLocaleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value="en"
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));

    const optionButtons = screen.getAllByRole('button')
        .filter((button) => button.classList.contains('option'));

    expect(optionButtons).toHaveLength(2);
    expect(optionButtons[0]).toHaveTextContent('de');
    expect(optionButtons[1]).toHaveTextContent('en');
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = createFormInspector();

    render(
        <CustomUrlsLocaleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="de"
        />
    );

    expect(webspaceStore.getWebspace).toHaveBeenCalledWith('sulu_io');

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: 'en'}));

    expect(changeSpy).toHaveBeenCalledWith('en');
    expect(finishSpy).toHaveBeenCalledWith();
});
