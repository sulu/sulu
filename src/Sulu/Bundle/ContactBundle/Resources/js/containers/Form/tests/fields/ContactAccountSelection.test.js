// @flow
import React from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {mount, shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import Router from 'sulu-admin-bundle/services/Router';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import ContactAccountSelection from '../../fields/ContactAccountSelection';

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn());

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn());

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.clearSelection = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../ContactAccountSelection/stores/ContactAccountSelectionStore', () => jest.fn(function() {
    this.loadItems = jest.fn();

    mockExtendObservable(this, {
        items: [],
    });
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

test('Call onItemClick callback', () => {
    const router = new Router();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const contactAccountSelection = mount(
        <ContactAccountSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
            value={['a1', 'c2']}
        />
    );

    contactAccountSelection.find('ContactAccountSelection').at(1).instance().store.items = [
        {id: 'a1'},
        {id: 'c2'},
    ];

    contactAccountSelection.update();

    contactAccountSelection.find('MultiItemSelection .content').at(0).simulate('click');
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.account_edit_form', {id: '1'});

    contactAccountSelection.find('MultiItemSelection .content').at(1).simulate('click');
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.contact_edit_form', {id: '2'});
});
