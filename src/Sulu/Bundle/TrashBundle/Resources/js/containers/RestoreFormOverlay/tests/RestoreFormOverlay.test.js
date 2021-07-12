// @flow
import mockReact from 'react';
import {shallow, mount} from 'enzyme';
import {extendObservable as mockExtendObservable} from 'mobx';
import RestoreFormOverlay from '../RestoreFormOverlay';

const React = mockReact;

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/SchemaFormStoreDecorator',
    () => jest.fn(function(initializer: any, formKey: string) {
        this.destroy = jest.fn();
        this.formKey = formKey;

        mockExtendObservable(this, {
            data: {},
        });
    })
);

jest.mock('sulu-admin-bundle/containers/Form/stores/MemoryFormStore',
    () => jest.fn()
);

jest.mock('sulu-admin-bundle/containers/Form', () => class FormMock extends mockReact.Component<*> {
    render() {
        return <div>form container mock</div>;
    }
});

test('Component should render', () => {
    const restoreFormOverlay = mount(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(restoreFormOverlay.render()).toMatchSnapshot();
});

test('Component should not render without formKey', () => {
    const restoreFormOverlay = mount(
        <RestoreFormOverlay
            formKey={null}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(restoreFormOverlay.render()).toMatchSnapshot();
});

test('Component should call close callback', () => {
    const onClose = jest.fn();

    const restoreFormOverlay = shallow(
        <RestoreFormOverlay
            formKey="test"
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    restoreFormOverlay.find('FormOverlay').prop('onClose')();

    expect(onClose).toHaveBeenCalled();
});

test('Component should call confirm callback', () => {
    const onConfirm = jest.fn();

    const restoreFormOverlay = shallow(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={true}
        />
    );

    const data = {foo: 'bar'};
    restoreFormOverlay.instance().formStore.data = data;

    restoreFormOverlay.find('FormOverlay').prop('onConfirm')();

    expect(onConfirm).toHaveBeenCalledWith(data);
});

test('Component should update formStore on changing form key', () => {
    const onConfirm = jest.fn();

    const restoreFormOverlay = shallow(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
        />
    );

    const formStore = restoreFormOverlay.instance().formStore;
    expect(formStore.formKey).toBe('test');

    restoreFormOverlay.setProps({formKey: 'other'});

    expect(formStore.destroy).toHaveBeenCalled();

    const newFormStore = restoreFormOverlay.instance().formStore;
    expect(newFormStore).not.toBe(formStore);
    expect(newFormStore.formKey).toBe('other');
    expect(newFormStore.destroy).not.toHaveBeenCalled();
});

test('Component should update formStore on reopen', () => {
    const onConfirm = jest.fn();

    const restoreFormOverlay = shallow(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
        />
    );

    const formStore = restoreFormOverlay.instance().formStore;

    restoreFormOverlay.setProps({open: true});

    expect(formStore.destroy).toHaveBeenCalled();

    const newFormStore = restoreFormOverlay.instance().formStore;
    expect(newFormStore).not.toBe(formStore);
    expect(newFormStore.destroy).not.toHaveBeenCalled();
});

test('Component should destroy formStore on unmount', () => {
    const onConfirm = jest.fn();

    const restoreFormOverlay = shallow(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={false}
        />
    );

    const formStore = restoreFormOverlay.instance().formStore;

    restoreFormOverlay.unmount();

    expect(formStore.destroy).toHaveBeenCalled();
});
