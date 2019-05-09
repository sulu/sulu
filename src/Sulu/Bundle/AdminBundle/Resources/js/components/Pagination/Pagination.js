// @flow
import React, {type Node} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Icon from '../Icon';
import Input from '../Input';
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

@observer
class Pagination extends React.Component<Props> {
    @observable currentInputValue = 1;

    static defaultProps = {
        loading: false,
    };

    @action componentDidMount() {
        const {currentPage} = this.props;

        if (!currentPage) {
            return;
        }

        this.currentInputValue = this.props.currentPage;
    }

    @action componentDidUpdate(prevProps: Props) {
        const {currentPage} = this.props;

        if (prevProps.currentPage !== currentPage) {
            if (!currentPage) {
                this.currentInputValue = 1;
                return;
            }

            this.currentInputValue = currentPage;
        }
    }

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

        if (selected !== currentLimit) {
            onLimitChange(selected);
        }
    };

    @action handleInputChange = (value: ?string) => {
        if (value === undefined) {
            this.currentInputValue = undefined;
            return;
        }

        const page = parseInt(value);

        if (!isNaN(page)) {
            this.currentInputValue = page;
        }
    };

    @action handleInputBlur = () => {
        const {currentPage, onPageChange, totalPages} = this.props;
        let page = this.currentInputValue;

        if (!page || !totalPages || page < 1) {
            page = 1;
        } else if (page > totalPages) {
            page = totalPages;
        }

        if (page !== currentPage) {
            onPageChange(page);
        }

        this.currentInputValue = currentPage;
    };

    render() {
        const {currentInputValue} = this;
        const {children, loading, totalPages, currentLimit} = this.props;

        return (
            <section>
                {children}
                <nav className={paginationStyles.pagination}>
                    <span className={paginationStyles.display}>{translate('sulu_admin.per_page')}:</span>
                    <span>
                        <SingleSelect onChange={this.handleLimitChange} skin="dark" value={currentLimit}>
                            {AVAILABLE_LIMITS.map((limit) => (
                                <SingleSelect.Option key={limit} value={limit}>
                                    {limit}
                                </SingleSelect.Option>
                            ))}
                        </SingleSelect>
                    </span>

                    <div className={paginationStyles.loader}>
                        {loading && <Loader size={24} />}
                    </div>
                    <span>
                        {translate('sulu_admin.page')}:
                    </span>
                    <span className={paginationStyles.inputContainer}>
                        <Input
                            alignment="center"
                            inputMode="numeric"
                            onBlur={this.handleInputBlur}
                            onChange={this.handleInputChange}
                            skin="dark"
                            type="text"
                            value={currentInputValue}
                        />
                    </span>
                    <span className={paginationStyles.display}>
                        {translate('sulu_admin.of')} {totalPages}
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

export default Pagination;
