// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Pagination from '../Pagination';

jest.mock('../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
            case 'sulu_admin.per_page':
                return 'Items per page';
        }
    },
}));

test('Render pagination with loader', () => {
    const {container} = render(
        <Pagination
            currentLimit={10}
            currentPage={5}
            loading={true}
            onLimitChange={jest.fn()}
            onPageChange={jest.fn()}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );
    expect(container).toMatchSnapshot();
});

test('Render pagination with page numbers', () => {
    const {container} = render(
        <Pagination
            currentLimit={10}
            currentPage={5}
            onLimitChange={jest.fn()}
            onPageChange={jest.fn()}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );
    expect(container).toMatchSnapshot();
});

test('Render disabled next link if current page is last page', () => {
    const {container} = render(
        <Pagination
            currentLimit={10}
            currentPage={5}
            onLimitChange={jest.fn()}
            onPageChange={jest.fn()}
            totalPages={5}
        >
            <p>Test</p>
        </Pagination>
    );
    expect(container).toMatchSnapshot();
});

test('Render disabled previous link current page is first page', () => {
    const {container} = render(
        <Pagination
            currentLimit={10}
            currentPage={1}
            onLimitChange={jest.fn()}
            onPageChange={jest.fn()}
            totalPages={5}
        >
            <p>Test</p>
        </Pagination>
    );
    expect(container).toMatchSnapshot();
});

test('Should call callback with updated page when initialized with an invalid page', () => {
    const changeSpy = jest.fn();

    render(
        <Pagination
            currentLimit={10}
            currentPage={15}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    expect(changeSpy).toBeCalledWith(10);
});

test('Should call callback with updated page when changing page to invalid value', () => {
    const changeSpy = jest.fn();

    const {rerender} = render(
        <Pagination
            currentLimit={10}
            currentPage={5}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    rerender(
        <Pagination
            currentLimit={10}
            currentPage={8}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );
    expect(changeSpy).not.toBeCalled();

    rerender(
        <Pagination
            currentLimit={10}
            currentPage={15}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );
    expect(changeSpy).toBeCalledWith(10);
});

test('Should call callback with updated page when changing total number of pages to lower value', () => {
    const changeSpy = jest.fn();

    const {rerender} = render(
        <Pagination
            currentLimit={10}
            currentPage={5}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    rerender(
        <Pagination
            currentLimit={10}
            currentPage={5}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={7}
        >
            <p>Test</p>
        </Pagination>
    );
    expect(changeSpy).not.toBeCalled();

    rerender(
        <Pagination
            currentLimit={10}
            currentPage={5}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={3}
        >
            <p>Test</p>
        </Pagination>
    );
    expect(changeSpy).toBeCalledWith(3);
});

test('Click previous link should call callback', async() => {
    const clickSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={5}
            onLimitChange={jest.fn()}
            onPageChange={clickSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    await userEvent.click(screen.queryByLabelText('su-angle-left'));
    expect(clickSpy).toBeCalledWith(4);
});

test('Click next link should call callback', async() => {
    const clickSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={6}
            onLimitChange={jest.fn()}
            onPageChange={clickSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    await userEvent.click(screen.queryByLabelText('su-angle-right'));
    expect(clickSpy).toBeCalledWith(7);
});

test('Click previous link on first page should not call callback', async() => {
    const clickSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={1}
            onLimitChange={jest.fn()}
            onPageChange={clickSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    await userEvent.click(screen.queryByLabelText('su-angle-left'));
    expect(clickSpy).not.toBeCalled();
});

test('Click next link on last page should not call callback', async() => {
    const clickSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={10}
            onLimitChange={jest.fn()}
            onPageChange={clickSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    await userEvent.click(screen.queryByLabelText('su-angle-right'));
    expect(clickSpy).not.toBeCalled();
});

test('Change limit should call callback', async() => {
    const changeSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={6}
            onLimitChange={changeSpy}
            onPageChange={jest.fn()}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    await userEvent.click(screen.queryByLabelText('su-angle-down'));
    await userEvent.click(screen.queryByText('20'));
    expect(changeSpy).toBeCalledWith(20);
});

test('Change limit to current limit should not call callback', async() => {
    const changeSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={6}
            onLimitChange={changeSpy}
            onPageChange={jest.fn()}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    await userEvent.click(screen.queryByLabelText('su-angle-down'));
    await userEvent.click(screen.queryAllByText('10')[1]);
    expect(changeSpy).not.toBeCalled();
});

test('Change callback should be called on blur when input was changed', async() => {
    const changeSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={2}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={50}
        >
            <p>Test</p>
        </Pagination>
    );

    const input = screen.queryByDisplayValue('2');
    await userEvent.type(input, '5');
    expect(changeSpy).not.toBeCalled();

    await userEvent.tab(); // tab away from input
    expect(changeSpy).toBeCalledWith(25);
});

test('Change callback should be called on enter when input was changed', async() => {
    const changeSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={2}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={50}
        >
            <p>Test</p>
        </Pagination>
    );

    const input = screen.queryByDisplayValue('2');
    await userEvent.type(input, '[Enter]');
    expect(changeSpy).not.toBeCalled();

    await userEvent.type(input, '5');
    expect(changeSpy).not.toBeCalled();

    await userEvent.type(input, '[Enter]');
    expect(changeSpy).toBeCalledWith(25);
});

test('Change callback should be called with 1 if input value is lower than 1', async() => {
    const changeSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={6}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    const input = screen.queryByDisplayValue('6');
    await userEvent.clear(input);
    await userEvent.type(input, '0');
    expect(changeSpy).not.toBeCalled();

    await userEvent.type(input, '[Enter]');
    expect(changeSpy).toBeCalledWith(1);
});

test('Change callback should be called with value of totalPages if input value is higher than total pages', async() => {
    const changeSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={6}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    const input = screen.queryByDisplayValue('6');
    await userEvent.clear(input);
    await userEvent.type(input, '12');
    expect(changeSpy).not.toBeCalled();

    await userEvent.type(input, '[Enter]');
    expect(changeSpy).toBeCalledWith(10);
});

test('Change callback should not be called if input value is equal to currentPage', async() => {
    const changeSpy = jest.fn();
    render(
        <Pagination
            currentLimit={10}
            currentPage={6}
            onLimitChange={jest.fn()}
            onPageChange={changeSpy}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    );

    const input = screen.queryByDisplayValue('6');
    await userEvent.clear(input);
    await userEvent.type(input, '6');
    expect(changeSpy).not.toBeCalled();

    await userEvent.type(input, '[Enter]');
    expect(changeSpy).not.toBeCalled();
});
