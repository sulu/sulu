// @flow
import React from 'react';
import classNames from 'classnames';
import publishIndicatorStyles from './publishIndicator.scss';

type Props = {
    draft: boolean,
    published: boolean,
};

export default class PublishIndicator extends React.Component<Props> {
    static defaultProps = {
        draft: false,
        published: false,
    };

    render() {
        const {draft, published} = this.props;

        const publishIndicatorClass = classNames(
            publishIndicatorStyles.publishIndicator,
            {
                [publishIndicatorStyles.hasPublished]: published,
                [publishIndicatorStyles.hasDraft]: draft,
            }
        );

        return (
            <div className={publishIndicatorClass}>
                {published && <span className={publishIndicatorStyles.published} />}
                {draft && <span className={publishIndicatorStyles.draft} />}
            </div>
        );
    }
}
