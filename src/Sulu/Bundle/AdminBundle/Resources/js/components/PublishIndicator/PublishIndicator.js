// @flow
import React, {Fragment} from 'react';
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

        return (
            <Fragment>
                {published && <span className={publishIndicatorStyles.published} />}
                {draft && <span className={publishIndicatorStyles.draft} />}
            </Fragment>
        );
    }
}
