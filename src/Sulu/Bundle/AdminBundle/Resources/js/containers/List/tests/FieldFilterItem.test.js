// @flow
/* eslint-disable react/jsx-no-bind */
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Mousetrap from 'mousetrap';
import FieldFilterItem from '../FieldFilterItem';
import listFieldFilterTypeRegistry from '../registries/listFieldFilterTypeRegistry';

jest.mock('../registries/listFieldFilterTypeRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../components/ArrowMenu', () => {
    const React = require('react');
    const ArrowMenu: any = jest.fn(function ArrowMenu(props) {
        function handleClose() {
            props.onClose();
        }

        return (
            <div>
                {props.anchorElement}
                {props.open && (
                    <React.Fragment>
                        <button onClick={handleClose} type="button">
                            close-menu
                        </button>
                        {props.children}
                    </React.Fragment>
                )}
            </div>
        );
    });

    ArrowMenu.Section = jest.fn(function Section(props) {
        return <div>{props.children}</div>;
    });

    return ArrowMenu;
});

jest.mock('../../../components/Chip', () => jest.fn(function Chip(props) {
    return (
        <div>
            <button
                onClick={() => props.onClick(props.value)}
                type="button"
            >
                {props.children}
            </button>
            <button
                onClick={() => props.onDelete(props.value)}
                type="button"
            >
                delete-chip
            </button>
        </div>
    );
}));

jest.mock('../../../components/Button', () => jest.fn(function Button(props) {
    return (
        <button onClick={props.onClick} type="button">
            {props.children}
        </button>
    );
}));

jest.mock('../../../components/Loader', () => jest.fn(() => <span>loading</span>));

function createFieldFilterType({
    confirm = jest.fn(),
    destroy = jest.fn(),
    getFormNode = () => <div>This is the form node</div>,
    getValueNode = (value) => Promise.resolve('The value is ' + value),
    setValue = jest.fn(),
}: Object = {}) {
    const ListFieldFilterType = jest.fn(function ListFieldFilterType(
        onChange,
        filterTypeParameters,
        value,
        options
    ) {
        void filterTypeParameters;
        void value;
        void options;

        this.confirm = confirm;
        this.destroy = destroy;
        this.getFormNode = () => getFormNode(onChange);
        this.getValueNode = getValueNode;
        this.setValue = setValue;
    });

    return {
        ListFieldFilterType,
        confirm,
        destroy,
        setValue,
    };
}

function renderFieldFilterItem(props: Object = {}) {
    return render(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={jest.fn()}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={true}
            value="Test"
            {...props}
        />
    );
}

afterEach(() => {
    jest.clearAllMocks();

    if (Mousetrap.reset) {
        Mousetrap.reset();
    }
});

test('Render FieldFilterItem with a FieldFilterType', () => {
    const setValueSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType({
        getValueNode: () => new Promise(() => {}),
        setValue: setValueSpy,
    });

    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    const {asFragment} = renderFieldFilterItem();

    expect(asFragment()).toMatchSnapshot();
    expect(listFieldFilterTypeRegistry.get).toBeCalledWith('text');
    expect(ListFieldFilterType).toBeCalledWith(expect.any(Function), {value: 'Test'}, 'Test', {});
    expect(setValueSpy).toBeCalledWith('Test');
});

test('Close when esc button is pressed', () => {
    const closeSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType();
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    renderFieldFilterItem({onClose: closeSpy});

    expect(closeSpy).not.toBeCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
});

test('Do not close when esc button is pressed if was not opened', () => {
    const closeSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType();
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    renderFieldFilterItem({onClose: closeSpy, open: false});

    Mousetrap.trigger('esc');
    expect(closeSpy).not.toBeCalled();
});

test('Close when esc button is pressed if initially was closed but has been opened in the mean time', () => {
    const closeSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType();
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    const {rerender} = renderFieldFilterItem({onClose: closeSpy, open: false});

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={jest.fn()}
            onClick={jest.fn()}
            onClose={closeSpy}
            onDelete={jest.fn()}
            open={true}
            value="Test"
        />
    );

    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
});

test('Do not close when esc button is pressed if initially was opened but has been closed already', () => {
    const closeSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType();
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    const {rerender} = renderFieldFilterItem({onClose: closeSpy, open: true});

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={jest.fn()}
            onClick={jest.fn()}
            onClose={closeSpy}
            onDelete={jest.fn()}
            open={false}
            value="Test"
        />
    );

    Mousetrap.trigger('esc');
    expect(closeSpy).not.toBeCalled();
});

test('Change when enter button is pressed', () => {
    const changeSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType();
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    renderFieldFilterItem({onChange: changeSpy, open: true});

    expect(changeSpy).not.toBeCalled();
    Mousetrap.trigger('enter');
    expect(changeSpy).toBeCalled();
});

test('Do not change when enter button is pressed if was not opened', () => {
    const changeSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType();
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    renderFieldFilterItem({onChange: changeSpy, open: false});

    Mousetrap.trigger('enter');
    expect(changeSpy).not.toBeCalled();
});

test('Change when enter button is pressed if initially was closed but has been opened in the mean time', () => {
    const changeSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType();
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    const {rerender} = renderFieldFilterItem({onChange: changeSpy, open: false});

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={changeSpy}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={true}
            value="Test"
        />
    );

    Mousetrap.trigger('enter');
    expect(changeSpy).toBeCalled();
});

test('Do not change when enter button is pressed if initially was opened but has been closed already', () => {
    const changeSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType();
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    const {rerender} = renderFieldFilterItem({onChange: changeSpy, open: true});

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={changeSpy}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={false}
            value="Test"
        />
    );

    Mousetrap.trigger('enter');
    expect(changeSpy).not.toBeCalled();
});

test('Pass callbacks to correct props', async() => {
    const user = userEvent.setup();
    const clickSpy = jest.fn();
    const closeSpy = jest.fn();
    const deleteSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType();
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    renderFieldFilterItem({onClick: clickSpy, onClose: closeSpy, onDelete: deleteSpy});

    await user.click(screen.getByRole('button', {name: 'delete-chip'}));
    expect(deleteSpy).toBeCalledWith('salutation');

    await user.click(screen.getByRole('button', {name: 'close-menu'}));
    expect(closeSpy).toBeCalledWith();

    await user.click(screen.getByRole('button', {name: /Salutation:/}));
    expect(clickSpy).toBeCalledWith('salutation');
});

test('Update value and reset when FieldFilterItem is closed without confirming', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const setValueSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType({
        getFormNode: (onChange) => (
            <button onClick={() => onChange('test-value')} type="button">
                set-value
            </button>
        ),
        setValue: setValueSpy,
    });
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    const {rerender} = renderFieldFilterItem({onChange: changeSpy, open: true});

    await user.click(screen.getByRole('button', {name: 'set-value'}));
    expect(setValueSpy).toBeCalledWith('test-value');

    setValueSpy.mockReset();

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={changeSpy}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={false}
            value="Test"
        />
    );
    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={changeSpy}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={true}
            value="Test"
        />
    );

    expect(changeSpy).not.toBeCalledWith('salutation', 'test-value');
    expect(setValueSpy).toBeCalledWith('Test');
});

test('Update value and call onChange when FieldFilterItem is confirmed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const confirmSpy = jest.fn();
    const setValueSpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType({
        confirm: confirmSpy,
        getFormNode: (onChange) => (
            <button onClick={() => onChange('test-value')} type="button">
                set-value
            </button>
        ),
        setValue: setValueSpy,
    });
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    renderFieldFilterItem({onChange: changeSpy, open: true});

    await user.click(screen.getByRole('button', {name: 'set-value'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(changeSpy).toBeCalledWith('salutation', 'test-value');
    expect(confirmSpy).toBeCalledWith();
    expect(setValueSpy).toBeCalledWith('test-value');
});

test('Return correct value node when value changes', async() => {
    const promise1 = Promise.resolve('First promise');
    const promise2 = Promise.resolve('Second promise');
    const getValueNodeSpy = jest.fn().mockReturnValueOnce(promise1).mockReturnValueOnce(promise2);
    const {ListFieldFilterType} = createFieldFilterType({
        getValueNode: getValueNodeSpy,
    });
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    const {rerender} = renderFieldFilterItem({
        filterTypeParameters: null,
        value: 'Test',
    });

    await waitFor(() => {
        expect(screen.getByRole('button', {name: /Salutation: First promise/})).toBeInTheDocument();
    });

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={null}
            label="Salutation"
            onChange={jest.fn()}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={true}
            value="Test 2"
        />
    );

    await waitFor(() => {
        expect(screen.getByRole('button', {name: /Salutation: Second promise/})).toBeInTheDocument();
    });
});

test('Call disposers when unmounted', () => {
    const unbindSpy = jest.spyOn(Mousetrap, 'unbind');
    const destroySpy = jest.fn();
    const {ListFieldFilterType} = createFieldFilterType({
        destroy: destroySpy,
    });
    listFieldFilterTypeRegistry.get.mockReturnValue(ListFieldFilterType);

    const {unmount} = renderFieldFilterItem({filterTypeParameters: null, value: undefined});

    unmount();

    expect(destroySpy).toBeCalled();
    expect(unbindSpy).toHaveBeenCalledWith('esc');
    expect(unbindSpy).toHaveBeenCalledWith('enter');

    unbindSpy.mockRestore();
});
