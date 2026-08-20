// @flow

import React, {Component} from 'react';
import classNames from 'classnames';
import ReplyIcon from './ReplyIcon';
import loaderStyles from './loader.scss';
import messageStyles from './message.scss';

type Props = {|
    commandTitle: string,
    expert: string,
|};

/**
 * @internal
 */
class Loader extends Component<Props> {
    render() {
        const {
            commandTitle,
            expert,
        } = this.props;

        const short = classNames(
            loaderStyles.skeletonLoader,
            loaderStyles.short
        );

        // the pending answer takes the same shape as a finished one, so the list does not jump once it arrives
        return (
            <div className={messageStyles.reply}>
                <span className={messageStyles.replyIcon}><ReplyIcon /></span>
                <div className={messageStyles.replyContent}>
                    <div className={messageStyles.command}>
                        {commandTitle}
                        <div className={messageStyles.expert}>{expert}</div>
                    </div>
                    <div className={messageStyles.message}>
                        <div className={loaderStyles.skeletonLoader}></div>
                        <div className={loaderStyles.skeletonLoader}></div>
                        <div className={short}></div>
                    </div>
                </div>
            </div>
        );
    }
}

export default Loader;
