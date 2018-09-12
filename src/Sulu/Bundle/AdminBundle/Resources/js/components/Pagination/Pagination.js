// @flow
import React, {type Node} from 'react';
import Icon from '../Icon';
import Loader from '../Loader';
import SingleSelect from '../SingleSelect';
import {translate} from '../../utils/Translator';
import paginationStyles from './pagination.scss';

type Props = {
    children: Node,
    currentLimit: number,
    currentPage: ?number,
    loading: boolean,
    onLimitChange: (limit: number) => void,
    onPageChange: (page: number) => void,
    totalPages: ?number,
};

const AVAILABLE_LIMITS = [10, 20, 50, 100];

export default class Pagination extends React.PureComponent<Props> {
    static defaultProps = {
        loading: false,
    };

    hasNextPage = () => {
        const {currentPage, totalPages} = this.props;
        if (!currentPage || !totalPages) {
            return false;
        }

        return currentPage < totalPages;
    };

    hasPreviousPage = () => {
        const {currentPage} = this.props;
        if (!currentPage) {
            return false;
        }

        return currentPage > 1;
    };

    handlePreviousClick = () => {
        const {currentPage, onPageChange} = this.props;
        if (!this.hasPreviousPage() || !currentPage) {
            return;
        }

        onPageChange(currentPage - 1);
    };

    handleNextClick = () => {
        const {currentPage, onPageChange} = this.props;
        if (!this.hasNextPage() || !currentPage) {
            return;
        }

        onPageChange(currentPage + 1);
    };

    handleLimitChange = (value: string | number) => {
        const {currentLimit, onLimitChange} = this.props;
        const selected = parseInt(value);

        if(selected !== currentLimit) {
            onLimitChange(selected);
        }
    };

    render() {
        const {children, currentPage, loading, totalPages, currentLimit} = this.props;

        if (loading && !totalPages) {
            return <Loader />;
        }

        return (
            <section>
                {children}
                <nav className={paginationStyles.pagination}>
                    <span className={paginationStyles.display}>{translate('sulu_admin.per_page')}:</span>
                    <span>
                        <SingleSelect onChange={this.handleLimitChange} skin="dark" value={currentLimit}>
                            {AVAILABLE_LIMITS.map((i) => (<SingleSelect.Option
                                key={i}
                                value={i}
                            >{i}</SingleSelect.Option>))}
                        </SingleSelect>
                    </span>

                    <div className={paginationStyles.loader}>
                        {loading && <Loader size={24} />}
                    </div>
                    <span className={paginationStyles.display}>
                        {translate('sulu_admin.page')}: {currentPage} {translate('sulu_admin.of')} {totalPages}
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
