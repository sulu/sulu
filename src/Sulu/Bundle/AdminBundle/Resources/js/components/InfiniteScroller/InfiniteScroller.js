// @flow
import React from 'react';
import debounce from 'debounce';
import type {ElementRef} from 'react';
import {translate} from '../../utils/Translator';
import type {PaginationProps} from '../../types.js';
import Loader from '../Loader';
import infiniteScrollerStyles from './infiniteScroller.scss';

const THRESHOLD = 100;

export default class InfiniteScroller extends React.PureComponent<PaginationProps> {
    static defaultProps = {
        loading: false,
    };

    elementRef: ?ElementRef<'section'>;

    scrollContainer: ElementRef<*>;

    componentDidMount() {
        if (this.elementRef) {
            this.scrollContainer = this.getScrollContainer(this.elementRef.parentNode);
        }

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

    setRef = (ref: ?ElementRef<'section'>) => {
        this.elementRef = ref;
    };

    bindScrollListener() {
        const {
            current,
            total,
        } = this.props;

        if (!current || !total || current >= total) {
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
        const {elementRef} = this;
        if (!elementRef) {
            return;
        }

        const {
            onChange,
            current,
        } = this.props;
        const {
            bottom: scrollContainerOffsetBottom,
        } = this.scrollContainer.getBoundingClientRect();
        const {
            bottom: elementOffsetBottom,
        } = elementRef.getBoundingClientRect();

        if ((elementOffsetBottom - scrollContainerOffsetBottom) < THRESHOLD) {
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
        } = this.props;
        let indicator = null;

        if (loading) {
            indicator = <Loader />;
        } else if (current === total && !loading) {
            indicator = translate('sulu_admin.reached_end_of_list');
        }

        return (
            <section ref={this.setRef}>
                <div>
                    {children}
                </div>
                <div className={infiniteScrollerStyles.indicator}>
                    {indicator}
                </div>
            </section>
        );
    }
}
