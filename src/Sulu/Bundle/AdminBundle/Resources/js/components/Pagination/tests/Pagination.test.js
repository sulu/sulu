// @flow
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import Pagination from '../Pagination';

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
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
    expect(render(
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
    )).toMatchSnapshot();
});

test('Render pagination with page numbers', () => {
    expect(render(
        <Pagination
            currentLimit={10}
            currentPage={5}
            onLimitChange={jest.fn()}
            onPageChange={jest.fn()}
            totalPages={10}
        >
            <p>Test</p>
        </Pagination>
    )).toMatchSnapshot();
});

test('Render disabled next link if current page is last page', () => {
    expect(render(
        <Pagination
            currentLimit={10}
            currentPage={5}
            onLimitChange={jest.fn()}
            onPageChange={jest.fn()}
            totalPages={5}
        >
            <p>Test</p>
        </Pagination>
    )).toMatchSnapshot();
});

test('Render disabled previous link current page is first page', () => {
    expect(render(
        <Pagination
            currentLimit={10}
            currentPage={1}
            onLimitChange={jest.fn()}
            onPageChange={jest.fn()}
            totalPages={5}
        >
            <p>Test</p>
        </Pagination>
    )).toMatchSnapshot();
});

test('Click previous link should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(
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

    pagination.find('button').at(1).simulate('click');
    expect(clickSpy).toBeCalledWith(4);
});

test('Click next link should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(
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

    pagination.find('button').at(2).simulate('click');
    expect(clickSpy).toBeCalledWith(7);
});

test('Click previous link on first page should not call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(
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

    pagination.find('button').at(1).simulate('click');
    expect(clickSpy).not.toBeCalled();
});

test('Click next link on laster page should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(
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

    pagination.find('button').at(2).simulate('click');
    expect(clickSpy).not.toBeCalled();
});

test('Change limit should call callback', () => {
    const changeSpy = jest.fn();
    const pagination = mount(
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

    pagination.find('SingleSelect').prop('onChange')(20);
    expect(changeSpy).toBeCalledWith(20);
});

test('Change limit to current limit should not call callback', () => {
    const changeSpy = jest.fn();
    const pagination = mount(
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

    pagination.find('SingleSelect').prop('onChange')(10);
    expect(changeSpy).not.toBeCalled();
});

test('Change input value should call callback', () => {
    const changeSpy = jest.fn();
    const pagination = mount(
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

    pagination.find('Input').prop('onChange')(3);
    pagination.find('Input').prop('onBlur')();
    expect(changeSpy).toBeCalledWith(3);
});

test('Change input value to something lower than 1 should call callback with 1', () => {
    const changeSpy = jest.fn();
    const pagination = mount(
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

    pagination.find('Input').prop('onChange')(0);
    pagination.find('Input').prop('onBlur')();
    expect(changeSpy).toBeCalledWith(1);
});

test('Change input value to something higher than total pages should call callback with the amount of total pages',
    () => {
        const changeSpy = jest.fn();
        const pagination = shallow(
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

        pagination.find('Input').prop('onChange')(12);
        pagination.find('Input').prop('onBlur')();
        expect(changeSpy).toBeCalledWith(10);
    }
);

test('Change input value to the current value should not call callback', () => {
    const changeSpy = jest.fn();
    const pagination = mount(
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

    pagination.find('Input').prop('onChange')(6);
    pagination.find('Input').prop('onBlur')();
    expect(changeSpy).not.toBeCalled();
});
