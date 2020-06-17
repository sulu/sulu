// @flow
import React from 'react';
import classNames from 'classnames';
import publishIndicatorStyles from './publishIndicator.scss';

type Props = {
    className?: string,
    draft: boolean,
    published: boolean,
};

export default class PublishIndicator extends React.Component<Props> {
    static defaultProps = {
        draft: false,
        published: false,
    };

    render() {
        const {className, draft, published} = this.props;

        if (!draft && !published) {
            return null;
        }

        const containerClass = classNames(
            publishIndicatorStyles.publishIndicator,
            className
        );

        return (
            <div className={containerClass}>
                {published && <span className={publishIndicatorStyles.published} />}
                {draft && <span className={publishIndicatorStyles.draft} />}
            </div>
        );
    }
}
