// @flow
import React from 'react';
import searchResultStyles from './searchResult.scss';

type Props = {|
    description: ?string,
    image: ?string,
    locale: ?string,
    resource: ?string,
    title: string,
|};

export default class SearchResult extends React.Component<Props> {
    render() {
        const {description, image, locale, resource, title} = this.props;

        return (
            <div className={searchResultStyles.searchResult}>
                <div className={searchResultStyles.imageContainer}>
                    {image &&
                        <img className={searchResultStyles.image} src={image} />
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
                            {description}
                        </div>
                    }
                </div>
            </div>
        );
    }
}
