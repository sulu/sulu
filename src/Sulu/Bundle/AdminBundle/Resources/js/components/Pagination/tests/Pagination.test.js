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
    expect(
        render(<Pagination current={5} loading={true} onChange={jest.fn()} total={10}><p>Test</p></Pagination>)
    ).toMatchSnapshot();
});

test('Render only loader if total is not passed initially', () => {
    expect(render(
        <Pagination current={0} loading={true} onChange={jest.fn()} total={undefined}><p></p></Pagination>
    )).toMatchSnapshot();
});

test('Render pagination with page numbers', () => {
    expect(render(<Pagination current={5} onChange={jest.fn()} total={10}><p>Test</p></Pagination>)).toMatchSnapshot();
});

test('Render disabled next link if current page is last page', () => {
    expect(render(<Pagination current={5} onChange={jest.fn()} total={5}><p>Test</p></Pagination>)).toMatchSnapshot();
});

test('Render disabled previous link current page is first page', () => {
    expect(render(<Pagination current={1} onChange={jest.fn()} total={5}><p>Test</p></Pagination>)).toMatchSnapshot();
});

test('Click previous link should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination current={5} onChange={clickSpy} total={10}><p>Test</p></Pagination>);

    pagination.find('button').at(0).simulate('click');
    expect(clickSpy).toBeCalledWith(4);
});

test('Click next link should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination current={6} onChange={clickSpy} total={10}><p>Test</p></Pagination>);

    pagination.find('button').at(1).simulate('click');
    expect(clickSpy).toBeCalledWith(7);
});

test('Click previous link on first page should not call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination current={1} onChange={clickSpy} total={10}><p>Test</p></Pagination>);

    pagination.find('button').at(0).simulate('click');
    expect(clickSpy).not.toBeCalled();
});

test('Click next link on laster page should call callback', () => {
    const clickSpy = jest.fn();
    const pagination = mount(<Pagination current={10} onChange={clickSpy} total={10}><p>Test</p></Pagination>);

    pagination.find('button').at(1).simulate('click');
    expect(clickSpy).not.toBeCalled();
});
