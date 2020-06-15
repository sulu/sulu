// @flow
import React from 'react';
import publishIndicatorStyles from './publishIndicator.scss';
import classNames from 'classnames';

type Props = {
    containerClass?: string,
    draft: boolean,
    published: boolean,
};

export default class PublishIndicator extends React.Component<Props> {
    static defaultProps = {
        draft: false,
        published: false,
    };

    render() {
        const {containerClass, draft, published} = this.props;

        const className = classNames(
            publishIndicatorStyles.publishIndicator,
            containerClass,
        );

        return (
            <div className={className}>
                {published && <span className={publishIndicatorStyles.published} />}
                {draft && <span className={publishIndicatorStyles.draft} />}
            </div>
        );
    }
}
