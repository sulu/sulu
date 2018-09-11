// @flow
import React from 'react';
import ReactCircularProgressbar from 'react-circular-progressbar';
import circularProgressbarStyles from './circularProgressbar.scss';

type Props = {
    size: number,
    percentage: number,
    hidePercentageText: boolean,
};

export default class CircularProgressbar extends React.PureComponent<Props> {
    static defaultProps = {
        hidePercentageText: false,
        percentage: 0,
        size: 100,
    };

    handlePercentageText = (percentage: number) => {
        const {hidePercentageText} = this.props;

        if (hidePercentageText) {
            return null;
        }

        return `${percentage}%`;
    };

    render() {
        const {
            size,
            percentage,
        } = this.props;
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
                    percentage={percentage}
                    textForPercentage={this.handlePercentageText} // eslint-disable-line react/jsx-handler-names
                />
            </div>
        );
    }
}
