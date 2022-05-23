// @flow
import React from 'react';
import collapsedTabStyles from './collapsedTab.scss';

type Props = {
    children: string,
    index: number,
    onClick: (index: number) => void,
};

export default class CollapsedTab extends React.PureComponent<Props> {
    handleClick = () => {
        const {
            index,
            onClick,
        } = this.props;

        onClick(index);
    };

    render() {
        const {
            children,
        } = this.props;

        return (
            <li className={collapsedTabStyles.collapsedTab}>
                <button
                    onClick={this.handleClick}
                    title={children}
                    type="button"
                >
                    {children}
                </button>
            </li>
        );
    }
}
