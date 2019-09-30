// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import ContactAccountSelection from '../../fields/ContactAccountSelection';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(),
    ResourceFormStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

test('Pass props correctly to ContactAccountSelection component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const contactAccountSelection = shallow(
        <ContactAccountSelection {...fieldTypeDefaultProps} formInspector={formInspector} />
    );

    expect(contactAccountSelection.props()).toEqual(expect.objectContaining({
        disabled: false,
        value: [],
    }));
});

test('Pass disabled prop to ContactAccountSelection component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const contactAccountSelection = shallow(
        <ContactAccountSelection {...fieldTypeDefaultProps} disabled={true} formInspector={formInspector} />
    );

    expect(contactAccountSelection.prop('disabled')).toEqual(true);
});

test('Pass value prop to ContactAccountSelection component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const contactAccountSelection = shallow(
        <ContactAccountSelection {...fieldTypeDefaultProps} formInspector={formInspector} value={['a1', 'c2']} />
    );

    expect(contactAccountSelection.prop('value')).toEqual(['a1', 'c2']);
});

test('Call onChange and onFinish calbacks', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const contactAccountSelection = shallow(
        <ContactAccountSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['a1', 'c2']}
        />
    );

    contactAccountSelection.prop('onChange')(['a1', 'c6']);

    expect(changeSpy).toBeCalledWith(['a1', 'c6']);
    expect(finishSpy).toBeCalledWith();
});
