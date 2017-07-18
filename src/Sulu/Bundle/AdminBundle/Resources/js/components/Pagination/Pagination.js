// @flow
import classNames from 'classnames';
import React from 'react';
import Icon from '../Icon';
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
        const previousClass = classNames({
            [paginationStyles.previous]: true,
            [paginationStyles.enabled]: this.hasPreviousPage(),
        });
        const nextClass = classNames({
            [paginationStyles.next]: true,
            [paginationStyles.enabled]: this.hasNextPage(),
        });
        // TODO load translations in here?
        return (
            <nav className={paginationStyles.pagination}>
                <span className={paginationStyles.display}>
                    Page: {this.props.current} of {this.props.total}
                </span>
                <a className={previousClass} onClick={this.handlePreviousClick}>
                    <Icon name="angle-left" />
                </a>
                <a className={nextClass} onClick={this.handleNextClick}>
                    <Icon name="angle-right" />
                </a>
            </nav>
        );
    }
}
