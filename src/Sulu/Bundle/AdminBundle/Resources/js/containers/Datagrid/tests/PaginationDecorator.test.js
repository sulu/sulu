// @flow
import {render, shallow} from 'enzyme';
import React from 'react';
import PaginationDecorator from '../PaginationDecorator';

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

test('Render a default pagination if no type is specified', () => {
    const onChangeSpy = jest.fn();

    expect(render(
        <PaginationDecorator
            total={10}
            current={1}
            loading={false}
            onChange={onChangeSpy}
        >
            <div className="adapter" />
        </PaginationDecorator>
    )).toMatchSnapshot();
});

test('Render an infinite scroll pagination if the type is set to infiniteScroll', () => {
    const onChangeSpy = jest.fn();

    expect(render(
        <PaginationDecorator
            type="infiniteScroll"
            total={10}
            current={1}
            loading={false}
            onChange={onChangeSpy}
        >
            <div className="adapter" />
        </PaginationDecorator>
    )).toMatchSnapshot();
});

test('Render an infinite scroll loader if the loading prop is set to true', () => {
    const onChangeSpy = jest.fn();

    expect(render(
        <PaginationDecorator
            type="infiniteScroll"
            total={10}
            current={1}
            loading={true}
            onChange={onChangeSpy}
        >
            <div className="adapter" />
        </PaginationDecorator>
    )).toMatchSnapshot();
});

test('The default pagination calls the onChange callback if it wants to load a new page', () => {
    const testPage = 2;
    const onChangeSpy = jest.fn();

    const paginationDecorator = shallow(
        <PaginationDecorator
            total={10}
            current={1}
            loading={true}
            onChange={onChangeSpy}
        >
            <div className="adapter" />
        </PaginationDecorator>
    );

    paginationDecorator.find('Pagination').props().onChange(testPage);
    expect(onChangeSpy).toBeCalledWith(testPage);
});

test('The infinite scroll pagination calls the onChange callback if it wants to load a new page', () => {
    const testPage = 2;
    const onChangeSpy = jest.fn();

    const paginationDecorator = shallow(
        <PaginationDecorator
            type="infiniteScroll"
            total={10}
            current={1}
            loading={true}
            onChange={onChangeSpy}
        >
            <div className="adapter" />
        </PaginationDecorator>
    );

    paginationDecorator.find('InfiniteScroller').props().onLoad(testPage);
    expect(onChangeSpy).toBeCalledWith(testPage);
});
