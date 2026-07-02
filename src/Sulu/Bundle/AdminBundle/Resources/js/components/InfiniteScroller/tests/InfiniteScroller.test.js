// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import InfiniteScroller from '../InfiniteScroller';

window.getComputedStyle = jest.fn();

jest.mock('debounce', () => jest.fn((callback) => callback));

jest.mock('../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_admin.reached_end_of_list':
                return 'Last page reached';
        }
    },
}));

test('InfiniteScroller traverses the dom upwards until it finds a scroll container', () => {
    window.getComputedStyle.mockReturnValue({
        'overflow-y': 'auto',
    });

    const loadSpy = jest.fn();
    const {rerender} = render(
        <div id="scrollable">
            <InfiniteScroller
                currentPage={1}
                loading={false}
                onPageChange={loadSpy}
                totalPages={10}
            >
                <div>Child content</div>
            </InfiniteScroller>
        </div>
    );
    const scrollable = document.getElementById('scrollable');

    if (!scrollable) {
        throw new Error('Expected scrollable container');
    }
    const addEventListenerSpy = jest.spyOn(scrollable, 'addEventListener');

    rerender(
        <div id="scrollable">
            <InfiniteScroller
                currentPage={1}
                loading={false}
                onPageChange={loadSpy}
                totalPages={10}
            >
                <div>Child content</div>
            </InfiniteScroller>
        </div>
    );

    expect(addEventListenerSpy).toHaveBeenCalledWith('scroll', expect.any(Function), false);
    expect(addEventListenerSpy).toHaveBeenCalledWith('resize', expect.any(Function), false);
    addEventListenerSpy.mockRestore();
});

test('InfiniteScroller should call onPageChange if the the bottom of the content is reached', () => {
    window.getComputedStyle.mockReturnValue({
        'overflow-y': 'auto',
    });

    const loadSpy = jest.fn();
    render(
        <div id="scrollable">
            <InfiniteScroller
                currentPage={1}
                loading={false}
                onPageChange={loadSpy}
                totalPages={10}
            >
                <div>Child content</div>
            </InfiniteScroller>
        </div>
    );
    const scrollable = document.getElementById('scrollable');
    const section = screen.getByText('Child content').closest('section');

    if (!scrollable || !section) {
        throw new Error('Expected rendered scroller');
    }

    const scrollContainerRectSpy = jest.spyOn(scrollable, 'getBoundingClientRect').mockImplementation(() => ({
        bottom: 260,
    }));
    const elementRectSpy = jest.spyOn(section, 'getBoundingClientRect').mockImplementation(() => ({
        bottom: 300,
    }));

    fireEvent.scroll(scrollable);

    expect(loadSpy).toHaveBeenCalledWith(2);
    scrollContainerRectSpy.mockRestore();
    elementRectSpy.mockRestore();
});

test('InfiniteScroller should unbind scroll and resize event on unmount', () => {
    window.getComputedStyle.mockReturnValue({
        'overflow-y': 'auto',
    });

    const loadSpy = jest.fn();
    const {rerender, unmount} = render(
        <div id="scrollable">
            <InfiniteScroller
                currentPage={1}
                loading={false}
                onPageChange={loadSpy}
                totalPages={10}
            >
                <div>Child content</div>
            </InfiniteScroller>
        </div>
    );
    const scrollable = document.getElementById('scrollable');

    if (!scrollable) {
        throw new Error('Expected scrollable container');
    }

    const addEventListenerSpy = jest.spyOn(scrollable, 'addEventListener');
    const removeEventListenerSpy = jest.spyOn(scrollable, 'removeEventListener');

    rerender(
        <div id="scrollable">
            <InfiniteScroller
                currentPage={1}
                loading={false}
                onPageChange={loadSpy}
                totalPages={10}
            >
                <div>Child content</div>
            </InfiniteScroller>
        </div>
    );

    const scrollCall = addEventListenerSpy.mock.calls.find((call) => call[0] === 'scroll');
    const resizeCall = addEventListenerSpy.mock.calls.find((call) => call[0] === 'resize');

    if (!scrollCall || !resizeCall) {
        throw new Error('Expected scroll and resize listeners to be registered');
    }

    const scrollListener = scrollCall[1];
    const resizeListener = resizeCall[1];

    unmount();
    expect(removeEventListenerSpy).toHaveBeenCalledWith('resize', resizeListener, false);
    expect(removeEventListenerSpy).toHaveBeenCalledWith('scroll', scrollListener, false);
});

test('InfiniteScroller should show a loader when the loading prop is set to true', () => {
    window.getComputedStyle.mockReturnValue({
        'overflow-y': 'auto',
    });

    const loadSpy = jest.fn();
    const {asFragment} = render(
        <div id="scrollable">
            <InfiniteScroller
                currentPage={1}
                loading={true}
                onPageChange={loadSpy}
                totalPages={10}
            >
                <div />
            </InfiniteScroller>
        </div>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('InfiniteScroller should show an info message when the last page has been reached', () => {
    window.getComputedStyle.mockReturnValue({
        'overflow-y': 'auto',
    });

    const loadSpy = jest.fn();
    const {asFragment} = render(
        <div id="scrollable">
            <InfiniteScroller
                currentPage={10}
                loading={false}
                onPageChange={loadSpy}
                totalPages={10}
            >
                <div />
            </InfiniteScroller>
        </div>
    );

    expect(asFragment()).toMatchSnapshot();
});
