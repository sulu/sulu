// @flow
import React from 'react';
import Icon from '../Icon';
import {translate} from '../../services/Translator';
import paginationStyles from './pagination.scss';

type Props = {
    current: number,
    total: number,
    onChange: (page: number) => void,
};

export default class Pagination extends React.PureComponent<Props> {
    hasNextPage = () => {
        return this.props.current < this.props.total;
    };

    hasPreviousPage = () => {
        return this.props.current > 1;
    };

    handlePreviousClick = () => {
        if (!this.hasPreviousPage()) {
            return;
        }

        this.props.onChange(this.props.current - 1);
    };

    handleNextClick = () => {
        if (!this.hasNextPage()) {
            return;
        }

        this.props.onChange(this.props.current + 1);
    };

    render() {
        const {current, total} = this.props;

        return (
            <nav className={paginationStyles.pagination}>
                <div className={paginationStyles.control}>
                    <span className={paginationStyles.display}>
                        {translate('sulu_admin.page')}: {current} {translate('sulu_admin.of')} {total}
                    </span>
                    <button
                        className={paginationStyles.previous}
                        disabled={!this.hasPreviousPage()}
                        onClick={this.handlePreviousClick}
                    >
                        <Icon name="angle-left" />
                    </button>
                    <button
                        className={paginationStyles.next}
                        disabled={!this.hasNextPage()}
                        onClick={this.handleNextClick}
                    >
                        <Icon name="angle-right" />
                    </button>
                </div>
            </nav>
        );
    }
}
