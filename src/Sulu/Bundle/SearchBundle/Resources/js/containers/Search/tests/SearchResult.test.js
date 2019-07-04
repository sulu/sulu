// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import SearchResult from '../SearchResult';

test('Render only with title', () => {
    expect(render(
        <SearchResult
            description={undefined}
            icon={undefined}
            image={undefined}
            index={2}
            locale={undefined}
            onClick={jest.fn()}
            resource={undefined}
            title="Result"
        />
    )).toMatchSnapshot();
});

test('Render with all data', () => {
    expect(render(
        <SearchResult
            description="Description"
            icon={undefined}
            image="/image.jpg"
            index={5}
            locale="de"
            onClick={jest.fn()}
            resource="Page"
            title="Result"
        />
    )).toMatchSnapshot();
});

test('Render with icon instead of image', () => {
    expect(render(
        <SearchResult
            description="Description"
            icon="su-test"
            image={undefined}
            index={5}
            locale="de"
            onClick={jest.fn()}
            resource="Page"
            title="Result"
        />
    )).toMatchSnapshot();
});

test('Render with html description', () => {
    expect(render(
        <SearchResult
            description="<p>Description</p>"
            icon={undefined}
            image="/image.jpg"
            index={5}
            locale="de"
            onClick={jest.fn()}
            resource="Page"
            title="Result"
        />
    )).toMatchSnapshot();
});

test('Call callback with index when result is clicked', () => {
    const clickSpy = jest.fn();

    const searchResult = mount(
        <SearchResult
            description="Description"
            icon={undefined}
            image="/image.jpg"
            index={5}
            locale="de"
            onClick={clickSpy}
            resource="Page"
            title="Result"
        />
    );

    searchResult.find('div').at(0).simulate('click');

    expect(clickSpy).toBeCalledWith(5);
});
