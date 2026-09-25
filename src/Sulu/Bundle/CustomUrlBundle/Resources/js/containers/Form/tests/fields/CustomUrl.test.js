// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import bindValueToOnChange from 'sulu-admin-bundle/utils/TestHelper/bindValueToOnChange';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import CustomUrl from '../../fields/CustomUrl';

let mockResourceLocatorHistoryProps: any;

const mockReact = require('react');

jest.mock('sulu-admin-bundle/containers', () => ({
    ResourceLocatorHistory: jest.fn((props) => {
        mockResourceLocatorHistoryProps = props;

        return mockReact.createElement('div', {'data-testid': 'resource-locator-history'});
    }),
}));

beforeEach(() => {
    mockResourceLocatorHistoryProps = undefined;
});

function createFormInspector(id, baseDomain): any {
    return {
        getValueByPath: jest.fn((path) => {
            if (path === '/baseDomain') {
                return baseDomain;
            }
        }),
        id,
        options: {webspace: 'sulu_io'},
    };
}

test('Pass correct props to CustomUrl component', () => {
    const formInspector = createFormInspector(undefined, '*.sulu.io/*');

    render(<CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} value={['a', 'b']} />);

    expect(screen.getByDisplayValue('a')).toBeInTheDocument();
    expect(screen.getByText('.sulu.io/')).toBeInTheDocument();
    expect(screen.getByDisplayValue('b')).toBeInTheDocument();
    expect(screen.queryByTestId('resource-locator-history')).not.toBeInTheDocument();
});

test('Pass correct props to ResourceLocatorHistory component if id an existing resource is loaded', () => {
    const formInspector = createFormInspector(2, '*.sulu.io/*');

    render(<CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} value={['a', 'b']} />);

    expect(screen.getByDisplayValue('a')).toBeInTheDocument();
    expect(screen.getByText('.sulu.io/')).toBeInTheDocument();
    expect(screen.getByDisplayValue('b')).toBeInTheDocument();
    expect(screen.getByTestId('resource-locator-history')).toBeInTheDocument();
    expect(mockResourceLocatorHistoryProps.id).toEqual(2);
    expect(mockResourceLocatorHistoryProps.options).toEqual({webspace: 'sulu_io'});
    expect(mockResourceLocatorHistoryProps.resourceKey).toEqual('custom_url_routes');
});

test('Pass correct props with empty value to CustomUrl component', () => {
    const formInspector = createFormInspector(undefined, 'sulu.io/*');

    render(<CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} value={undefined} />);

    expect(screen.getByText('sulu.io/')).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toHaveValue('');
});

test('Call onChange when if a value changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const formInspector = createFormInspector(undefined, 'sulu.io/*');

    render(
        bindValueToOnChange(
            <CustomUrl
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                onChange={changeSpy}
                value={undefined}
            />
        )
    );

    await user.type(screen.getByRole('textbox'), 'test');

    expect(changeSpy).toHaveBeenLastCalledWith(['test']);
});

test('Call onFinish when if the field is blurred', async() => {
    const user = userEvent.setup();
    const finishSpy = jest.fn();
    const formInspector = createFormInspector(undefined, 'sulu.io/*');

    render(
        <CustomUrl
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
            value={undefined}
        />
    );

    await user.click(screen.getByRole('textbox'));
    await user.tab();

    expect(finishSpy).toHaveBeenCalledWith();
});
