// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import {ResourceLocatorHistory} from 'sulu-admin-bundle/containers';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import CustomUrl from '../../fields/CustomUrl';

jest.mock('sulu-admin-bundle/containers', () => ({
    ResourceLocatorHistory: jest.fn(() => null),
}));

function createFormInspector(id: ?number, baseDomain: string) {
    return ({
        getValueByPath: jest.fn().mockReturnValue(baseDomain),
        id,
        options: {webspace: 'sulu_io'},
    }: any);
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct props to CustomUrl component', () => {
    const formInspector = createFormInspector(undefined, '*.sulu.io/*');

    render(<CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} value={['a', 'b']} />);

    expect(formInspector.getValueByPath).toHaveBeenCalledWith('/baseDomain');
    expect(screen.getByDisplayValue('a')).toBeInTheDocument();
    expect(screen.getByDisplayValue('b')).toBeInTheDocument();
    expect(ResourceLocatorHistory).not.toHaveBeenCalled();
});

test('Pass correct props to ResourceLocatorHistory component if id an existing resource is loaded', () => {
    const formInspector = createFormInspector(2, '*.sulu.io/*');

    render(<CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} value={['a', 'b']} />);

    expect(formInspector.getValueByPath).toHaveBeenCalledWith('/baseDomain');
    expect(screen.getByDisplayValue('a')).toBeInTheDocument();
    expect(screen.getByDisplayValue('b')).toBeInTheDocument();
    expect(ResourceLocatorHistory).toHaveBeenCalledTimes(1);
    const [resourceLocatorHistoryProps] = (ResourceLocatorHistory: any).mock.calls[0];
    expect(resourceLocatorHistoryProps.id).toEqual(2);
    expect(resourceLocatorHistoryProps.options).toEqual({webspace: 'sulu_io'});
    expect(resourceLocatorHistoryProps.resourceKey).toEqual('custom_url_routes');
});

test('Pass correct props with empty value to CustomUrl component', () => {
    const formInspector = createFormInspector(undefined, 'sulu.io/*');

    render(<CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} value={undefined} />);

    expect(formInspector.getValueByPath).toHaveBeenCalledWith('/baseDomain');
    expect(screen.getByRole('textbox')).toHaveValue('');
});

test('Call onChange when if a value changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const formInspector = createFormInspector(undefined, 'sulu.io/*');
    const CustomUrlTestWrapper = () => {
        const [value, setValue] = React.useState(undefined);
        const handleChange = React.useCallback((newValue) => {
            setValue(newValue);
            changeSpy(newValue);
        }, []);

        return (
            <CustomUrl
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                onChange={handleChange}
                value={value}
            />
        );
    };

    render(<CustomUrlTestWrapper />);

    await user.type(screen.getByRole('textbox'), 'test');

    expect(changeSpy).toBeCalledWith(['test']);
});

test('Call onFinish when if the field is blurred', () => {
    const finishSpy = jest.fn();
    const formInspector = createFormInspector(undefined, 'sulu.io/*');

    render(
        <CustomUrl {...fieldTypeDefaultProps} formInspector={formInspector} onFinish={finishSpy} value={undefined} />
    );

    fireEvent.blur(screen.getByRole('textbox'));

    expect(finishSpy).toBeCalledWith();
});
