/* eslint-disable flowtype/require-valid-file-annotation */
import {mount} from 'enzyme';
import React from 'react';
import InfiniteScroller from '../InfiniteScroller';

window.getComputedStyle = jest.fn();

test('InfiniteScroller traverses the dom upwards until it finds a scroll container', () => {
    window.getComputedStyle.mockReturnValue({
        'overflow-y': 'auto',
    });

    const loadSpy = jest.fn();
    const infiniteScrollerWrapper = mount(
        <div id="scrollable">
            <InfiniteScroller
                onLoad={loadSpy}
            >
                <div />
            </InfiniteScroller>
        </div>
    );

    expect(infiniteScrollerWrapper.find('InfiniteScroller').get(0).scrollContainer.id).toBe('scrollable');
});

test('InfiniteScroller should call onLoad if the the bottom of the content is reached', (done) => {
    window.getComputedStyle.mockReturnValue({
        'overflow-y': 'auto',
    });

    const loadSpy = jest.fn();
    const infiniteScrollerWrapper = mount(
        <div id="scrollable">
            <InfiniteScroller
                total={10}
                current={1}
                onLoad={loadSpy}
            >
                <div />
            </InfiniteScroller>
        </div>
    );

    const infiniteScroller = infiniteScrollerWrapper.find('InfiniteScroller').get(0);

    infiniteScroller.scrollContainer = {
        getBoundingClientRect: () => ({
            bottom: 260,
        }),
    };
    infiniteScroller.elementRef = {
        getBoundingClientRect: () => ({
            bottom: 300,
        }),
    };
    infiniteScroller.scrollListener();

    setTimeout(() => {
        expect(loadSpy).toBeCalledWith(2);
        done();
    }, 250);
});

test('InfiniteScroller should unbind scroll and resize event on unmount', () => {
    window.getComputedStyle.mockReturnValue({
        'overflow-y': 'auto',
    });

    const loadSpy = jest.fn();
    const removeEventListenerSpy = jest.fn();
    const infiniteScrollerWrapper = mount(
        <div id="scrollable">
            <InfiniteScroller
                total={10}
                current={1}
                onLoad={loadSpy}
            >
                <div />
            </InfiniteScroller>
        </div>
    );

    const infiniteScroller = infiniteScrollerWrapper.find('InfiniteScroller').get(0);

    infiniteScroller.scrollContainer = {
        removeEventListener: removeEventListenerSpy,
    };

    infiniteScrollerWrapper.unmount();
    expect(removeEventListenerSpy).toBeCalledWith('resize', infiniteScroller.scrollListener, false);
    expect(removeEventListenerSpy).toBeCalledWith('scroll', infiniteScroller.scrollListener, false);
});
