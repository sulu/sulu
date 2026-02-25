// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import ContactDetails from '../../fields/ContactDetails';
import ContactDetailsComponent from '../../../../components/ContactDetails';

jest.mock('../../../../components/ContactDetails', () => {
    const MockContactDetailsComponent: any = jest.fn(() => null);
    MockContactDetailsComponent.defaultProps = {
        value: {
            emails: [],
            faxes: [],
            phones: [],
            socialMedia: [],
            websites: [],
        },
    };

    return MockContactDetailsComponent;
});

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

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
            {...createProps()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    const contactDetailsProps: any = getLatestMockProps((ContactDetailsComponent: any));
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
            {...createProps()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={null}
        />
    );

    const contactDetailsProps: any = getLatestMockProps((ContactDetailsComponent: any));
    expect(contactDetailsProps.value).toEqual({
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    });
});
