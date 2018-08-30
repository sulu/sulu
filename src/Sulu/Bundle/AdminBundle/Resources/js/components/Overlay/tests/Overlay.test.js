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
            title="My overlay title"
            onClose={onClose}
            onConfirm={jest.fn()}
            confirmText="Apply"
            open={true}
            size="small"
        >
            <p>My overlay content</p>
        </Overlay>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should render with a disabled confirm button', () => {
    const body = document.body;
    const onClose = jest.fn();
    const view = mount(
        <Overlay
            title="My overlay title"
            onClose={onClose}
            onConfirm={jest.fn()}
            confirmText="Apply"
            confirmDisabled={true}
            open={true}
        >
            <p>My overlay content</p>
        </Overlay>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should render in body with loader instead of confirm button', () => {
    const body = document.body;
    const onClose = jest.fn();
    const view = mount(
        <Overlay
            title="My overlay title"
            onClose={onClose}
            onConfirm={jest.fn()}
            confirmText="Apply"
            confirmLoading={true}
            open={true}
        >
            <p>My overlay content</p>
        </Overlay>
    ).render();

    expect(view).toMatchSnapshot();
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
            title="My overlay title"
            onClose={onClose}
            confirmText="Apply"
            onConfirm={jest.fn()}
            actions={actions}
            open={true}
        >
            <p>My overlay content</p>
        </Overlay>
    ).render();

    expect(view).toMatchSnapshot();
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should not render in body when closed', () => {
    const body = document.body;
    const onClose = jest.fn();
    const view = mount(
        <Overlay
            title="My overlay title"
            onClose={onClose}
            onConfirm={jest.fn()}
            confirmText="Apply"
            open={false}
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
            title="My overlay title"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            confirmText="Apply"
            open={true}
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
            title="My overlay title"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            confirmText="Apply"
            open={true}
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
            title="My overlay title"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            confirmText="Apply"
            open={true}
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(closeSpy).not.toBeCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
});

test('The component should call the callback when the confirm button is clicked', () => {
    const onClose = jest.fn();
    const onConfirm = jest.fn();
    const view = shallow(
        <Overlay
            title="My title"
            onClose={onClose}
            onConfirm={onConfirm}
            confirmText="Alright mate!"
            open={true}
        >
            <p>My overlay content</p>
        </Overlay>
    );

    expect(onConfirm).not.toBeCalled();
    view.find('Button').simulate('click');
    expect(onConfirm).toBeCalled();
});
