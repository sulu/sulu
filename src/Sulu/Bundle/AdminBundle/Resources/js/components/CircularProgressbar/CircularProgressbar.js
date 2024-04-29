// @flow
import React from 'react';
import {computed} from 'mobx';
import {CircularProgressbar as ReactCircularProgressbar} from 'react-circular-progressbar';
import circularProgressbarStyles from './circularProgressbar.scss';

type Props = {
    hidePercentageText: boolean,
    percentage: number,
    size: number,
};

export default class CircularProgressbar extends React.PureComponent<Props> {
    static defaultProps = {
        hidePercentageText: false,
        percentage: 0,
        size: 100,
    };

    @computed get percentageText() {
        const {hidePercentageText, percentage} = this.props;

        if (hidePercentageText) {
            return null;
        }

        return `${percentage}%`;
    }

    render() {
        const {size, percentage} = this.props;
        const sizeStyle = {
            width: size,
            height: size,
        };

        return (
            <div style={sizeStyle}>
                <ReactCircularProgressbar
                    background={true}
                    classes={{
                        root: circularProgressbarStyles.root,
                        path: circularProgressbarStyles.path,
                        tail: circularProgressbarStyles.tail,
                        text: circularProgressbarStyles.text,
                        background: circularProgressbarStyles.background,
                    }}
                    text={this.percentageText}
                    value={percentage}
                />
            </div>
        );
    }
}
