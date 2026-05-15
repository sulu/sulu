// @flow
import {render} from '@testing-library/react';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import ContactDetails from '../../fields/ContactDetails';
import ContactDetailsComponent from '../../../../components/ContactDetails';

jest.mock('../../../../components/ContactDetails', () => jest.fn(() => null));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector() {
    return ({}: any);
}

test('Pass props correctly to ContactDetails component', () => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();
    const value = {
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    };

    render(
        <ContactDetails
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    const [contactDetailsProps] = (ContactDetailsComponent: any).mock.calls[0];
    expect(contactDetailsProps).toEqual(expect.objectContaining({
        onBlur: finishSpy,
        onChange: changeSpy,
        value,
    }));
});

test('Pass undefined as value if null is given', () => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        <ContactDetails
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={null}
        />
    );

    const [contactDetailsProps] = (ContactDetailsComponent: any).mock.calls[0];
    expect(contactDetailsProps.value).toEqual(undefined);
});
