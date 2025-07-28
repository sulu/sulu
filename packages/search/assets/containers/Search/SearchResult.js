// @flow
import React from 'react';
import textVersion from 'textversionjs';
import {Icon} from 'sulu-admin-bundle/components';
import searchResultStyles from './searchResult.scss';

type Props = {|
    description: ?string,
    icon: ?string,
    image: ?string,
    index: number,
    locale: ?string,
    onClick: (index: number) => void,
    resource: ?string,
    title: string,
|};

export default class SearchResult extends React.Component<Props> {
    handleClick = () => {
        const {index, onClick} = this.props;
        onClick(index);
    };

    render() {
        const {description, icon, image, locale, resource, title} = this.props;

        return (
            <div className={searchResultStyles.searchResult} onClick={this.handleClick} role="button">
                <div className={searchResultStyles.imageContainer}>
                    {image &&
                        <img className={searchResultStyles.image} src={image} />
                    }
                    {!image && icon &&
                        <div className={searchResultStyles.icon}>
                            <Icon name={icon} />
                        </div>
                    }
                </div>
                <div className={searchResultStyles.resultContainer}>
                    {resource &&
                        <div className={searchResultStyles.resource}>
                            {resource}
                        </div>
                    }
                    <div className={searchResultStyles.titleContainer}>
                        <div className={searchResultStyles.title}>
                            {title}
                        </div>
                        {locale && <div className={searchResultStyles.locale}> ({locale})</div>}
                    </div>
                    {description &&
                        <div className={searchResultStyles.description}>
                            {textVersion(description)}
                        </div>
                    }
                </div>
            </div>
        );
    }
}
