// @flow
import React from 'react';
import type {Element} from 'react';
import Pagination from '../../components/Pagination';
import InfiniteScroller from '../../components/InfiniteScroller';

type Props = {
    type: 'default' | 'infiniteScroll',
    total: number,
    current: ?number,
    children: Element<*>,
    onChange: (page: number) => void,
};

export default class PaginationDecorator extends React.PureComponent<Props> {
    static defaultProps = {
        type: 'default',
    };

    createInfiniteScrollWrapper() {
        const {
            total,
            current,
            children,
        } = this.props;

        return (
            <section>
                {!!current && !!total &&
                    <InfiniteScroller
                        total={total}
                        current={current}
                        onLoad={this.handlePageChange}
                    >
                        {children}
                    </InfiniteScroller>
                }
            </section>
        );
    }

    createDefaultPaginationWrapper() {
        const {
            total,
            current,
            children,
        } = this.props;

        return (
            <section>
                {children}
                {!!current && !!total &&
                    <Pagination
                        total={total}
                        current={current}
                        onChange={this.handlePageChange}
                    />
                }
            </section>
        );
    }

    handlePageChange = (page: number) => {
        this.props.onChange(page);
    };

    render() {
        const {
            type,
        } = this.props;

        if (type === 'infiniteScroll') {
            return this.createInfiniteScrollWrapper();
        }

        return this.createDefaultPaginationWrapper();
    }
}
