// @flow
import mockReact from 'react';
import {mount, shallow} from 'enzyme';
import {extendObservable as mockExtendObservable} from 'mobx';
import FormOverlay from '../FormOverlay';
import Overlay from '../../../components/Overlay';
import ResourceStore from '../../../stores/ResourceStore';
import MemoryFormStore from '../../../containers/Form/stores/MemoryFormStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';
import Form from '../../../containers/Form';
import Snackbar from '../../../components/Snackbar';

const React = mockReact;

jest.mock('../../../containers/Form', () => class FormMock extends mockReact.Component<*> {
    render() {
        return <div>form container mock</div>;
    }
});

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(
    (resourceKey, itemId) => {
        return {
            id: itemId,
        };
    }
));
jest.mock('../../../containers/Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceStore, formKey, options, metadataOptions) {
        this.id = resourceStore.id;
        this.formKey = formKey;
        this.options = options;
        this.metadataOptions = metadataOptions;

        this.save = jest.fn();

        mockExtendObservable(this, {
            dirty: false,
            saving: false,
        });
    })
);
jest.mock('../../../containers/Form/stores/MemoryFormStore',
    () => jest.fn(function(data, rawSchema, jsonSchema, locale) {
        this.rawSchema = rawSchema;
        this.jsonSchema = jsonSchema;
        this.locale = locale;

        mockExtendObservable(this, {
            data,
            dirty: false,
        });
    })
);

test('Component should render', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);

    const formOverlay = mount(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    expect(formOverlay.render()).toMatchSnapshot();
});

test('Should pass correct props to Overlay component', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);
    formStore.dirty = true;

    const closeSpy = jest.fn();

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={closeSpy}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const overlay = formOverlay.find(Overlay);

    expect(overlay.props()).toEqual(expect.objectContaining({
        confirmDisabled: false,
        confirmLoading: false,
        confirmText: 'confirm-text',
        onClose: closeSpy,
        open: true,
        size: 'small',
        title: 'overlay-title',
    }));
});

test('Should pass correct props to Form component', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const form = formOverlay.find(Form);

    expect(form.props()).toEqual(expect.objectContaining({
        store: formStore,
    }));
});

test('Should disable confirm button if FromStore is not dirty', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    formStore.dirty = false;
    expect(formOverlay.find(Overlay).props().confirmDisabled).toEqual(true);

    formStore.dirty = true;
    expect(formOverlay.find(Overlay).props().confirmDisabled).toEqual(false);
});

test('Should display confirm button as loading if FromStore is saving', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    // $FlowFixMe
    formStore.saving = false;
    expect(formOverlay.find(Overlay).props().confirmLoading).toEqual(false);

    // $FlowFixMe
    formStore.saving = true;
    expect(formOverlay.find(Overlay).props().confirmLoading).toEqual(true);
});

test('Should submit Form container when Overlay is confirmed', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);

    const formOverlay = mount(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const submitSpy = jest.fn();
    formOverlay.find(Form).instance().submit = submitSpy;

    formOverlay.find(Overlay).props().onConfirm();

    expect(submitSpy).toBeCalled();
});

test('Should save ResourceFormStore and call onConfirm callback on submit of Form', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const savePromise = Promise.resolve();
    formStore.save.mockReturnValueOnce(savePromise);

    formOverlay.find(Form).props().onSubmit();

    return savePromise.finally(() => {
        expect(formStore.save).toBeCalled();
        expect(confirmSpy).toBeCalled();
    });
});

test('Should call onConfirm callback directly in case of MemoryFormStore on submit of Form', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);
    const confirmSpy = jest.fn();

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    formOverlay.find(Form).props().onSubmit();

    expect(confirmSpy).toBeCalled();
});

test('Should display Snackbar with generic message if an error happens while saving ResourceFormStore', (done) => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const savePromise = Promise.reject('error');
    formStore.save.mockReturnValueOnce(savePromise);

    formOverlay.find(Form).props().onSubmit();

    // wait until rejection of savePromise was handled by component with setTimeout
    setTimeout(() => {
        expect(formStore.save).toBeCalled();
        expect(confirmSpy).not.toBeCalled();

        formOverlay.update();
        expect(formOverlay.find(Snackbar).prop('visible')).toBeTruthy();
        expect(formOverlay.find(Snackbar).prop('message')).toEqual('sulu_admin.form_save_server_error');

        done();
    });
});

test('Should display Snackbar with message from server if an error happens while saving ResourceFormStore', (done) => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const savePromise = Promise.reject({code: 100, detail: 'URL is already assigned to another page.'});
    formStore.save.mockReturnValueOnce(savePromise);

    formOverlay.find(Form).props().onSubmit();

    // wait until rejection of savePromise was handled by component with setTimeout
    setTimeout(() => {
        expect(formStore.save).toBeCalled();
        expect(confirmSpy).not.toBeCalled();

        formOverlay.update();
        expect(formOverlay.find(Snackbar).prop('visible')).toBeTruthy();
        expect(formOverlay.find(Snackbar).prop('message')).toEqual('URL is already assigned to another page.');

        done();
    });
});

test('Should display Snackbar if a form is not valid', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    formOverlay.find(Form).props().onError();
    formOverlay.update();

    expect(formOverlay.find(Snackbar).prop('visible')).toBeTruthy();
    expect(formOverlay.find(Snackbar).prop('message')).toEqual('sulu_admin.form_contains_invalid_values');
});

test('Should hide Snackbar when closeClick callback of Snackbar is fired', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    formOverlay.find(Form).props().onError();
    formOverlay.update();
    expect(formOverlay.find(Snackbar).prop('visible')).toBeTruthy();

    formOverlay.find(Snackbar).props().onCloseClick();
    formOverlay.update();
    expect(formOverlay.find(Snackbar).props().visible).toBeFalsy();
});

test('Should clear old errors if Overlay is opened a second time', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    const formOverlay = shallow(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    formOverlay.find(Form).props().onError();
    formOverlay.update();
    expect(formOverlay.find(Snackbar).prop('visible')).toBeTruthy();

    formOverlay.setProps({open: false});
    formOverlay.setProps({open: true});

    expect(formOverlay.find(Snackbar).props().visible).toBeFalsy();
});
