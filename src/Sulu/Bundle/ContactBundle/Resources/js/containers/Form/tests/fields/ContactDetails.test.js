// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import ContactDetails from '../../fields/ContactDetails';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(),
    ResourceFormStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

test('Pass props correctly to ContactDetails component', () => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = {
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    };

    const bic = shallow(
        <ContactDetails
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    expect(bic.props()).toEqual(expect.objectContaining({
        onBlur: finishSpy,
        onChange: changeSpy,
        value: value,
    }));
});

test('Pass undefined as value if null is given', () => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const bic = shallow(
        <ContactDetails
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={null}
        />
    );

    expect(bic.prop('value')).toEqual({emails: [], faxes: [], phones: [], socialMedia: [], websites: []});
});
