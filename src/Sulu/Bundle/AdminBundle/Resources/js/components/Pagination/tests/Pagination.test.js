// @flow
import {mount, render} from 'enzyme';
import React from 'react';
import Pagination from '../Pagination';

jest.mock('../../../services/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
        }
    },
}));

test('Render pagination with page numbers', () => {
    const pagination = render(<Pagination current={5} total={10} onChange={jest.fn()} />);

    expect(pagination).toMatchSnapshot();
});

test('Render disabled next link if current page is last page', () => {
    const pagination = render(<Pagination current={5} total={5} onChange={jest.fn()} />);

    expect(pagination).toMatchSnapshot();
});

test('Render disabled previous link current page is first page', () => {
    const pagination = render(<Pagination current={1} total={5} onChange={jest.fn()} />);

    expect(pagination).toMatchSnapshot();
});

test('Click previous link should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination current={5} total={10} onChange={clickSpy} />);

    pagination.find('button').at(0).simulate('click');
    expect(clickSpy).toBeCalledWith(4);
});

test('Click next link should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination current={6} total={10} onChange={clickSpy} />);

    pagination.find('button').at(1).simulate('click');
    expect(clickSpy).toBeCalledWith(7);
});

test('Click previous link on first page should not call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination current={1} total={10} onChange={clickSpy} />);

    pagination.find('button').at(0).simulate('click');
    expect(clickSpy).not.toBeCalled();
});

test('Click next link on laster page should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination current={10} total={10} onChange={clickSpy} />);

    pagination.find('button').at(1).simulate('click');
    expect(clickSpy).not.toBeCalled();
});
