// @flow
import React from 'react';
import debounce from 'debounce';
import type {Element, ElementRef} from 'react';
import Loader from '../Loader';
import infiniteScrollerStyles from './infiniteScroller.scss';

const THRESHOLD = 100;

type Props = {
    children: Element<*>,
    onChange: (page: number) => void,
    current: number,
    total: number,
    loading: boolean,
    lastPageReachedText: string,
};

export default class InfiniteScroller extends React.PureComponent<Props> {
    elementRef: ElementRef<'div'>;

    scrollContainer: ElementRef<*>;

    componentDidMount() {
        this.scrollContainer = this.getScrollContainer(this.elementRef.parentNode);

        this.bindScrollListener();
    }

    componentWillUnmount() {
        this.unbindScrollListener();
    }

    componentDidUpdate() {
        this.bindScrollListener();
    }

    getScrollContainer(parentContainer: ElementRef<*>) {
        if (!parentContainer || parentContainer === window.document) {
            return window.document.body;
        }

        if (this.isScrollable(parentContainer)) {
            return parentContainer;
        }

        return this.getScrollContainer(parentContainer.parentNode);
    }

    // We have to check for the overflow property inside the styling to detect if the container is scrollable
    // otherwise (using scrollHeight) we would have issues with async content loads leading to wrong container sizes.
    isScrollable(el: ElementRef<*>): boolean {
        const overflowY = window.getComputedStyle(el)['overflow-y'];

        return overflowY === 'auto' || overflowY === 'scroll';
    }

    setRef = (ref: ElementRef<'div'>) => {
        this.elementRef = ref;
    };

    bindScrollListener() {
        const {
            total,
            current,
        } = this.props;

        if (current >= total) {
            return;
        }

        this.scrollContainer.addEventListener('resize', this.scrollListener, false);
        this.scrollContainer.addEventListener('scroll', this.scrollListener, false);
    }

    unbindScrollListener() {
        this.scrollContainer.removeEventListener('resize', this.scrollListener, false);
        this.scrollContainer.removeEventListener('scroll', this.scrollListener, false);
    }

    scrollListener = debounce(() => {
        const {
            onChange,
            current,
        } = this.props;
        const {
            bottom: scrollContainerOffsetBottom,
        } = this.scrollContainer.getBoundingClientRect();
        const {
            bottom: elementOffsetBottom,
        } = this.elementRef.getBoundingClientRect();

        if ((elementOffsetBottom - scrollContainerOffsetBottom) < THRESHOLD)  {
            const nextPage = current + 1;

            onChange(nextPage);
            this.unbindScrollListener();
        }
    }, 200);

    render() {
        const {
            total,
            current,
            loading,
            children,
            lastPageReachedText,
        } = this.props;
        let indicator = null;

        if (loading) {
            indicator = <Loader />;
        } else if (current === total && !loading) {
            indicator = lastPageReachedText;
        }

        return (
            <div ref={this.setRef}>
                <div>
                    {children}
                </div>
                <div className={infiniteScrollerStyles.indicator}>
                    {indicator}
                </div>
            </div>
        );
    }
}
