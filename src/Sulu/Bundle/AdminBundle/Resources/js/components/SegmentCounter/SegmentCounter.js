// @flow
import React from 'react';
import classNames from 'classnames';
import {translate} from '../../utils/Translator';
import segmentCounterStyles from './segmentCounter.scss';

type Props = {
    delimiter: string,
    max: number,
    value: ?string,
};

export default class SegmentCounter extends React.Component<Props> {
    render() {
        const {delimiter, max, value} = this.props;
        const segmentsCount = value ? value.split(delimiter).length : 0;
        const segmentsLeft = max - segmentsCount;

        const segmentsLeftLabelClass = classNames(
            segmentCounterStyles.segmentCounter,
            {
                [segmentCounterStyles.exceeded]: segmentsLeft && segmentsLeft < 0,
            }
        );

        return (
            <label className={segmentsLeftLabelClass}>
                {segmentsLeft} {translate('sulu_admin.segments_left')}
            </label>
        );
    }
}
