// @flow
import React from 'react';
import Icon from '../Icon';
import Loader from '../Loader';
import type {PaginationProps} from '../../types.js';
import {translate} from '../../utils/Translator';
import paginationStyles from './pagination.scss';

export default class Pagination extends React.PureComponent<PaginationProps> {
    static defaultProps = {
        loading: false,
    };

    hasNextPage = () => {
        const {current, total} = this.props;
        if (!current || !total) {
            return false;
        }

        return current < total;
    };

    hasPreviousPage = () => {
        const {current} = this.props;
        if (!current) {
            return false;
        }

        return 1 < current;
    };

    handlePreviousClick = () => {
        const {current, onChange} = this.props;
        if (!this.hasPreviousPage() || !current) {
            return;
        }

        onChange(current - 1);
    };

    handleNextClick = () => {
        const {current, onChange} = this.props;
        if (!this.hasNextPage() || !current) {
            return;
        }

        onChange(current + 1);
    };

    render() {
        const {children, current, loading, total} = this.props;

        if (loading && !total) {
            return <Loader />;
        }

        return (
            <section>
                {children}
                <nav className={paginationStyles.pagination}>
                    <div className={paginationStyles.loader}>
                        {loading && <Loader size={24} />}
                    </div>
                    <span className={paginationStyles.display}>
                        {translate('sulu_admin.page')}: {current} {translate('sulu_admin.of')} {total}
                    </span>
                    <button
                        className={paginationStyles.previous}
                        disabled={!this.hasPreviousPage()}
                        onClick={this.handlePreviousClick}
                    >
                        <Icon name="su-angle-left" />
                    </button>
                    <button
                        className={paginationStyles.next}
                        disabled={!this.hasNextPage()}
                        onClick={this.handleNextClick}
                    >
                        <Icon name="su-angle-right" />
                    </button>
                </nav>
            </section>
        );
    }
}
