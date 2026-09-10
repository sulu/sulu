// @flow
import React from 'react';
import classNames from 'classnames';
import publishIndicatorStyles from './publishIndicator.scss';

type Props = {
    className?: string,
    draft: boolean,
    published: boolean,
    review: boolean,
};

export default class PublishIndicator extends React.Component<Props> {
    static defaultProps = {
        draft: false,
        published: false,
        review: false,
    };

    render() {
        const {className, draft, published, review} = this.props;

        if (!draft && !published && !review) {
            return null;
        }

        const containerClass = classNames(
            publishIndicatorStyles.publishIndicator,
            className
        );

        return (
            <div className={containerClass}>
                {published && <span className={publishIndicatorStyles.published} />}
                {review && <span className={publishIndicatorStyles.review} />}
                {draft && <span className={publishIndicatorStyles.draft} />}
            </div>
        );
    }
}
