// @flow
import React from 'react';
import searchResultStyles from './searchResult.scss';

type Props = {
    description: ?string,
    title: ?string,
    url: ?string,
};

export default class SearchResult extends React.Component<Props> {
    render() {
        const {description, title, url} = this.props;

        return (
            <div className={searchResultStyles.searchResult}>
                <div className={searchResultStyles.title}>{title}</div>
                <div className={searchResultStyles.url}>{url}</div>
                <div className={searchResultStyles.description}>{description}</div>
            </div>
        );
    }
}
