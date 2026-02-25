// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import itemStyles from '../item.scss';
import Item from '../Item';

const createProps = (props: Object = {}) => ({
    children: 'Test',
    id: 1,
    order: 1,
    ...props,
});

test('Should render item as not selected by default', () => {
    const {asFragment} = render(<Item {...createProps()} />);
    const itemRootNode = screen.getByRole('button');

    expect(itemRootNode).not.toHaveClass(itemStyles.selected);
    expect(itemRootNode).not.toHaveClass(itemStyles.disabled);
    expect(asFragment()).toMatchSnapshot();
});

test('Should render item as selected', () => {
    render(<Item {...createProps({selected: true})} />);
    const itemRootNode = screen.getByRole('button');

    expect(itemRootNode).toHaveClass(itemStyles.selected);
});

test('Should render item as disabled', () => {
    render(<Item {...createProps({disabled: true})} />);
    const itemRootNode = screen.getByRole('button');

    expect(itemRootNode).toHaveClass(itemStyles.disabled);
});

test('Should render item with indicators', () => {
    const indicators = [
        <span key={1}>ghost</span>,
        <span key={2}>shadow</span>,
    ];
    render(
        <Item {...createProps({id: 2, indicators, order: 4, children: 'Test with indicators'})} />
    );
    expect(screen.getByText('ghost')).toBeInTheDocument();
    expect(screen.getByText('shadow')).toBeInTheDocument();
});

test('Should render item with order input', () => {
    const indicators = [
        <span key={1}>ghost</span>,
        <span key={2}>shadow</span>,
    ];
    render(
        <Item {...createProps({
            id: 2,
            indicators,
            order: 4,
            showOrderField: true,
            children: 'Test with indicators',
        })}
        />
    );

    expect(screen.getByRole('textbox')).toHaveValue('4');
});

test('Should call onDoubleClick', async() => {
    const user = userEvent.setup();
    const doubleClickSpy = jest.fn();
    render(<Item {...createProps({id: 2, onDoubleClick: doubleClickSpy, children: 'Test with indicators'})} />);

    await user.dblClick(screen.getByRole('button'));

    expect(doubleClickSpy).toBeCalledWith(2);
});

test('Should not call onDoubleClick if order field is shown', async() => {
    const user = userEvent.setup();
    const doubleClickSpy = jest.fn();
    render(
        <Item {...createProps({
            id: 2,
            onDoubleClick: doubleClickSpy,
            showOrderField: true,
            children: 'Test with indicators',
        })}
        />
    );

    await user.dblClick(screen.getByRole('button'));

    expect(doubleClickSpy).not.toBeCalled();
});

test('Should call onOrderChange callback when order has changed', async() => {
    const user = userEvent.setup();
    const orderChangePromise = Promise.resolve(true);
    const orderChangeSpy = jest.fn().mockReturnValue(orderChangePromise);
    render(
        <Item {...createProps({
            id: 2,
            onOrderChange: orderChangeSpy,
            order: 4,
            showOrderField: true,
            children: 'Test with indicators',
        })}
        />
    );
    const inputNode = screen.getByRole('textbox');

    await user.type(inputNode, '{backspace}5');
    await user.tab();

    expect(orderChangeSpy).toBeCalledWith(2, 5);
    expect(inputNode).toHaveValue('5');

    await orderChangePromise;
    expect(inputNode).toHaveValue('5');
});

test('Should call onOrderChange callback when order has changed and reset order if cancelled', async() => {
    const user = userEvent.setup();
    const orderChangePromise = Promise.resolve(false);
    const orderChangeSpy = jest.fn().mockReturnValue(orderChangePromise);
    render(
        <Item {...createProps({
            id: 2,
            onOrderChange: orderChangeSpy,
            order: 4,
            showOrderField: true,
            children: 'Test with indicators',
        })}
        />
    );
    const inputNode = screen.getByRole('textbox');

    await user.type(inputNode, '{backspace}5');
    await user.tab();

    expect(orderChangeSpy).toBeCalledWith(2, 5);

    await orderChangePromise;
    await waitFor(() => expect(inputNode).toHaveValue('4'));
});

test('Should call onOrderChange callback when order has changed after pressing enter', async() => {
    const user = userEvent.setup();
    const orderChangeSpy = jest.fn().mockReturnValue(Promise.resolve(true));
    render(
        <Item {...createProps({
            id: 2,
            onOrderChange: orderChangeSpy,
            order: 4,
            showOrderField: true,
            children: 'Test with indicators',
        })}
        />
    );
    const inputNode = screen.getByRole('textbox');

    await user.type(inputNode, '{backspace}5{enter}');

    await waitFor(() => expect(orderChangeSpy).toBeCalledWith(2, 5));
});

test('Should change order when item receives new props', async() => {
    const user = userEvent.setup();
    const {rerender} = render(<Item {...createProps({id: 2, order: 4, showOrderField: true})} />);
    const inputNode = screen.getByRole('textbox');

    expect(inputNode).toHaveValue('4');
    await user.type(inputNode, '{backspace}5');
    expect(inputNode).toHaveValue('5');

    rerender(<Item {...createProps({id: 2, order: 1, showOrderField: true})} />);

    expect(screen.getByRole('textbox')).toHaveValue('1');
});
