// @flow
import {render} from '@testing-library/react';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import ContactAccountSelection from '../../fields/ContactAccountSelection';
import ContactAccountSelectionComponent from '../../../../containers/ContactAccountSelection';

jest.mock('../../../../containers/ContactAccountSelection', () => {
    const ContactAccountSelectionMock = jest.fn(() => null);
    (ContactAccountSelectionMock: any).defaultProps = {
        disabled: false,
        value: [],
    };

    return ContactAccountSelectionMock;
});

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector() {
    return ({}: any);
}

test('Pass props correctly to ContactAccountSelection component', () => {
    render(<ContactAccountSelection {...fieldTypeDefaultProps} formInspector={createFormInspector()} />);

    const [contactAccountSelectionProps] = (ContactAccountSelectionComponent: any).mock.calls[0];
    expect(contactAccountSelectionProps).toEqual(expect.objectContaining({
        disabled: false,
        value: [],
    }));
});

test('Pass disabled prop to ContactAccountSelection component', () => {
    render(
        <ContactAccountSelection {...fieldTypeDefaultProps} disabled={true} formInspector={createFormInspector()} />
    );

    const [contactAccountSelectionProps] = (ContactAccountSelectionComponent: any).mock.calls[0];
    expect(contactAccountSelectionProps.disabled).toEqual(true);
});

test('Pass value prop to ContactAccountSelection component', () => {
    render(
        <ContactAccountSelection
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            value={['a1', 'c2']}
        />
    );

    const [contactAccountSelectionProps] = (ContactAccountSelectionComponent: any).mock.calls[0];
    expect(contactAccountSelectionProps.value).toEqual(['a1', 'c2']);
});

test('Call onChange and onFinish callbacks', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <ContactAccountSelection
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['a1', 'c2']}
        />
    );

    const [contactAccountSelectionProps] = (ContactAccountSelectionComponent: any).mock.calls[0];
    contactAccountSelectionProps.onChange(['a1', 'c6']);

    expect(changeSpy).toBeCalledWith(['a1', 'c6']);
    expect(finishSpy).toBeCalledWith();
});

test('Call onItemClick callback', () => {
    const router = ({
        navigate: jest.fn(),
    }: any);

    render(
        <ContactAccountSelection
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            router={router}
            value={['a1', 'c2']}
        />
    );

    const [contactAccountSelectionProps] = (ContactAccountSelectionComponent: any).mock.calls[0];

    contactAccountSelectionProps.onItemClick('a1');
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.account_edit_form', {id: '1'});

    contactAccountSelectionProps.onItemClick('c2');
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.contact_edit_form', {id: '2'});
});
