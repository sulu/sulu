// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import CustomUrlsDomainSelect from '../../fields/CustomUrlsDomainSelect';

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
        customUrls: [
            {url: 'www.sulu.io/*'},
            {url: '*.sulu.io'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);
});

test('Pass correct props to MultiSelect', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();

    const {unmount} = render(
        <CustomUrlsDomainSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value="www.sulu.io/*"
        />
    );

    expect(webspaceStore.getWebspace).toHaveBeenCalledWith('sulu_io');
    expect(screen.getByRole('button', {name: /www\.sulu\.io\/\*/})).toBeDisabled();

    unmount();

    render(
        <CustomUrlsDomainSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value="www.sulu.io/*"
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));

    const optionButtons = screen.getAllByRole('button')
        .filter((button) => button.classList.contains('option'));

    expect(optionButtons).toHaveLength(2);
    expect(optionButtons[0]).toHaveTextContent('www.sulu.io/*');
    expect(optionButtons[1]).toHaveTextContent('*.sulu.io');
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = createFormInspector();

    render(
        <CustomUrlsDomainSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="www.sulu.io/*"
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: '*.sulu.io'}));

    expect(changeSpy).toHaveBeenCalledWith('*.sulu.io');
    expect(finishSpy).toHaveBeenCalledWith();
});
