// @flow
import {mount, shallow} from 'enzyme';
import Mousetrap from 'mousetrap';
import React from 'react';
import pretty from 'pretty';
import Overlay from '../Overlay';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('The component should render in body when open', () => {
    const body = document.body;
    const onClose = jest.fn();
    const view = mount(
        <Overlay
            confirmText="Apply"
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
            size="small"
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(view.find('Backdrop')).toHaveLength(1);
    expect(view.find('Backdrop').prop('open')).toEqual(true);
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should render with a disabled confirm button', () => {
    const body = document.body;
    const onClose = jest.fn();
    const view = mount(
        <Overlay
            confirmDisabled={true}
            confirmText="Apply"
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(view.find('Backdrop')).toHaveLength(1);
    expect(view.find('Backdrop').prop('open')).toEqual(true);
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should render in body with loader instead of confirm button', () => {
    const body = document.body;
    const onClose = jest.fn();
    const view = mount(
        <Overlay
            confirmLoading={true}
            confirmText="Apply"
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(view.find('Backdrop')).toHaveLength(1);
    expect(view.find('Backdrop').prop('open')).toEqual(true);
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should render in body with actions when open', () => {
    const actions = [
        {title: 'Action 1', onClick: () => {}},
        {title: 'Action 2', onClick: () => {}},
    ];
    const body = document.body;
    const onClose = jest.fn();
    const view = mount(
        <Overlay
            actions={actions}
            confirmText="Apply"
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(view.find('Backdrop')).toHaveLength(1);
    expect(view.find('Backdrop').prop('open')).toEqual(true);
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should not render in body when closed', () => {
    const body = document.body;
    const onClose = jest.fn();
    const view = mount(
        <Overlay
            confirmText="Apply"
            onClose={onClose}
            onConfirm={jest.fn()}
            open={false}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    ).render();
    expect(view).toMatchSnapshot();
    expect(body ? body.innerHTML : '').toBe('');
});

test('The component should request to be closed on click on backdrop', () => {
    const closeSpy = jest.fn();
    const view = shallow(
        <Overlay
            confirmText="Apply"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );
    const backdrop = view.find('Backdrop');
    expect(backdrop.length).toBe(1);

    expect(closeSpy).not.toBeCalled();
    backdrop.props().onClick();
    expect(closeSpy).toBeCalled();
});

test('The component should request to be closed when the close icon is clicked', () => {
    const closeSpy = jest.fn();
    const view = shallow(
        <Overlay
            confirmText="Apply"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(closeSpy).not.toBeCalled();
    view.find('Icon').simulate('click');
    expect(closeSpy).toBeCalled();
});

test('The component should request to be closed when the esc key is pressed', () => {
    const closeSpy = jest.fn();
    mount(
        <Overlay
            confirmText="Apply"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(closeSpy).not.toBeCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
});

test('The component should bind and unbind the esc key when overlay is opened and closed', () => {
    const closeSpy = jest.fn();
    const overlay = mount(
        <Overlay
            confirmText="Apply"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
            title="My overlay title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(closeSpy).not.toBeCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
    closeSpy.mockReset();

    overlay.setProps({open: false});
    Mousetrap.trigger('esc');
    expect(closeSpy).not.toBeCalled();
    closeSpy.mockReset();

    overlay.setProps({open: true});
    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
    closeSpy.mockReset();
});

test('The component should call the callback when the confirm button is clicked', () => {
    const onClose = jest.fn();
    const onConfirm = jest.fn();
    const view = shallow(
        <Overlay
            confirmText="Alright mate!"
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
            title="My title"
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(onConfirm).not.toBeCalled();
    view.find('Button').simulate('click');
    expect(onConfirm).toBeCalled();
});
