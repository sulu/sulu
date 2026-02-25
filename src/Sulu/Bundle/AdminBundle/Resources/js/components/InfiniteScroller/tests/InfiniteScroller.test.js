// @flow
import React from 'react';
import {render} from '@testing-library/react';
import InfiniteScroller from '../InfiniteScroller';

window.getComputedStyle = jest.fn();

jest.mock('../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_admin.reached_end_of_list':
                return 'Last page reached';
        }
    },
}));

const createProps = (props = {}) => ({
    currentPage: 1,
    loading: false,
    onPageChange: jest.fn(),
    totalPages: 10,
    ...props,
});

const renderInfiniteScrollerSnapshot = (props = {}) => {
    const bindScrollListenerSpy = jest.spyOn(InfiniteScroller.prototype, 'bindScrollListener')
        .mockImplementation(() => {});
    const unbindScrollListenerSpy = jest.spyOn(InfiniteScroller.prototype, 'unbindScrollListener')
        .mockImplementation(() => {});
    const view = render(
        <InfiniteScroller
            currentPage={1}
            loading={false}
            onPageChange={jest.fn()}
            totalPages={10}
            {...props}
        >
            <div />
        </InfiniteScroller>
    );

    const snapshot = view.asFragment();
    view.unmount();
    bindScrollListenerSpy.mockRestore();
    unbindScrollListenerSpy.mockRestore();

    return snapshot;
};

beforeEach(() => {
    jest.useRealTimers();
    window.getComputedStyle.mockReset();
    window.getComputedStyle.mockReturnValue({'overflow-y': 'auto'});
});

test('InfiniteScroller traverses the dom upwards until it finds a scroll container', () => {
    const scrollableContainer = {id: 'scrollable'};
    const middleContainer = {parentNode: scrollableContainer};
    const childContainer = {parentNode: middleContainer};

    // $FlowFixMe - test provides minimal node-like objects for style lookup.
    window.getComputedStyle.mockImplementation((element) => ({
        'overflow-y': element === scrollableContainer ? 'auto' : 'visible',
    }));

    const ref: any = React.createRef();
    const props = createProps();

    render(
        <InfiniteScroller {...props} ref={ref}>
            <div />
        </InfiniteScroller>
    );

    if (!ref.current) {
        throw new Error('Expected InfiniteScroller ref to be set');
    }

    expect(ref.current.getScrollContainer(childContainer)).toBe(scrollableContainer);
});

test('InfiniteScroller should call onPageChange if the bottom of the content is reached', () => {
    const loadSpy = jest.fn();
    const ref: any = React.createRef();
    const props = createProps({onPageChange: loadSpy});

    render(
        <InfiniteScroller {...props} ref={ref}>
            <div />
        </InfiniteScroller>
    );

    if (!ref.current) {
        throw new Error('Expected InfiniteScroller ref to be set');
    }

    ref.current.scrollContainer = {
        getBoundingClientRect: () => ({bottom: 260}),
        removeEventListener: jest.fn(),
    };
    ref.current.elementRef = {
        getBoundingClientRect: () => ({bottom: 300}),
    };

    ref.current.scrollListener();
    ref.current.scrollListener.flush();

    expect(loadSpy).toBeCalledWith(2);
});

test('InfiniteScroller should unbind scroll and resize event on unmount', () => {
    const getScrollContainerSpy = jest.spyOn(InfiniteScroller.prototype, 'getScrollContainer');
    const removeEventListenerSpy = jest.fn();
    getScrollContainerSpy.mockReturnValue({
        addEventListener: jest.fn(),
        getBoundingClientRect: jest.fn(() => ({bottom: 0})),
        removeEventListener: removeEventListenerSpy,
    });

    const {unmount} = render(
        <InfiniteScroller {...createProps()}>
            <div />
        </InfiniteScroller>
    );

    const scrollListener = getScrollContainerSpy.mock.instances[0].scrollListener;
    unmount();

    expect(removeEventListenerSpy).toBeCalledWith('resize', scrollListener, false);
    expect(removeEventListenerSpy).toBeCalledWith('scroll', scrollListener, false);

    getScrollContainerSpy.mockRestore();
});

test('InfiniteScroller should show a loader when the loading prop is set to true', () => {
    const tree = renderInfiniteScrollerSnapshot({loading: true});

    expect(tree).toMatchSnapshot();
});

test('InfiniteScroller should show an info message when the last page has been reached', () => {
    const tree = renderInfiniteScrollerSnapshot({currentPage: 10});

    expect(tree).toMatchSnapshot();
});
