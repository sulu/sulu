// @flow
import {mount, render} from 'enzyme';
import React from 'react';
import Pagination from '../Pagination';

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
        }
    },
}));

test('Render pagination with loader', () => {
    expect(render(<Pagination
        currentLimit={10}
        onLimitChange={jest.fn()}
        currentPage={5}
        totalPages={10}
        loading={true}
        onPageChange={jest.fn()}
    ><p>Test</p></Pagination>)).toMatchSnapshot();
});

test('Render only loader if total is not passed initially', () => {
    expect(render(<Pagination
        currentLimit={10}
        onLimitChange={jest.fn()}
        currentPage={0}
        totalPages={undefined}
        loading={true}
        onPageChange={jest.fn()}
    ><p></p></Pagination>)).toMatchSnapshot();
});

test('Render pagination with page numbers', () => {
    expect(render(<Pagination
        currentLimit={10}
        onLimitChange={jest.fn()}
        currentPage={5}
        totalPages={10}
        onPageChange={jest.fn()}
    ><p>
        Test</p></Pagination>)).toMatchSnapshot();
});

test('Render disabled next link if current page is last page', () => {
    expect(render(<Pagination
        currentLimit={10}
        onLimitChange={jest.fn()}
        currentPage={5}
        totalPages={5}
        onPageChange={jest.fn()}
    ><p>
        Test</p></Pagination>)).toMatchSnapshot();
});

test('Render disabled previous link current page is first page', () => {
    expect(render(<Pagination
        currentLimit={10}
        onLimitChange={jest.fn()}
        currentPage={1}
        totalPages={5}
        onPageChange={jest.fn()}
    ><p>
        Test</p></Pagination>)).toMatchSnapshot();
});

test('Click previous link should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination
        currentLimit={10}
        onLimitChange={jest.fn()}
        currentPage={5}
        totalPages={10}
        onPageChange={clickSpy}
    ><p>Test</p></Pagination>);

    pagination.find('button').at(1).simulate('click');
    expect(clickSpy).toBeCalledWith(4);
});

test('Click next link should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination
        currentLimit={10}
        onLimitChange={jest.fn()}
        currentPage={6}
        totalPages={10}
        onPageChange={clickSpy}
    ><p>Test</p></Pagination>);

    pagination.find('button').at(2).simulate('click');
    expect(clickSpy).toBeCalledWith(7);
});

test('Click previous link on first page should not call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination
        currentLimit={10}
        onLimitChange={jest.fn()}
        currentPage={1}
        totalPages={10}
        onPageChange={clickSpy}
    ><p>Test</p></Pagination>);

    pagination.find('button').at(1).simulate('click');
    expect(clickSpy).not.toBeCalled();
});

test('Click next link on laster page should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination
        currentLimit={10}
        onLimitChange={jest.fn()}
        currentPage={10}
        totalPages={10}
        onPageChange={clickSpy}
    ><p>Test</p></Pagination>);

    pagination.find('button').at(2).simulate('click');
    expect(clickSpy).not.toBeCalled();
});

test('Change limit should call callback', () => {
    const changeSpy = jest.fn();
    const pagination = mount(<Pagination
        currentLimit={10}
        onLimitChange={changeSpy}
        currentPage={6}
        totalPages={10}
        onPageChange={jest.fn()}
    ><p>Test</p></Pagination>);

    pagination.find('SingleSelect').prop('onChange')(20);
    expect(changeSpy).toBeCalledWith(20);
});

test('Change limit to current limit should not call callback', () => {
    const changeSpy = jest.fn();
    const pagination = mount(<Pagination
        currentLimit={10}
        onLimitChange={changeSpy}
        currentPage={6}
        totalPages={10}
        onPageChange={jest.fn()}
    ><p>Test</p></Pagination>);

    pagination.find('SingleSelect').prop('onChange')(10);
    expect(changeSpy).not.toBeCalled();
});
