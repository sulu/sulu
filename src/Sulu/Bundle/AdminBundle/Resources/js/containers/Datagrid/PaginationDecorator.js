// @flow
import React from 'react';
import type {Element} from 'react';
import Pagination from '../../components/Pagination';
import InfiniteScroller from '../../components/InfiniteScroller';
import {translate} from '../../services/Translator';

type Props = {
    type: string,
    total: number,
    current: ?number,
    loading: boolean,
    children: Element<*>,
    onChange: (page: number) => void,
};

export default class PaginationDecorator extends React.PureComponent<Props> {
    static defaultProps = {
        type: 'pagination',
    };

    createInfiniteScrollWrapper() {
        const {
            total,
            current,
            loading,
            children,
        } = this.props;

        console.log(total);

        return (
            <section>
                {!!current && !!total &&
                    <InfiniteScroller
                        total={total}
                        current={current}
                        onLoad={this.handlePageChange}
                        loading={loading}
                        lastPageReachedText={translate('sulu_admin.reached_end_of_list')}
                    >
                        {children}
                    </InfiniteScroller>
                }
            </section>
        );
    }

    createPaginationWrapper() {
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

        return this.createPaginationWrapper();
    }
}
